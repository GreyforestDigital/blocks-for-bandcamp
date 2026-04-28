<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$align = !empty( $attributes['align'] ) ? esc_attr( $attributes['align'] ) : '';
$anchor = !empty( $attributes['anchor'] ) ? esc_attr( $attributes['anchor'] ) : esc_attr('wp-anchor-' . wp_rand());
$blockID = !empty( $attributes['blockID'] ) ? esc_attr( $attributes['blockID'] ) : esc_attr('wp-block-' . wp_rand());
$className = !empty( $attributes['className'] ) ? esc_attr( $attributes['className'] ) : '';
$is_editor = defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$artist_url =  $attributes['artist_url'] ?? '';
$button_color = $attributes['button_color'] ?? '#171717';
$button_text_color = $attributes['button_text_color'] ?? '#ffffff';
$display_album_art = $attributes['display_album_art'] ?? true;
$display_album_artist = $attributes['display_album_artist'] ?? true;
$display_album_link = $attributes['display_album_link'] ?? true;
$display_album_title = $attributes['display_album_title'] ?? true;
$layout_grid_gap = $attributes['layout_grid_gap'] ?? '2em';
$text_color = $attributes['text_color'] ?? '#171717';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$wrapper_attributes = get_block_wrapper_attributes([
	'id' => $anchor
]);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Build input-border string:
$release_border = '';
$release_border .= !empty( $attributes['release_border']['color'] ) ? 'border-color:' . esc_attr( $attributes['release_border']['color'] ) . ';'   : '';
$release_border .= !empty( $attributes['release_border']['style'] ) ? 'border-style:' . esc_attr( $attributes['release_border']['style'] ) . ';'   : '';
$release_border .= !empty( $attributes['release_border']['width'] ) ? 'border-width:' . esc_attr( $attributes['release_border']['width'] ) . ';'   : '';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$release_titles_align = $attributes['release_titles_align'] ?? 'left';
$release_titles_padding = '';
if (!empty($attributes['release_titles_padding'])) {
	$release_titles_padding .= 'padding:'
		. esc_attr($attributes['release_titles_padding']['top'] ?? '0') . ' '
		. esc_attr($attributes['release_titles_padding']['right'] ?? '0') . ' '
		. esc_attr($attributes['release_titles_padding']['bottom'] ?? '0') . ' '
		. esc_attr($attributes['release_titles_padding']['left'] ?? '0') . ';';
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$release_image_padding = '';
if (!empty($attributes['release_image_padding'])) {
	$release_image_padding .= 'padding:'
		. esc_attr($attributes['release_image_padding']['top'] ?? '0') . ' '
		. esc_attr($attributes['release_image_padding']['right'] ?? '0') . ' '
		. esc_attr($attributes['release_image_padding']['bottom'] ?? '0') . ' '
		. esc_attr($attributes['release_image_padding']['left'] ?? '0') . ';';
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//$buttonTextColor = gf_blocks_for_bandcamp__get_contrast_color( $button_color );
$blockID = sanitize_html_class( $blockID );

$css = "
#{$blockID} .bandcamp-release {" . ( sanitize_text_field( $release_border ) ?: 'border:1px solid #ddd' ) . ";}
#{$blockID} .bandcamp-release h5,
#{$blockID} .bandcamp-release h6,
#{$blockID} .bandcamp-release p,
#{$blockID} .bandcamp-release small,
#{$blockID} .bandcamp-release span,
#{$blockID} .bandcamp-release a {color:" . ( sanitize_hex_color( $text_color ) ?: 'inherit' ) . ";}
#{$blockID} .bandcamp-release a.bandcamp-release-button {background-color:" . ( sanitize_hex_color( $button_color ) ?: 'transparent' ) . ";color:" . ( sanitize_hex_color( $button_text_color ) ?: 'inherit' ) . " !important;}
#{$blockID} .bandcamp-release a.bandcamp-release-button div {color:" . ( sanitize_hex_color( $button_text_color ) ?: 'inherit' ) . " !important;}
#{$blockID} .bandcamp-release a.bandcamp-release-button path {fill:" . ( sanitize_hex_color( $button_text_color ) ?: 'inherit' ) . ";}
#{$blockID} .bandcamp-release .bandcamp-release-image {" . ( sanitize_text_field( $release_image_padding ) ?: 'padding:1rem' ) . ";}
#{$blockID} .bandcamp-release .bandcamp-release-titles {" . ( sanitize_text_field( $release_titles_padding ) ?: 'padding:1rem' ) . ";text-align:" . ( sanitize_text_field( $release_titles_align ) ) . ";}
#{$blockID} .bandcamp-grid {grid-gap:" . ( sanitize_text_field( $layout_grid_gap ) ?: '2em' ) . ";}
";

// Frontend
//if ( ! $is_editor ) {
//	wp_add_inline_style( 'blocks-for-bandcamp-bandcamp-discography-style', $css );
//} else {
	echo '<style>' . wp_kses( $css, [] ) . '</style>';
//}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$bandcamp = new BlocksForBandcamp_API();

// GET BANDCAMP DATA
$args = [
	'endpoint' => 'discography',
	'id' => $artist_url
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

if ($is_editor && $data['cached'] == true) {
	echo '<span style="position:absolute;z-index:99;top:10px;right:10px;background:#1da0c3;color:#fff;font-size:10px;padding:3px 10px;border-radius:20px;display:inline-block;">CACHE</span>';
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////?>
 
 
<div <?php echo wp_kses_post(!$is_editor ? $wrapper_attributes : ''); ?>>

	<?php if ($data) : ?>

	<section id="<?php echo esc_attr($blockID); ?>" class="bandcamp-discography">
		
		<div class="bandcamp-grid bandcamp-columns-<?php echo esc_attr($attributes['layout_columns'] ?? 3); ?> bandcamp-columns-mobile-<?php echo esc_attr($attributes['layout_columns_mobile'] ?? 2); ?>">
		
			<?php foreach ($data['data']['releases'] as $release) : ?>

			<div class="bandcamp-release">
				<?php if ($attributes['display_album_art']) : ?>
				<div class="bandcamp-release-image">
					<a target="_blank" href="<?php echo esc_url($release['link']); ?>" >
						<img loading="lazy" src="<?php echo esc_url($release['image']); ?>" alt="<?php echo esc_attr($release['title'].' Cover Art'); ?>">
					</a>				
				</div>
				<?php endif; ?>
				<div class="bandcamp-release-titles">
					<h5><?php echo wp_kses_post($attributes['display_album_artist'] ? $release['artist'] : ''); ?></h5>
					<h6><i><?php echo wp_kses_post($attributes['display_album_title'] ? $release['title'] : ''); ?></i></h6>
					
					<?php if ($display_album_link) : ?>
					<a href="<?php echo esc_url($release['link']); ?>" class="bandcamp-release-button" target="_blank">
						<div>
							<?php echo esc_html__('Listen on Bandcamp','blocks-for-bandcamp'); ?>
							<svg version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" xml:space="preserve">
								<path d="M10.1,8.9l-3.3,6h7l3.3-6H10.1z M21.6,11.9c0-5.3-4.3-9.6-9.6-9.6s-9.6,4.3-9.6,9.6s4.3,9.6,9.6,9.6 	S21.6,17.2,21.6,11.9z M12,20.9c-5,0-9-4-9-9s4-9,9-9s9,4,9,9S17,20.9,12,20.9z"/>
							</svg>
						</div>
					</a>
					<?php endif; ?>
				</div>
			</div>

			<?php endforeach; ?>

		</div>

	</section>

	<?php endif; ?>

</div>