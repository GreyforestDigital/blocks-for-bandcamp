<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$align = !empty( $attributes['align'] ) ? esc_attr( $attributes['align'] ) : '';
$anchor = !empty( $attributes['anchor'] ) ? esc_attr( $attributes['anchor'] ) : esc_attr('wp-anchor-' . wp_rand());
$blockID = !empty( $attributes['blockID'] ) ? esc_attr( $attributes['blockID'] ) : esc_attr('wp-block-' . wp_rand());
$className = !empty( $attributes['className'] ) ? esc_attr( $attributes['className'] ) : '';
$is_editor = defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$albumID = $attributes['albumID'] ?? false;
$artOpacity = $attributes['artOpacity'] ?? 0.5;
$artOpacityHover = $attributes['artOpacityHover'] ?? 0.2;
$aspectRatioHeight = $attributes['aspectRatioHeight'] ?? 1;
$aspectRatioWidth = $attributes['aspectRatioWidth'] ?? 1;
$backgroundColor = $attributes['backgroundColor'] ?? '#171717';
$buttonColor = $attributes['buttonColor'] ?? '#ffffff';
$display_album_artist = $attributes['display_album_artist'] ?? true;
$display_album_title = $attributes['display_album_title'] ?? true;
$display_album_link = $attributes['display_album_link'] ?? true;
$display_progress_bar = $attributes['display_progress_bar'] ?? true;
$display_track_title = $attributes['display_track_title'] ?? true;
$progressBarColor = $attributes['progressBarColor'] ?? '#000000';
$textColor = $attributes['textColor'] ?? '#ffffff';
$trackNumber = $attributes['trackNumber'] ?? false;
$url = $attributes['url'] ?? false;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$wrapper_attributes = get_block_wrapper_attributes([
	'id' => $anchor
]);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$css_display_progress_bar = $display_progress_bar ? 'block' : 'none';
$css_width_progress_bar = $is_editor ? '50%' : '0';

$blockID = sanitize_html_class( $blockID );

$css = "
#{$blockID}.bandcamp-miniplayer {background-color:" . ( sanitize_hex_color( $backgroundColor ) ?: 'transparent' ) . ";aspect-ratio:" . absint( $aspectRatioWidth ) . "/" . absint( $aspectRatioHeight ) . ";}
#{$blockID}.bandcamp-miniplayer .bandcamp-miniplayer-play svg path {fill:" . ( sanitize_hex_color( $buttonColor ) ?: 'currentColor' ) . " !important;}
#{$blockID}.bandcamp-miniplayer .bandcamp-miniplayer-art {opacity:" . ( is_numeric( $attributes['artOpacity'] ) ? (float) $attributes['artOpacity'] : 1 ) . ";}
#{$blockID}.bandcamp-miniplayer:hover .bandcamp-miniplayer-art {opacity:" . ( is_numeric( $attributes['artOpacityHover'] ) ? (float) $attributes['artOpacityHover'] : 1 ) . ";}
#{$blockID}.bandcamp-miniplayer .bandcamp-miniplayer-albumlink a path {fill:" . esc_attr( gf_blocks_for_bandcamp__get_contrast_color( $buttonColor ) ) . ";}
#{$blockID}.bandcamp-miniplayer h3, 
#{$blockID}.bandcamp-miniplayer h4, 
#{$blockID}.bandcamp-miniplayer p {color:" . ( sanitize_hex_color( $textColor ) ?: 'inherit' ) . ";padding:5px 0;}
#{$blockID}.bandcamp-miniplayer .bandcamp-miniplayer-progress {background:" . ( sanitize_hex_color( $progressBarColor ) ?: 'transparent' ) . ";display:" . esc_attr( $css_display_progress_bar ) . ";width:" . esc_attr( $css_width_progress_bar ) . ";}
";

//if ( !$is_editor ) {
//	wp_add_inline_style( 'gf-bandcamp-inline', $css );
//} else {
	echo "<style>".wp_kses($css,[])."</style>";
//}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$bandcamp = new BlocksForBandcamp_API();

if (!$url || !$albumID) :
	echo wp_kses($bandcamp->render_notice_html([
		'error'=>true,
		'message'=>__('BANDCAMP ALBUM ID REQUIRED','blocks-for-bandcamp')
	]), 'post');
	return;
endif;

// GET BANDCAMP DATA
$args = [
	'endpoint' => 'album',
	'id' => $albumID
];

$data = $bandcamp->fetch($args);

if (!empty($data['error']) ) {
	echo wp_kses($bandcamp->render_error_html($data), 'post');
	return;
}

if (empty($data)) :
	echo wp_kses($bandcamp->render_error_html([
		'error'=>true,
		'message'=>__('BANDCAMP URL IS NOT VALID','blocks-for-bandcamp')
	]), 'post');
	return;
endif;

