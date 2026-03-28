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

if (!$code) :
	
	echo '
	<div class="bandcamp-block-message">
		<svg version="1.1" xmlns:cc="http://creativecommons.org/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 24 24" xml:space="preserve">
			<path d="M10.1,8.9l-3.3,6h7l3.3-6H10.1z M21.6,11.9c0-5.3-4.3-9.6-9.6-9.6s-9.6,4.3-9.6,9.6s4.3,9.6,9.6,9.6 	S21.6,17.2,21.6,11.9z M12,20.9c-5,0-9-4-9-9s4-9,9-9s9,4,9,9S17,20.9,12,20.9z"/>
		</svg>
		<span>'. esc_html__('EMBED CODE REQUIRED','blocks-for-bandcamp') .'</span>
	</div>';
	return;

endif;

$allowed_tags = array(
	'iframe' => array(
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
	)
);

if ($embedType == 'url') :

	$bandcamp_data_page = gf_blocks_for_bandcamp__get_meta_tag_content($code, 'bc-page-properties');
	$bandcamp_data_album = json_decode($bandcamp_data_page,true);

	$output = '
	<iframe style="border:0;width:100%;max-width:700px;height:120px;margin:0px;" src="https://bandcamp.com/EmbeddedPlayer/album='.esc_attr($bandcamp_data_album['item_id']).'/size=large/bgcol=ffffff/linkcol=111111/tracklist=false/artwork=small/transparent=true/" seamless>
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
		<?php
		if ($embedType == 'iframe' || $embedType == 'url') : 
			echo wp_kses( $output, $allowed_tags ); 
		else : 
			echo wp_kses_post($output); 
		endif; 
		?>
	</div>
</div>