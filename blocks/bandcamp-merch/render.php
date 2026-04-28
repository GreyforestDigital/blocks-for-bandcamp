<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$align = !empty( $attributes['align'] ) ? esc_attr( $attributes['align'] ) : '';
$anchor = !empty( $attributes['anchor'] ) ? esc_attr( $attributes['anchor'] ) : esc_attr('wp-block-' . wp_rand());
$blockID = !empty( $attributes['blockID'] ) ? esc_attr( $attributes['blockID'] ) : esc_attr('wp-block-' . wp_rand());
$className = !empty( $attributes['className'] ) ? esc_attr( $attributes['className'] ) : '';
$is_editor = defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$albumID = $attributes['albumID'] ?? false;
$display_album_art = $attributes['display_album_art'] ?? true;
$display_album_artist = $attributes['display_album_artist'] ?? true;
$display_album_title = $attributes['display_album_title'] ?? true;
$display_description = $attributes['display_description'] ?? true;
$display_edition_size = $attributes['display_edition_size'] ?? true;
$display_media_type = $attributes['display_media_type'] ?? true;
$display_photos = $attributes['display_photos'] ?? true;
$display_price = $attributes['display_price'] ?? true;
$display_purchase_button = $attributes['display_purchase_button'] ?? true;
$display_quantity_available = $attributes['display_quantity_available'] ?? true;
$display_sold_out_items = $attributes['display_sold_out_items'] ?? true;
$layout_style = $attributes['layout_style'] ?? 'cards';
$layout_columns = $attributes['layout_columns'] ?? 3;
$photo_version = $attributes['photo_version'] ?? "cropped";
$single_or_all = $attributes['single_or_all'] ?? 'single';
$style_purchase_button_bgcolor = $attributes['style_purchase_button_bgcolor'] ?? '#111111';
$style_purchase_button_textcolor = $attributes['style_purchase_button_textcolor'] ?? '#ffffff';
$url = $attributes['url'] ?? false;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$wrapper_attributes = get_block_wrapper_attributes([
	'id' => $anchor
]);
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
	'endpoint' => 'merch',
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