if ($is_editor && !empty($data['cached'])) {
	echo '<span style="position:absolute;z-index:99;top:10px;right:10px;background:#1da0c3;color:#fff;font-size:10px;padding:3px 10px;border-radius:20px;display:inline-block;">CACHE</span>';
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

foreach ($data['tracks'] as $track) :
	if (!empty($trackNumber) && $track['tracknum'] == $trackNumber) {
		$data['featured_track_mp3'] = $track['mp3'];
		$data['featured_track_title'] = $track['title'];
		$data['featured_track_artist'] = $track['artist'];
	} elseif (empty($trackNumber) && $data['featured_track_id'] == $track['id']) {
		$data['featured_track_mp3'] = $track['mp3'];
		$data['featured_track_title'] = $track['title'];
		$data['featured_track_artist'] = $track['artist'];
	}
endforeach;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////?>

<div <?php echo wp_kses_post(!$is_editor ? $wrapper_attributes : ''); ?>>

	<?php if ($data) : ?>

	<section id="<?php echo esc_attr($blockID); ?>" class="bandcamp-miniplayer">
		<audio id="<?php echo esc_attr( $blockID . '-audio' ); ?>" preload="auto" tabindex="0" controls="" type="audio/mpeg" style="display:none">
			<source type="audio/mp3" src="<?php echo esc_url($data['featured_track_mp3']); ?>">
			<?php echo esc_html__('Sorry, your browser does not support HTML5 audio.','blocks-for-bandcamp'); ?>
		</audio>
		<button type="button" class="bandcamp-miniplayer-play" aria-label="<?php echo esc_attr__( 'Play Track', 'blocks-for-bandcamp' ); ?>" aria-controls="<?php echo esc_attr( $blockID . '-audio' ); ?>" data-play-label="<?php echo esc_attr__( 'Play Track', 'blocks-for-bandcamp' ); ?>" data-pause-label="<?php echo esc_attr__( 'Pause Track', 'blocks-for-bandcamp' ); ?>" aria-pressed="false">
			<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 800 800" style="enable-background:new 0 0 800 800;" xml:space="preserve">
				<path class="play" d="M553.4,386.6L342.3,242.2c-6.6-4.8-24.1-4.8-25.5,13.4v288.8c1.6,18.3,19.4,18.6,25.5,13.4l211.1-144.4 	C558.9,410.2,566.4,396.7,553.4,386.6L553.4,386.6z M349.5,513.6V286.4L515.3,400L349.5,513.6z"/>
				<path class="pause" style="display:none" d="M363.3,560.9h-37.6c-14.9,0-27-12.1-27-27V265c0-14.9,12.1-27,27-27h37.6c14.9,0,27,12.1,27,27v268.9 	C390.3,548.8,378.2,560.9,363.3,560.9z M328.7,530.9h31.6V268h-31.6V530.9z M474.3,560.9h-37.6c-14.9,0-27-12.1-27-27V265 	c0-14.9,12.1-27,27-27h37.6c14.9,0,27,12.1,27,27v268.9C501.3,548.8,489.2,560.9,474.3,560.9z M439.7,530.9h31.6V268h-31.6V530.9z" 	/>
				<path class="circle" d="M400,17.2C188.9,17.2,17.2,188.9,17.2,400S188.9,782.8,400,782.8S782.8,611.1,782.8,400S611.1,17.2,400,17.2z 	 M400,750.2c-193.1,0-350.2-157-350.2-350.2S206.9,49.8,400,49.8s350.2,157,350.2,350.2S593.1,750.2,400,750.2z"/>
			</svg>
		</button>
		<div class="bandcamp-miniplayer-text">
			<?php echo $attributes['display_track_title'] ? '<h3><i>'.wp_kses_post($data['featured_track_title']).'</i></h3>' : ''; ?>
			<?php echo $attributes['display_album_artist'] ? '<h4>'.wp_kses_post($data['featured_track_artist']).'</h4>' : ''; ?>
			<?php echo $attributes['display_album_title'] ? '<p>from <i>"'.wp_kses_post($data['album_title']).'"</i></p>' : ''; ?>
		</div>
		<div class="bandcamp-miniplayer-art" style="background-image:url(<?php echo esc_url($data['album_art_medium']); ?>);"></div>
		<div class="bandcamp-miniplayer-albumlink">
			<?php if ($display_album_link) : ?>
			<a href="<?php echo esc_url($data['album_link']); ?>" class="album-link" target="_blank">
				<div>
					<?php echo esc_html__( 'Listen on Bandcamp', 'blocks-for-bandcamp' ); ?>
					<svg version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" xml:space="preserve">
						<path d="M10.1,8.9l-3.3,6h7l3.3-6H10.1z M21.6,11.9c0-5.3-4.3-9.6-9.6-9.6s-9.6,4.3-9.6,9.6s4.3,9.6,9.6,9.6 	S21.6,17.2,21.6,11.9z M12,20.9c-5,0-9-4-9-9s4-9,9-9s9,4,9,9S17,20.9,12,20.9z"/>
					</svg>
				</div>
			</a>
			<?php endif; ?>
		</div>
		<div class="bandcamp-miniplayer-progress-wrap" role="slider" tabindex="0" aria-label="<?php echo esc_attr__( 'Seek audio', 'blocks-for-bandcamp' ); ?>" aria-controls="<?php echo esc_attr( $blockID . '-audio' ); ?>" aria-orientation="horizontal" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-valuetext="<?php echo esc_attr__( '00:00 / 00:00', 'blocks-for-bandcamp' ); ?>">
			<div class="bandcamp-miniplayer-progress-buffer"></div>
			<div class="bandcamp-miniplayer-progress"></div>
		</div>
	</section>

	<?php endif; ?>

</div>
