<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BlocksforBandcamp_API {

	private $logger;
	private $settings;
	private $cache_ttl;
	private $plugin;
	private $plugin_key = 'gf_blocks_for_bandcamp';

	///////////////////////////////////////////////////////////////////////////
	public function __construct() {

		// start logging class
		$this->logger = new BlocksForBandcamp_Logging();

		// set shared variables for use
		$this->settings = get_option($this->plugin_key.'__settings', []);
		$this->cache_ttl = isset($this->settings['transient']) ? intval($this->settings['transient']) * MINUTE_IN_SECONDS : 60 * MINUTE_IN_SECONDS;
	
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Perform cache check, build API fetch url, perform API call, cache and return data
	 * Example: $bandcamp->fetch(['endpoint'=>'album','id'=>'123456789']);
	 *
	 * @since 1.0.0
	 * @return array	 
	 */
	public function fetch(array $args)
	{

		$key = $this->cache_key($args);

		// 1. Try cache first
		$cached = get_transient($key);
		if ($cached !== false) {
			$cached['cached'] = true;
			return $cached;
		}

		// 2. Make API request
		if ($args['endpoint'] == 'album') {
			$data = $this->get_bandcamp_album_data($args['id']);
			$data = $this->parse_bandcamp_album_data($data);
		} elseif ($args['endpoint'] == 'merch') {
			$data = $this->get_bandcamp_album_data($args['id'],'merch');
			$data = $this->parse_bandcamp_album_data($data);
		} elseif ($args['endpoint'] == 'discography') {
			$data = $this->get_bandcamp_discography_data($args['id']);
		} elseif ($args['endpoint'] == 'embed') {
			$data = $this->get_meta_tag_content('','bc-page-properties',$args['id']);
			$data = json_decode($data,true);
		}
		
		// 3. Return error message if failed
		if (!empty($data['error'])) {
			return $data;
		}

		// 4. Cache and return data
		if ( !empty($data) ) {
			set_transient($key, $data, $this->cache_ttl);
		}
		return $data;

	}

	///////////////////////////////////////////////////////////////////////////
	/**
	* Gets HTML of Bandcamp page by URL
	*
	* @param string $url url of page to parse
	* @return string full html content of page
	*/
	public function fetch_bandcamp_page( $url )
	{

		// Basic SSRF hardening: require https://*.bandcamp.com
		$p = wp_parse_url( $url );
		if ( empty( $p['scheme'] ) || strtolower( $p['scheme'] ) !== 'https' ) {
			return ['error'=>true,'message'=>'bad_scheme | Only https URLs are allowed.'];
		}
		if ( empty( $p['host'] ) || ! preg_match( '/(^|\.)bandcamp\.com$/i', $p['host'] ) ) {
			return ['error'=>true,'message'=>'bad_host | Only bandcamp.com URLs are allowed.'];
		}

		$args = array(
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
				'Referer'    => isset($_SERVER['HTTP_HOST']) ? sanitize_url(wp_unslash($_SERVER['HTTP_HOST'])) : home_url()
			),
			'timeout' => 10,
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return ['error'=>true,'message'=>'Request Error: ' . $response->get_error_message()];
			$this->logger->log('error','Bandcamp page fetch failed', $url.' | Error: '.$response->get_error_message() );
			return false;
		}

	    $response_code = wp_remote_retrieve_response_code( $response );

		if ($response_code === 404) {
			return ['error'=>true,'message'=>'Bandcamp URL does not exist'];
		}

		$html = wp_remote_retrieve_body( $response );

		return $html;
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	* Parses a url for meta tag by name
	*
	* @param string $html fetched html content of page to parse
	* @param string $meta_name name of meta tag for capturing
	* @return string value of meta field OR false if doesn't exist
	*/
	public function get_meta_tag_content( $html, $meta_name, $url = null )
	{

		if (!empty($url)) {
			$html = $this->fetch_bandcamp_page($url);
			if (isset($html['error'])) {
				return;
			}
			$this->logger->log('info', 'Embed data sync successful', 'ID: '.$url );
		}

		if ( $html == false || empty($html) ) return false;

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

	///////////////////////////////////////////////////////////////////////////
	/**
	* Get album data based on album ID
	*
	* @since 1.0.0
	* @param string $album_id id of album from Bandcamp
	* @param string $endpoint 'merch' or null
	* @return array array of album data
	*/
	public function get_bandcamp_album_data($album_id, $endpoint = null)
	{

		if (empty($album_id)) { return false; }

		$response['error'] = true;
		$response['message'] = '';
		$response['data'] = [];

		$url = 'https://bandcamp.com/EmbeddedPlayer/album='.$album_id;
		$html = $this->fetch_bandcamp_page($url);

		if (empty($html)) { 
			$response['message'] = 'Could not fetch album page data.';
			$this->logger->log('error','Bandcamp album data was empty', 'ID: '.$album_id );
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
				$response['error'] = false;
				$response['message'] = "Tracks exist";
			} else {
				$this->logger->log('error','Bandcamp album data JSON decode error', 'ID: '.$album_id.' | Error: '.json_last_error_msg() );
				$response['message'] = "JSON decode error: " . json_last_error_msg();
				return $response;
			}
		} else {
			$response['message'] = "Could not find data-player-data attribute.";
			return $response;
		}

		$data_type = !empty($endpoint) ? ucwords($endpoint) : 'Album';
		$this->logger->log('info', $data_type.' data sync successful', 'ID: '.$album_id );

		return $response;

	}

	///////////////////////////////////////////////////////////////////////////
	/**
	* Parses bandcamp album data into formatted array for outputting on front-end
	*
	* @since 1.0.0
	* @param array $album_data array of raw data from Bandcamp album
	* @param string $trackNumber chosen track number if desired
	* @return array array of formatted album data
	*/
	public function parse_bandcamp_album_data($bandcamp_album_data)
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
			$t++;
		endforeach;

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

	///////////////////////////////////////////////////////////////////////////
	/**
	* Get all albums from an account's music page
	*
	* @param string $artist_url subdomain / url of artist/account from Bandcamp
	* @return array array of releases data
	*/
	function get_bandcamp_discography_data($artist_url)
	{

		if (empty($artist_url)) { 
			return ['error'=>true,'message'=>'Artist URL Required'];
		}

		// Setup response array
		$response['error'] = true;
		$response['message'] = '';
		$response['data'] = [];

		// URL for fetching
		$url = 'https://'.$artist_url.'.bandcamp.com/music';

		// Fetch HTML of page
		$html = $this->fetch_bandcamp_page($url);

		// Error if page not fetched successfully
		if (empty($html)) { 
			$this->logger->log('error','Data fetched from Bandcamp account was empty', 'ID: '.$artist_url );
			return ['error'=>true, 'message'=>'Could not fetch discography page data'];
		}

		if (!empty($html['error'])) {
			$this->logger->log('error',$html['message'], 'ID: '.$artist_url );
			return [ 'error'=>true, 'message'=>$html['message'] ];
		}

		// URL building for later use
		$parsed = wp_parse_url( $url );
		$base   = $parsed['scheme'] . '://' . $parsed['host'];

		// Init libxml + DOM parsing
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadHTML( $html );
		$xpath = new DOMXPath( $dom );

		// Variables for later use
		$releases = [];
		$source   = '';
		$node     = null;

		// Only keep/use ONE container: #music-grid OR #indexpage
		$music_grid = $xpath->query('//*[@id="music-grid"]');
		$indexpage  = $xpath->query('//*[@id="indexpage"]');

		// Determine page type and source of data
		if ( $music_grid && $music_grid->length ) {
			$node   = $music_grid->item(0);
			$source = 'music-grid';
		} elseif ( $indexpage && $indexpage->length ) {
			$node   = $indexpage->item(0);
			$source = 'indexpage';
		}

		// Error if neither element exists
		if ( ! $node ) {
			return ['error'=>true, 'message'=>'Album data not found.'];
		}

		/**
		* Convert relative URLs to absolute.
		*/
		$make_absolute_url = function( $value ) use ( $base ) {
			$value = trim( (string) $value );

			if ( $value === '' ) {
				return '';
			}

			if ( strpos( $value, '//' ) === 0 ) {
				$scheme = wp_parse_url( $base, PHP_URL_SCHEME );
				return $scheme . ':' . $value;
			}

			if ( preg_match( '#^https?://#i', $value ) ) {
				return $value;
			}

			if ( substr( $value, 0, 1 ) === '/' ) {
				return $base . $value;
			}

			return $base . '/' . ltrim( $value, '/' );
		};

		if ( $source === 'music-grid' ) {
			$items = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " music-grid-item ")]', $node);

			if ( $items && $items->length ) {
				foreach ( $items as $item ) {
					$image  = '';
					$title  = '';
					$link   = '';
					$artist = '';

					$link_nodes = $xpath->query('.//a[@href]', $item);
					if ( $link_nodes && $link_nodes->length ) {
						$link = $make_absolute_url( $link_nodes->item(0)->getAttribute('href') );
					}

					$img_nodes = $xpath->query('.//img[@src]', $item);
					if ( $img_nodes && $img_nodes->length ) {
						if (!empty($img_nodes->item(0)->getAttribute('data-original'))) {
							$image = $make_absolute_url( $img_nodes->item(0)->getAttribute('data-original') );
						} elseif (!empty($img_nodes->item(0)->getAttribute('src'))) {
							$image = $make_absolute_url( $img_nodes->item(0)->getAttribute('src') );
						}
						$image = str_replace('_2.jpg','_16.jpg',$image);
						$image = str_replace('_9.jpg','_16.jpg',$image);
						$image = str_replace('_10.jpg','_16.jpg',$image);
					}

					$title_nodes = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " title ")]', $item);
					if ( $title_nodes && $title_nodes->length ) {
						$title = trim( preg_replace( '/\s+/', ' ', $title_nodes->item(0)->textContent ) );
					}

					$artist = $this->get_meta_tag_content( $html, 'title' );

					// Skip empty cells / junk
					if ( $title === '' && $link === '' && $artist === '' && $image === '' ) {
						continue;
					}

					$releases[] = array(
						'image'  => $image,
						'title'  => $title,
						'link'    => $link,
						'artist' => $artist,
					);
				}
			}
		}

		if ( $source === 'indexpage' ) {
			$sets = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ipCellSet ")]', $node);

			if ( $sets && $sets->length ) {
				foreach ( $sets as $set ) {
					$image  = '';
					$title  = '';
					$link   = '';
					$artist = '';

					// Image src from the only image inside the container
					$img_nodes = $xpath->query('.//img[@src]', $set);
					if ( $img_nodes && $img_nodes->length ) {
						$image = $make_absolute_url( $img_nodes->item(0)->getAttribute('src') );
						$image = str_replace('_2.jpg','_16.jpg',$image);
						$image = str_replace('_9.jpg','_16.jpg',$image);
						$image = str_replace('_10.jpg','_16.jpg',$image);				
					}

					// Title + href from .ipCellLabel1 a
					$title_nodes = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ipCellLabel1 ")]//a[@href]', $set);
					if ( $title_nodes && $title_nodes->length ) {
						$title_node = $title_nodes->item(0);
						$title      = trim( $title_node->textContent );
						$link       = $make_absolute_url( $title_node->getAttribute('href') );
					}

					// Artist from .ipCellLabel2 a
					$artist_nodes = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ipCellLabel2 ")]//a', $set);
					if ( $artist_nodes && $artist_nodes->length ) {
						$artist = trim( $artist_nodes->item(0)->textContent );
					} else {
						$artist = $this->get_meta_tag_content( $html, 'title' );
					}

					// Skip empty cells / junk
					if ( $title === '' && $link === '' && $artist === '' && $image === '' ) {
						continue;
					}

					$releases[] = array(
						'image'  => $image,
						'title'  => $title,
						'link'    => $link,
						'artist' => $artist,
					);
				}
			}
		}

		if ( empty( $releases ) ) {
			return ['error'=>true, 'message'=>'No releases found on the Bandcamp page.'];
		}

		$response['data']['releases'] = $releases;
		$response['error'] = false;
		$response['message'] = 'Discography data sync successful';

		$this->logger->log('info','Discography data sync successful', 'ID: '.$artist_url );

		return $response;

	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Generate a safe transient key
	 *
	 * @since 1.0.0
	 * @return string	 
	 */
	private function cache_key($args)
	{
		$cache_key_raw = sanitize_title($this->plugin_key.'__' . $args['endpoint'].'_'.($args['id']));
		$cache_key = substr( $cache_key_raw, 0, 150 );
		return $cache_key;
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Standardized error response
	 *
	 * @since 1.0.0
	 * @return array	 
	 */
	private function error($message)
	{
		return [ 
			'error' => true, 
			'message' => esc_html($message)
		];
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Render a nice HTML block for errors
	 *
	 * @since 1.0.0
	 * @return string	 
	 */
	public function render_error_html($data)
	{

		$msg = esc_html($data['message'] ?? 'Unknown error');
		return '
			<div class="bandcamp-block-message bandcamp-block-error">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/></svg>
				<span>'.$msg.'</span>
			</div>';

	}
	///////////////////////////////////////////////////////////////////////////
	/**
	 * Render a nice HTML block for notices
	 *
	 * @since 1.0.0
	 * @return string	 
	 */
	public function render_notice_html($data)
	{

		$msg = esc_html($data['message'] ?? 'Unknown');
		return '
			<div class="bandcamp-block-message">
				<svg version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" xml:space="preserve"> <path d="M10.1,8.9l-3.3,6h7l3.3-6H10.1z M21.6,11.9c0-5.3-4.3-9.6-9.6-9.6s-9.6,4.3-9.6,9.6s4.3,9.6,9.6,9.6 	S21.6,17.2,21.6,11.9z M12,20.9c-5,0-9-4-9-9s4-9,9-9s9,4,9,9S17,20.9,12,20.9z"/> </svg>
				<span>'.$msg.'</span>
			</div>';
	}

}