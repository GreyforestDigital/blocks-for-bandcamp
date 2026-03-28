<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
//////////////////////////////////////////////////////////////////////////////////////////////////

add_action( 'rest_api_init', 'gf_blocks_for_bandcamp__meta_rest_route');

/**
* Registers custom REST route for fetching album id in gutenberg editor
*
* @return array response status + value
*/
function gf_blocks_for_bandcamp__meta_rest_route() {
    register_rest_route(
        'blocks-for-bandcamp/v1',
        '/meta',
        array(
            'methods'             => 'POST',
            'permission_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
            'args' => array(
                'url'  => array(
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'esc_url_raw',
                ),
                'name' => array(
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_key',
                ),
            ),
            'callback' => function ( WP_REST_Request $req ) {
                $url  = $req->get_param( 'url' );
                $name = $req->get_param( 'name' );

                // Basic SSRF hardening: require https://*.bandcamp.com
                $p = wp_parse_url( $url );
                if ( empty( $p['scheme'] ) || strtolower( $p['scheme'] ) !== 'https' ) {
                    return new WP_Error( 'bad_scheme', 'Only https URLs are allowed.', array( 'status' => 400 ) );
                }
                if ( empty( $p['host'] ) || ! preg_match( '/(^|\.)bandcamp\.com$/i', $p['host'] ) ) {
                    return new WP_Error( 'bad_host', 'Only bandcamp.com URLs are allowed.', array( 'status' => 400 ) );
                }

                // Cache result to avoid repeated fetches
                $key     = 'blocks_for_bandcamp__meta_' . md5( $url . '|' . $name );
                $cached  = get_transient( $key );
                if ( $cached !== false ) {
                    return array(
                        'ok'     => true,
                        'value'  => $cached,
                        'cached' => true,
                    );
                }

                // Meta tag parser
                $bandcamp_page_data = gf_blocks_for_bandcamp__get_meta_tag_content( $url, $name );
				$bandcamp_album_id = !empty($bandcamp_page_data) ? json_decode($bandcamp_page_data,true)['item_id'] : false;

                if ( $bandcamp_album_id === false ) {
                    return new WP_Error( 'no_meta', 'URL is not valid.', array( 'status' => 404 ) );
                }

                set_transient( $key, (string) $bandcamp_album_id, 10 * MINUTE_IN_SECONDS );

                return array(
                    'ok'    => true,
                    'value' => (string) $bandcamp_album_id,
                );
            },
        )
    );
}

//////////////////////////////////////////////////////////////////////////////////////////////////

/**
* Gets HTML of Bandcamp album page by URL for retrieving album ID
*
* @param string $url url of page to parse
* @return string value of album ID OR false if doesn't exist
*/
function gf_blocks_for_bandcamp__fetch_bandcamp_page( $url ) {
	$args = array(
		'headers' => array(
			'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
			'Referer'    => isset($_SERVER['HTTP_HOST']) ? sanitize_url(wp_unslash($_SERVER['HTTP_HOST'])) : home_url()
		),
		'timeout' => 10,
	);

	$response = wp_remote_get( $url, $args );

	if ( is_wp_error( $response ) ) {
		echo esc_html( 'Request Error: ' . $response->get_error_message() );
		return false;
	}

	$html = wp_remote_retrieve_body( $response );

	return $html;
}

//////////////////////////////////////////////////////////////////////////////////////////////////

/**
* Parses a url for meta tag by name
*
* @param string $url url of page to parse
* @param string $meta_name name of meta tag for capturing
* @return string value of meta field OR false if doesn't exist
*/
function gf_blocks_for_bandcamp__get_meta_tag_content( $url, $meta_name ) {

	$html = gf_blocks_for_bandcamp__fetch_bandcamp_page($url);

	if ( $html == false ) return false;

	libxml_use_internal_errors(true);
	$doc = new DOMDocument();
	$doc->loadHTML($html);
	$metas = $doc->getElementsByTagName('meta');

	foreach ( $metas as $meta ) {
		if ( $meta->getAttribute('name') === $meta_name ) {
			return $meta->getAttribute('content');
		}
	}
	return false;
}

//////////////////////////////////////////////////////////////////////////////////////////////////
add_shortcode('bandcamp', 'gf_blocks_for_bandcamp__register_bandcamp_shortcode');

