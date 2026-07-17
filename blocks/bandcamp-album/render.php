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
$backgroundColor = $attributes['backgroundColor'] ?? '#171717';
$buttonColor = $attributes['buttonColor'] ?? '#171717';
$display_album_art = $attributes['display_album_art'] ?? true;
$display_album_artist = $attributes['display_album_artist'] ?? true;
$display_album_link = $attributes['display_album_link'] ?? true;
$display_album_title = $attributes['display_album_title'] ?? true;
$display_audio = $attributes['display_audio'] ?? true;
$display_merch = $attributes['display_merch'] ?? true;
$display_playlist = $attributes['display_playlist'] ?? true;
$embedType = $attributes['embedType'] ?? 'custom';
$playerControlsColor = $attributes['playerControlsColor'] ?? '#ffffff';
$progressBarColor = $attributes['progressBarColor'] ?? '#ffffff';
$textColor = $attributes['textColor'] ?? '#ffffff';
$trackColor = $attributes['trackColor'] ?? '#ffffff';
$url =  $attributes['url'] ?? false;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$wrapper_attributes = get_block_wrapper_attributes([
	'id' => $anchor
]);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$buttonTextColor = gf_blocks_for_bandcamp__get_contrast_color( $buttonColor );
$blockID = sanitize_html_class( $blockID );

$css_width_progress_bar = $is_editor ? '50%' : '0';

$css = "
#{$blockID}.bandcamp-album {background-color:" . ( sanitize_hex_color( $backgroundColor ) ?: 'transparent' ) . ";}
#{$blockID}.bandcamp-album h3,
#{$blockID}.bandcamp-album h4,
#{$blockID}.bandcamp-album p,
#{$blockID}.bandcamp-album small,
#{$blockID}.bandcamp-album span,
#{$blockID}.bandcamp-album a {color:" . ( sanitize_hex_color( $textColor ) ?: 'inherit' ) . ";}
#{$blockID}.bandcamp-album .playlist-tracks * {color:" . ( sanitize_hex_color( $trackColor ) ?: 'inherit' ) . ";}
#{$blockID}.bandcamp-album a.album-link {background-color:" . ( sanitize_hex_color( $buttonColor ) ?: 'transparent' ) . ";color:" . ( sanitize_hex_color( $buttonTextColor ) ?: 'inherit' ) . " !important;}
#{$blockID}.bandcamp-album a.album-link div {color:" . ( sanitize_hex_color( $buttonTextColor ) ?: 'inherit' ) . " !important;}
#{$blockID}.bandcamp-album a.album-link path {fill:" . ( sanitize_hex_color( $buttonTextColor ) ?: 'inherit' ) . ";}
#{$blockID}.bandcamp-album .bandcamp-player-time {color:" . ( sanitize_hex_color( $textColor ) ?: 'inherit' ) . "}
#{$blockID}.bandcamp-album .bandcamp-player-play svg path {fill:" . ( sanitize_hex_color( $playerControlsColor ) ?: 'currentColor' ) . " !important;}
#{$blockID}.bandcamp-album .bandcamp-player-progress {background:" . ( sanitize_hex_color( $progressBarColor ) ?: 'transparent' ) . ";width:" . esc_attr( $css_width_progress_bar ) . ";}
#{$blockID}.bandcamp-album .bandcamp-player-progress-wrap {background:rgba(255,255,255,0.2);transition:.3s ease;}
";

// Frontend
//if ( ! $is_editor ) {
//	wp_add_inline_style( 'blocks-for-bandcamp-bandcamp-album-style', $css );
//} else {
	echo '<style>' . wp_kses( $css, [] ) . '</style>';
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
	if ($track['id'] == $data['featured_track_id']) :
		$data['featured_track_mp3'] = $track['mp3'];
		$data['featured_track_title'] = $track['title'];
		$data['featured_track_artist'] = $track['artist'];
	endif;
