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
                $key     = 'gf_blocks_for_bandcamp__meta_' . md5( $url . '|' . $name );
                $cached  = get_transient( $key );
                if ( $cached !== false ) {
                    return array(
                        'ok'     => true,
                        'value'  => $cached,
                        'cached' => true,
                    );
                }

				// Meta tag parser
				$bandcamp = new BlocksForBandcamp_API();
                $bandcamp_page_data = $bandcamp->fetch_bandcamp_page( $url );
                $bandcamp_meta_content = $bandcamp->get_meta_tag_content( $bandcamp_page_data, $name );

				$bandcamp_album_id = !empty($bandcamp_meta_content) ? json_decode($bandcamp_meta_content,true)['item_id'] : false;

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
* Adds svg element tags to wp_kses() escaping
*/
if (!function_exists('gf_blocks_for_bandcamp__kses_additions')) {

	add_filter( 'wp_kses_allowed_html', 'gf_blocks_for_bandcamp__kses_additions', 10, 2 );
	function gf_blocks_for_bandcamp__kses_additions( $tags, $context ) {

		if ( $context !== 'post' ) {
			return $tags;
		}

		$tags['svg'] = array(
			'class'           => true,
			'aria-hidden'     => true,
			'aria-labelledby' => true,
			'role'            => true,
			'xmlns'           => true,
			'width'           => true,
			'height'          => true,
			'viewbox'         => true,
			'fill'            => true,
		);

		$tags['iframe'] = array(
			'src'             => true,
			'width'           => true,
			'height'          => true,
			'frameborder'     => true,
			'allow'           => true,
			'allowfullscreen' => true,
			'loading'         => true,
			'class'           => true,
			'id'              => true,
			'style'           => true,
			'seamless'        => true,
		);

		$tags['path'] = array(
			'd'    => true,
			'fill' => true,
		);

		return $tags;
	}

}

//////////////////////////////////////////////////////////////////////////////////////////////////