if ($is_editor && $data['cached'] == true) {
	echo '<span style="position:absolute;z-index:99;top:10px;right:10px;background:#1da0c3;color:#fff;font-size:10px;padding:3px 10px;border-radius:20px;display:inline-block;">CACHE</span>';
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>

<div <?php echo wp_kses_post(!$is_editor ? $wrapper_attributes : ''); ?>>

	<section id="<?php echo esc_attr($blockID); ?>" class="bandcamp-merch">

		<?php if (!empty($data['packages'])) : ?>
		<ul class="bandcamp-grid bandcamp-merch-layout-<?php echo esc_attr($layout_style); ?> bandcamp-columns-<?php echo esc_attr($layout_columns); ?>">
			<?php $package_exists = false; $pI = 0;
				foreach ($data['packages'] as $package) : 
					if (!$display_sold_out_items && $package['quantity_available'] < 1 && $package['quantity_available'] !== null) :
						continue;
					else :

						if ($single_or_all == 'all' || $single_or_all == 'single' && $package['album_id'] == $data['album_id']): $package_exists = true; ?>
							
							<li>
								<div class="bandcamp-merch-photos">
									
									<div class="bandcamp-merch-photos-main">
										<?php if ($display_purchase_button) : ?>
										<a href="<?php echo esc_url($package['photos'][0]['large']); ?>" class="bandcamp-lightbox" data-bandcamp-gallery="<?php echo esc_url($package['album_title'] ? $package['album_title'].'-'.$pI : $package['title'].'-'.$pI); ?>">
											<img loading="lazy" src="<?php echo esc_url($photo_version == 'uncropped' ? $package['photos'][0]['large'] : $package['photos'][0]['medium']); ?>" alt="<?php echo wp_kses_post($package['album_title'] ?? $package['title']); ?>">
										</a>
										<?php else : ?>
										<a href="<?php echo esc_url($package['url']); ?>" title="View Item" target="_blank">
											<img loading="lazy" src="<?php echo esc_url($photo_version == 'uncropped' ? $package['photos'][0]['large'] : $package['photos'][0]['medium']); ?>" alt="<?php echo wp_kses_post($package['album_title'] ?? $package['title']); ?>">
										</a>
										<?php endif; ?>
									</div>
									
									<?php if ($display_photos && count($package['photos']) > 1) : ?>
									<div class="bandcamp-merch-photos-gallery">
										<?php foreach ($package['photos'] as $photo) : ?>
										<a href="<?php echo esc_url($photo['large']); ?>" target="_blank" class="bandcamp-lightbox" data-bandcamp-gallery="<?php echo esc_url($package['album_title'] ? $package['album_title'].'-'.$pI : $package['title'].'-'.$pI); ?>">
											<img loading="lazy" src="<?php echo esc_url($photo['small']); ?>" alt="<?php echo wp_kses_post($package['album_title'] ?? $package['title']); ?>">
										</a>
										<?php endforeach; ?>
									</div>
									<?php endif; ?>

								</div>
								<div class="bandcamp-merch-information">

									<?php if ($display_album_title) : ?>
									<h3><?php echo esc_html($package['album_title'] ?? $package['title']); ?></h3>
									<?php endif; ?>

									<?php if ($display_album_artist) : ?>
									<h4><?php echo esc_html($package['album_artist']); ?></h4>
									<?php endif; ?>

									<span>
										<?php echo esc_html($package['title']); ?>
										<?php echo esc_html($attributes['display_price'] ? ' | $'.$package['price'] : ''); ?>
									</span>

									<?php if ($display_description && !empty($package['description'])) : ?>
									<p><i><?php echo esc_html($package['description']); ?></i></p>
									<?php endif; ?>

									<small>
										<strong>
										<?php echo esc_html($attributes['display_media_type'] ? $package['type'] : ''); ?>
										<?php
										if ($attributes['display_edition_size'] && $package['edition_size'] && $attributes['display_media_type']) :
											echo esc_html( __( ' | Edition of ', 'blocks-for-bandcamp' ) ) . esc_html( $package['edition_size'] );
										elseif ($attributes['display_edition_size'] && $package['edition_size'] && !$attributes['display_media_type']) :
											echo esc_html( __( 'Edition of ', 'blocks-for-bandcamp' ) ) . esc_html( $package['edition_size'] );
										endif;
										?>
										</strong>
									</small>
									
									<?php if ($display_purchase_button) : ?>
									<div>
										<?php if ($package['quantity_available'] > 0 || $package['quantity_available'] === null) : ?>
										<a target="_blank" class="bandcamp-merch-button" href="<?php echo esc_url($package['url']); ?>" style="background-color:<?php echo esc_attr($style_purchase_button_bgcolor); ?>;color:<?php echo esc_attr($style_purchase_button_textcolor); ?>">
											<?php echo esc_html__( 'Purchase on Bandcamp', 'blocks-for-bandcamp' ); ?> 
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
												<!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
												<path fill="<?php echo esc_attr($style_purchase_button_textcolor); ?>" d="M320 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l82.7 0L201.4 265.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L448 109.3l0 82.7c0 17.7 14.3 32 32 32s32-14.3 32-32l0-160c0-17.7-14.3-32-32-32L320 0zM80 32C35.8 32 0 67.8 0 112L0 432c0 44.2 35.8 80 80 80l320 0c44.2 0 80-35.8 80-80l0-112c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 112c0 8.8-7.2 16-16 16L80 448c-8.8 0-16-7.2-16-16l0-320c0-8.8 7.2-16 16-16l112 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L80 32z"/>
											</svg>
										</a>
										<?php else : ?>
										<a target="_blank" class="bandcamp-merch-button sold-out" href="<?php echo esc_url($package['url']); ?>"><?php echo esc_html__( 'Sold Out', 'blocks-for-bandcamp' ); ?></a>
										<?php endif; ?>
									</div>
									<?php endif; ?>

									<?php if ($display_quantity_available && !empty($package['quantity_available'])) : ?>
										<div><?php echo esc_html($package['quantity_available']) .' '.esc_html__( 'Remaining', 'blocks-for-bandcamp' ); ?></div>
									<?php endif; ?>
								
								</div>
							</li>
						<?php
						endif;
					endif; $pI++;
				endforeach;
			?>
		</ul>
		<?php endif; ?>


		<?php if ($package_exists == false) : ?>

			<div class="bandcamp-block-message bandcamp-block-error">
				<svg version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" xml:space="preserve">
					<path d="M10.1,8.9l-3.3,6h7l3.3-6H10.1z M21.6,11.9c0-5.3-4.3-9.6-9.6-9.6s-9.6,4.3-9.6,9.6s4.3,9.6,9.6,9.6 	S21.6,17.2,21.6,11.9z M12,20.9c-5,0-9-4-9-9s4-9,9-9s9,4,9,9S17,20.9,12,20.9z"/>
				</svg>
				<span>
					<?php 
					if ($single_or_all == 'all') : 
						echo esc_html__( 'NO MERCH ITEMS FOR THIS ACCOUNT', 'blocks-for-bandcamp' );
					else : 
						echo esc_html__( 'NO MERCH ITEMS FOR THIS ALBUM', 'blocks-for-bandcamp' ); 
					endif;
					?>
				</span>
			</div>

		<?php endif; ?>
		

	</section>

</div>