endforeach;

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////?>
 
 
<div <?php echo wp_kses_post(!$is_editor ? $wrapper_attributes : ''); ?>>

	<?php if ($data) : ?>

	<section id="<?php echo esc_attr($blockID); ?>" class="bandcamp-album">
		
		<div class="bandcamp-album-header">
			<div class="bandcamp-album-header-wrap">
				<?php if ($attributes['display_album_art']) : ?>
				<div class="bandcamp-album-artwork">
					<img src="<?php echo esc_url($data['album_art_medium']); ?>" alt="<?php echo esc_attr($data['album_title'].' Cover Art'); ?>">
				</div>
				<?php endif; ?>
				<div class="bandcamp-album-titles">
					<div>
						<h3><i><?php echo wp_kses_post($attributes['display_album_title'] ? $data['album_title'] : ''); ?></i></h3>
						<h4><?php echo wp_kses_post($attributes['display_album_artist'] ? $data['artist'] : ''); ?></h4>
						
						<?php if ($display_album_link) : ?>
						<a href="<?php echo esc_url($data['album_link']); ?>" class="album-link" target="_blank">
							<div>
								<?php echo esc_html__('Listen on Bandcamp','blocks-for-bandcamp'); ?>
								<svg version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" xml:space="preserve">
									<path d="M10.1,8.9l-3.3,6h7l3.3-6H10.1z M21.6,11.9c0-5.3-4.3-9.6-9.6-9.6s-9.6,4.3-9.6,9.6s4.3,9.6,9.6,9.6 	S21.6,17.2,21.6,11.9z M12,20.9c-5,0-9-4-9-9s4-9,9-9s9,4,9,9S17,20.9,12,20.9z"/>
								</svg>
							</div>
						</a>
						<?php endif; ?>

						<?php if ($display_audio && $embedType == 'bandcamp') : ?>
						<iframe src="https://bandcamp.com/EmbeddedPlayer/album=<?php echo esc_attr($data['album_id']); ?>/size=small/bgcol=<?php echo esc_attr(str_replace('#','',$backgroundColor)); ?>/linkcol=<?php echo esc_attr(str_replace('#','',gf_blocks_for_bandcamp__get_contrast_color($backgroundColor))); ?>/artwork=none/transparent=true/" seamless></iframe>
						<?php endif; ?>						
					</div>
				</div>
			</div>

			<?php if ($display_audio && $embedType == 'custom') : ?>
			<div class="bandcamp-album-audio bandcamp-player">
				<audio id="<?php echo esc_attr( $blockID . '-audio' ); ?>" preload="auto" tabindex="0" controls="" type="audio/mpeg" style="display:none">
					<source type="audio/mp3" src="<?php echo esc_url($data['featured_track_mp3'] ?? ''); ?>">
					<?php echo esc_html__('Sorry, your browser does not support HTML5 audio.','blocks-for-bandcamp'); ?>
				</audio>
				<button type="button" class="bandcamp-player-play" aria-label="<?php echo esc_attr__( 'Play Track', 'blocks-for-bandcamp' ); ?>" aria-controls="<?php echo esc_attr( $blockID . '-audio' ); ?>" data-play-label="<?php echo esc_attr__( 'Play Track', 'blocks-for-bandcamp' ); ?>" data-pause-label="<?php echo esc_attr__( 'Pause Track', 'blocks-for-bandcamp' ); ?>" aria-pressed="false">
					<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" xml:space="preserve">
						<path class="play" d="M 25,15 L 85,50 L 25,85 Z" />
						<path class="pause" style="display:none" d="M 20,20 H 40 V 80 H 20 Z M 60,20 H 80 V 80 H 60 Z" />
					</svg>
				</button>
				<div class="bandcamp-player-progress-wrap" role="slider" tabindex="0" aria-label="<?php echo esc_attr__( 'Seek audio', 'blocks-for-bandcamp' ); ?>" aria-controls="<?php echo esc_attr( $blockID . '-audio' ); ?>" aria-orientation="horizontal" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-valuetext="<?php echo esc_attr__( '00:00 / 00:00', 'blocks-for-bandcamp' ); ?>">
					<div class="bandcamp-player-progress-buffer"></div>
					<div class="bandcamp-player-progress"></div>
				</div>
				<div class="bandcamp-player-time" aria-live="off">00:00 / 00:00</div>
			</div>
			<?php endif; ?>

			<div class="bandcamp-album-background" style="background-image:url(<?php echo esc_url($attributes['display_album_art'] ? $data['album_art_large'] : ''); ?>);"></div>
		
		</div>

		<?php if (!empty($data['tracks']) && $display_playlist && $display_audio && $embedType == 'custom') : ?>
		<div class="bandcamp-album-tracks">
			<ul>
				<?php
				foreach ($data['tracks'] as $track) {
					if (isset($track['mp3'])) {
						$active = $track['id'] == $data['featured_track_id'] ? 'active' : '';
						$track_label = sprintf(
							/* translators: 1: track title, 2: track artist */
							esc_attr__( 'Play %1$s by %2$s', 'blocks-for-bandcamp' ),
							$track['title'],
							$track['artist']
						);
						echo '
						<li class="'.esc_attr($active).'">
							<span class="track-number">'.esc_html($track['tracknum']).'</span>
							<button type="button" class="track-link" data-track="'.esc_url($track['mp3']).'" aria-label="'.esc_attr( $track_label ).'" aria-current="'.esc_attr( $active === 'active' ? 'true' : 'false' ).'">'.wp_kses_post($track['artist']).' : '.wp_kses_post($track['title']).'</button>
							<span class="track-duration">'.esc_html($track['duration']).'</span>
						</li>';
					} else {
						echo '
						<li>
							<span class="track-number">'.esc_html($track['tracknum']).'</span>
							<button type="button" class="track-link" aria-label="'.esc_attr( sprintf( esc_attr__( 'Track %1$s by %2$s is not available', 'blocks-for-bandcamp' ), $track['title'], $track['artist'] ) ).'" disabled>'.wp_kses_post($track['artist']).' : '.wp_kses_post($track['title']).'</button>
							<span class="track-duration"></span>
						</li>';
					}		
				}
				?>
			</ul>
		</div>
		<?php endif; ?>

		<?php if (!empty($data['packages']) && $display_merch) : ?>
		<div class="bandcamp-album-merch">
			<ul>
				<?php
					$pI = 0;
					foreach ($data['packages'] as $package) { 
						if ($package['album_id'] == $data['album_id']) :  ?>
							
							<li>
								<div class="bandcamp-album-merch-photos">
									<?php foreach ($package['photos'] as $photo) : ?>
									<a href="<?php echo esc_url($photo['large']); ?>" target="_blank" class="bandcamp-lightbox" data-bandcamp-gallery="<?php echo esc_url($package['album_title'] ? $package['album_title'].'-'.$pI : $package['title'].'-'.$pI); ?>">
										<img loading="lazy" src="<?php echo esc_url($photo['small']); ?>" alt="<?php echo wp_kses_post($package['album_title'] ?? $package['title']); ?>">
									</a>
									<?php endforeach; ?>
								</div>
								<div class="bandcamp-album-merch-info">
									<h3><?php echo esc_html($package['album_title'] ?? $package['title']); ?></h3>
									<span><?php echo esc_html($package['title']); ?> | $<?php echo esc_html($package['price']); ?></span>
									<small><i><?php echo esc_html($package['description']); ?></i></small>
									<small><strong><?php echo esc_html($package['type']); ?><?php echo esc_html($package['edition_size'] ? ' | Edition of '.$package['edition_size'] : ''); ?></strong></small>
									<span>
										<?php if ($package['quantity_available'] > 0) : ?>
										<a target="_blank" class="album-link" href="<?php echo esc_url($package['url']); ?>">
											<div>
												<?php echo esc_html__('Purchase on Bandcamp','blocks-for-bandcamp'); ?>
												<svg version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" xml:space="preserve">
													<path d="M10.1,8.9l-3.3,6h7l3.3-6H10.1z M21.6,11.9c0-5.3-4.3-9.6-9.6-9.6s-9.6,4.3-9.6,9.6s4.3,9.6,9.6,9.6 	S21.6,17.2,21.6,11.9z M12,20.9c-5,0-9-4-9-9s4-9,9-9s9,4,9,9S17,20.9,12,20.9z"/>
												</svg>
											</div>
										</a>
										<?php else : ?>
										<a target="_blank" class="album-link" disabled style="color:red !important;" href="<?php echo esc_url($package['url']); ?>"><?php echo esc_html__('Sold Out','blocks-for-bandcamp'); ?></a>
										<?php endif; ?>
									</span>
								</div>
							</li>
						<?php
						endif;
						$pI++;
					}
				?>
			</ul>
		</div>
		<?php endif; ?>

	</section>

	<?php endif; ?>

</div>