/**
* Register "bandcamp" shortcode as legacy backup for Wordpress.com embed option
*
* @param array $atts shortcode attributes
* @return html outputs html of shortcode
*/
function gf_blocks_for_bandcamp__register_bandcamp_shortcode($atts) {
	$defaults = array(
		'width'     => '100%',
		'height'    => '120',
		'album'     => '',
		'size'      => 'large',
		'bgcol'     => 'ffffff',
		'linkcol'   => '0687f5',
		'tracklist' => '',
		'artwork'   => '',
		'minimal'   => ''
	);

	$styles = '';
	$atts = shortcode_atts($defaults, $atts, 'bandcamp');

	if (empty($atts['album'])) {
		return '<p><strong>Error:</strong> Missing <code>album</code> ID in Bandcamp shortcode.</p>';
	}

	// Start building the embedded URL
	$src = 'https://bandcamp.com/EmbeddedPlayer';
	$params = array(
		'album=' . esc_attr($atts['album']),
		'size=' . esc_attr($atts['size']),
		'bgcol=' . esc_attr($atts['bgcol']),
		'linkcol=' . esc_attr($atts['linkcol']),
	);

	// Conditionally add optional params
	if (!empty($atts['tracklist'])) {
		$params[] = 'tracklist=' . esc_attr($atts['tracklist']);
	}
	if (!empty($atts['artwork'])) {
		$params[] = 'artwork=' . esc_attr($atts['artwork']);
	}
	if (!empty($atts['minimal'])) {
		$params[] = 'minimal=' . esc_attr($atts['minimal']);
		$styles .= 'aspect-ratio:1/1;height:auto;';
	}

	// Always include transparent=true
	$params[] = 'transparent=true';

	$src .= '/' . implode('/', $params) . '/';

	// Optional fallback link (you can replace with album-specific if needed)
	$fallback = '<a href="https://bandcamp.com">Listen on Bandcamp</a>';

	if (strpos($atts['width'], '%') !== false) {
		// It's a percentage
	} else {
		// It's likely a pixel value
		// Optionally, append 'px' if needed
		$atts['width'] .= 'px';
	}

	// Return the full iframe
	$output ='<iframe style="border: 0; width:100%; max-width:'.esc_attr($atts['width']).'; height: '.esc_attr($atts['height']).'px; '.$styles.'" src="'.esc_url($src).'" seamless>'.$fallback.'</iframe>';

	return $output;
}

//////////////////////////////////////////////////////////////////////////////////////////////////

/**
* Return either "white" or "black" based on which contrasts best
* against the given background color.
*
* @param string $bg_color 6-digit hex color, with or without leading "#", e.g. "#336699" or "336699".
* @return string "white" or "black"
*/
function gf_blocks_for_bandcamp__get_contrast_color( $bg_color ) {
	// Remove leading "#" if present
	$hex = ltrim( $bg_color, '#' );

	// If not exactly 6 characters, default to black
	if ( strlen( $hex ) !== 6 ) {
		return 'black';
	}

	// Split into RGB components
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	// Compute brightness: (299*R + 587*G + 114*B) / 1000
	$brightness = ( ( 299 * $r ) + ( 587 * $g ) + ( 114 * $b ) ) / 1000;

	// If bright, use black text; otherwise use white text
	return ( $brightness > 128 ) ? esc_attr('#000000') : esc_attr('#ffffff');
}

//////////////////////////////////////////////////////////////////////////////////////////////////

/**
* Get album data based on album ID
*
* @param string $album_id id of album from Bandcamp
* @return array array of album data
*/
function gf_blocks_for_bandcamp__get_bandcamp_album_data($album_id){

	if (empty($album_id)) { return false; }

	$response['status'] = false;
	$response['message'] = '';
	$response['data'] = [];

	$url = 'https://bandcamp.com/EmbeddedPlayer/album='.$album_id;
	$html = gf_blocks_for_bandcamp__fetch_bandcamp_page($url);

	if (empty($html)) { 
		$response['message'] = '❌ Could not fetch album page data.';
		return $response;
	}

	// Step 1: Find the raw encoded data-player-data content
	if (preg_match('/data-player-data="([^"]+)"/', $html, $matches)) {
		// Step 2: Unescape HTML entities
		$json_escaped = html_entity_decode($matches[1]);

		// Step 3: Decode JSON
		$json_decoded = json_decode($json_escaped, true);

		if (json_last_error() === JSON_ERROR_NONE) {
			$response['data'] = $json_decoded;
			$response['status'] = true;
			$response['message'] = "Tracks exist";
		} else {
			$response['message'] = "❌ JSON decode error: " . json_last_error_msg();
		}
	} else {
		$response['message'] = "❌ Could not find data-player-data attribute.";
	}

	return $response;

}

//////////////////////////////////////////////////////////////////////////////////////////////////

