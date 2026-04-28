<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$align = !empty( $attributes['align'] ) ? esc_attr( $attributes['align'] ) : '';
$anchor = !empty( $attributes['anchor'] ) ? esc_attr( $attributes['anchor'] ) : esc_attr('wp-block-' . wp_rand());
$blockID = !empty( $attributes['blockID'] ) ? esc_attr( $attributes['blockID'] ) : esc_attr('wp-block-' . wp_rand());
$className = !empty( $attributes['className'] ) ? esc_attr( $attributes['className'] ) : '';
$is_editor = defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$textAlign = $attributes['textAlign'] ?? 'center';
$embedType = $attributes['embedType'] ?? 'shortcode';
$code = $attributes['code'] ?? false;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$wrapper_attributes = get_block_wrapper_attributes([
	'id' => $anchor,
	'style' => 'text-align:'.$textAlign.';'
]);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$bandcamp = new BlocksForBandcamp_API();

if (!$code) :
	
	echo wp_kses($bandcamp->render_error_html([
		'error'=>true,
		'message'=>__('EMBED CODE REQUIRED','blocks-for-bandcamp')
	]), 'post');
	return;

endif;

if ($embedType == 'url') :

	$data = $bandcamp->fetch(['endpoint'=>'embed','id'=>$code]);

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

	$output = '
	<iframe style="border:0;width:100%;max-width:700px;height:120px;margin:0px;" src="https://bandcamp.com/EmbeddedPlayer/album='.esc_attr($data['item_id']).'/size=large/bgcol=ffffff/linkcol=111111/tracklist=false/artwork=small/transparent=true/" seamless>
		<a href="'.esc_url($code).'">'.esc_html__( 'Listen on Bandcamp', 'blocks-for-bandcamp' ).'</a>
	</iframe>';

elseif ($embedType == 'shortcode') :

	$output = do_shortcode($code);

elseif ($embedType == 'iframe') :

	$output = html_entity_decode($code);

endif; 
?>

<div <?php echo wp_kses_post(!$is_editor ? $wrapper_attributes : 'style="text-align:'.esc_attr($textAlign).'"'); ?>>
	<div id="<?php echo esc_attr($blockID); ?>" class="bandcamp-embed">
		<?php echo wp_kses( $output, 'post'); ?>
	</div>
</div>