/**
* Parses bandcamp album data into formatted array for outputting on front-end
*
* @param array $album_data array of raw data from Bandcamp album
* @param string $trackNumber chosen track number if desired
* @return array array of formatted album data
*/
function gf_blocks_for_bandcamp__parse_bandcamp_album_data($bandcamp_album_data, $trackNumber = false)
{
	$data = [];

	// ESTABLISH ALBUM DATA
	$data['artist'] = $bandcamp_album_data['data']['artist'];
	$data['band_url'] = $bandcamp_album_data['data']['band_url'];
	$data['album_id'] = $bandcamp_album_data['data']['album_id'];
	$data['album_title'] = $bandcamp_album_data['data']['album_title'];
	$data['album_art_thumb'] = $bandcamp_album_data['data']['album_art']; // _7
	$data['album_art_small'] = $bandcamp_album_data['data']['album_art_lg']; // _2
	$data['album_art_medium'] = 'https://f4.bcbits.com/img/a'.$bandcamp_album_data['data']['album_art_id'].'_16.jpg';
	$data['album_art_large'] = 'https://f4.bcbits.com/img/a'.$bandcamp_album_data['data']['album_art_id'].'_10.jpg';
	$data['album_link'] = $bandcamp_album_data['data']['linkback'];
	$data['album_release_date'] = $bandcamp_album_data['data']['release_date'] ?? 20990101;
	$data['publish_date'] = $bandcamp_album_data['data']['publish_date'];
	$data['featured_track_id'] = $bandcamp_album_data['data']['featured_track_id'];

	// LOOP THROUGH TRACKS
	$t = 1;
	foreach ($bandcamp_album_data['data']['tracks'] as $track) :
		$data['tracks'][$t]['artist'] = $track['artist'];
		$data['tracks'][$t]['title'] = $track['title'];
		$data['tracks'][$t]['album_title'] = $track['album_title'] ?? '';
		$data['tracks'][$t]['id'] = $track['id'];
		$data['tracks'][$t]['duration'] = gmdate("i:s",(int)$track['duration']);
		$data['tracks'][$t]['tracknum'] = $track['tracknum'] + 1;
		$data['tracks'][$t]['link'] = $track['title_link'];
		$data['tracks'][$t]['mp3'] = $track['file']['mp3-128'];	
		if ($data['featured_track_id'] == $track['id']) :
			$data['featured_track_mp3'] = $track['file']['mp3-128'];
			$featuredTrackNumber = $data['tracks'][$t]['tracknum'];
		endif;
		$t++;
	endforeach;

	// GET FEATURED TRACK DATA
	$data['featured_track_title'] = ($trackNumber == false || $trackNumber == 0) ? $data['tracks'][$featuredTrackNumber]['title'] : $data['tracks'][$trackNumber]['title'];
	$data['featured_track_artist'] = ($trackNumber == false || $trackNumber == 0) ? $data['tracks'][$featuredTrackNumber]['artist'] : $data['tracks'][$trackNumber]['artist'];
	$data['featured_track_mp3'] = ($trackNumber == false || $trackNumber == 0)  ? $data['tracks'][$featuredTrackNumber]['mp3'] : $data['tracks'][$trackNumber]['mp3'];
	
	// LOOP THROUGH PACKAGES
	$p = 0;
	foreach ($bandcamp_album_data['data']['packages'] as $package) :

		$data['packages'][$p]['id'] = $package['id'];
		$data['packages'][$p]['url'] = (strpos($package['url'], $data['band_url']) === 0) ? $package['url'] : $data['band_url'] . $package['url'];
		$data['packages'][$p]['type'] = $package['type_name'];
		$data['packages'][$p]['title'] = $package['title'];
		$data['packages'][$p]['description'] = $package['description'];
		$data['packages'][$p]['price'] = number_format($package['price'],'2','.',',');
		$data['packages'][$p]['currency'] = $package['currency'];
		$data['packages'][$p]['quantity_total'] = $package['quantity'] ?? 9999;
		$data['packages'][$p]['quantity_available'] = $package['quantity_available'] ?? 0;
		$data['packages'][$p]['edition_size'] = $package['edition_size'];
		$data['packages'][$p]['album_id'] = $package['album_id'];
		$data['packages'][$p]['album_title'] = $package['album_title'];
		$data['packages'][$p]['album_artist'] = $package['album_artist'];
		$data['packages'][$p]['album_release_date'] = $package['album_release_date'];

		$ph = 0;
		if (!empty($package['arts'])) :
			foreach ($package['arts'] as $photo) :
				$data['packages'][$p]['photos'][$ph]['thumb'] = 'https://f4.bcbits.com/img/'.str_pad($photo['image_id'],10,'0', STR_PAD_LEFT).'_7.jpg';
				$data['packages'][$p]['photos'][$ph]['small'] = 'https://f4.bcbits.com/img/'.str_pad($photo['image_id'],10,'0', STR_PAD_LEFT).'_2.jpg';
				$data['packages'][$p]['photos'][$ph]['medium'] = 'https://f4.bcbits.com/img/'.str_pad($photo['image_id'],10,'0', STR_PAD_LEFT).'_16.jpg';
				$data['packages'][$p]['photos'][$ph]['large'] = 'https://f4.bcbits.com/img/'.str_pad($photo['image_id'],10,'0', STR_PAD_LEFT).'_10.jpg';
				$ph++;
			endforeach;
		endif;

	$p++;
	endforeach;

	return $data;

}

//////////////////////////////////////////////////////////////////////////////////////////////////