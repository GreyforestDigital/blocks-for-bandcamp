<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$align = !empty( $attributes['align'] ) ? esc_attr( $attributes['align'] ) : '';
$anchor = !empty( $attributes['anchor'] ) ? esc_attr( $attributes['anchor'] ) : esc_attr('wp-block-' . wp_rand());
$blockID = !empty( $attributes['blockID'] ) ? esc_attr( $attributes['blockID'] ) : esc_attr('wp-block-' . wp_rand());
$className = !empty( $attributes['className'] ) ? esc_attr( $attributes['className'] ) : '';
$is_editor = defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$button_text = $attributes['button_text'] ?? 'Redeem Download';
$input_placeholder_text = $attributes['input_placeholder_text'] ?? 'XXXX-XXXX';
$layout_style = $attributes['layout_style'] ?? 'stacked';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$wrapper_attributes = get_block_wrapper_attributes( [
    'id' => $anchor,
] );
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Helper: Given a raw BoxControl side value (e.g. "10", "2.5", "1em", ""),
 * return a valid CSS dimension (e.g. "10px", "2.5px", "1em", or "0px" if empty).
 */
$normalize_padding_value = function( $raw ) {
    // If empty or not set, treat as "0px"
    if ( $raw === '' || is_null( $raw ) ) { return '0px'; }
    // If it already ends with letters (e.g. "em", "rem", "%", "px"), leave as-is:
    if ( preg_match( '/[a-zA-Z%]+$/', $raw ) ) { return esc_attr( $raw ); }
    // If it’s purely numeric (e.g. "10" or "2.5"), append "px":
    if ( preg_match( '/^\d+(\.\d+)?$/', $raw ) ) { return esc_attr( $raw ) . 'px'; }
    // Fallback: return as-is (escaped)
    return esc_attr( $raw );
};

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Build CSS for inline styles
$cssButton = '';
$cssButtonHover = '';
$cssInput = '';
$cssInputHover = '';

// Input style attributes
$cssInput .= !empty( $attributes['inputBackgroundColor']) ? 'background-color:'.esc_attr($attributes['inputBackgroundColor']).';' : '';
$cssInput .= !empty( $attributes['inputTextColor'] ) ? 'color:' . esc_attr( $attributes['inputTextColor'] ) . ';' : '';
$cssInput .= !empty( $attributes['inputFontSize'] ) ? 'font-size:' . esc_attr( $attributes['inputFontSize'] ) . $attributes['inputFontUnit'] . ';' : '';
$cssInput .= !empty( $attributes['inputFontFamily'] ) ? 'font-family:' . esc_attr( $attributes['inputFontFamily'] ) . ';' : '';
$cssInput .= !empty( $attributes['inputFontStyle'] ) ? 'font-style:' . esc_attr( $attributes['inputFontStyle'] ) . ';' : '';
$cssInput .= !empty( $attributes['inputLineHeight'] ) ? 'line-height:' . floatval( $attributes['inputLineHeight'] ) . ';' : '';
$cssInput .= !empty( $attributes['inputTextAlign'] ) ? 'text-align:' . esc_attr( $attributes['inputTextAlign'] ) . ';' : '';	

// Button style attributes
$cssButton .= !empty( $attributes['buttonBackgroundColor'] ) ? 'background-color:' . esc_attr( $attributes['buttonBackgroundColor'] ) . ';' : '';
$cssButton .= !empty( $attributes['buttonTextColor'] ) ? 'color:' . esc_attr( $attributes['buttonTextColor'] ) . ';' : '';
$cssButton .= !empty( $attributes['buttonFontSize'] ) ? 'font-size:' . esc_attr( $attributes['buttonFontSize'] ) . $attributes['buttonFontUnit'] . ';' : '';
$cssButton .= !empty( $attributes['buttonFontFamily'] ) ? 'font-family:' . esc_attr( $attributes['buttonFontFamily'] ) . ';' : '';
$cssButton .= !empty( $attributes['buttonFontStyle'] ) ? 'font-style:' . esc_attr( $attributes['buttonFontStyle'] ) . ';' : '';
$cssButton .= !empty( $attributes['buttonLineHeight'] ) ? 'line-height:' . floatval( $attributes['buttonLineHeight'] ) . ';' : '';
$cssButton .= !empty( $attributes['buttonTextAlign'] ) ? 'text-align:' . esc_attr( $attributes['buttonTextAlign'] ) . ';' : '';

// Build input-border string:
if ( !empty( $attributes['inputBorder'] ) && is_array( $attributes['inputBorder'] ) ) {
    $ib 	   		= $attributes['inputBorder'];
    $cssInputHover .= !empty( $ib['color'] ) ? 'border-color:' . esc_attr( $ib['color'] ) . ';'   : '';
    $cssInputHover .= !empty( $ib['style'] ) ? 'border-style:' . esc_attr( $ib['style'] ) . ';'   : '';
    $cssInputHover .= !empty( $ib['width'] ) ? 'border-width:' . esc_attr( $ib['width'] ) . ';'   : '';
    $cssInputHover .= !empty( $ib['radius'] ) ? 'border-radius:' . esc_attr( $ib['radius'] ) . ';' : '';
}

// Build button-border string:
if ( !empty( $attributes['buttonBorder'] ) && is_array( $attributes['buttonBorder'] ) ) {
    $bb         	 = $attributes['buttonBorder'];
    $cssButtonHover .= !empty( $bb['color'] ) ? 'border-color:' . esc_attr( $bb['color'] ) . ';'   : '';
    $cssButtonHover .= !empty( $bb['style'] ) ? 'border-style:' . esc_attr( $bb['style'] ) . ';'   : '';
    $cssButtonHover .= !empty( $bb['width'] ) ? 'border-width:' . esc_attr( $bb['width'] ) . ';'   : '';
    $cssButtonHover .= !empty( $bb['radius']) ? 'border-radius:' . esc_attr( $bb['radius'] ) . ';' : '';
}

// Build input-padding string:
if ( ! empty( $attributes['inputPadding'] ) && is_array( $attributes['inputPadding'] ) ) {
    $ip     	= $attributes['inputPadding'];
    $top    	= $normalize_padding_value( $ip['top'] ?? '' );
    $right  	= $normalize_padding_value( $ip['right'] ?? '' );
    $bottom 	= $normalize_padding_value( $ip['bottom'] ?? '' );
    $left   	= $normalize_padding_value( $ip['left'] ?? '' );
    $cssInput 	.= "padding:{$top} {$right} {$bottom} {$left};";
}

// Build button-padding string:
if ( ! empty( $attributes['buttonPadding'] ) && is_array( $attributes['buttonPadding'] ) ) {
    $bp     	= $attributes['buttonPadding'];
    $top    	= $normalize_padding_value( $bp['top'] ?? '' );
    $right  	= $normalize_padding_value( $bp['right'] ?? '' );
    $bottom 	= $normalize_padding_value( $bp['bottom'] ?? '' );
    $left   	= $normalize_padding_value( $bp['left'] ?? '' );
    $cssButton .= "padding:{$top} {$right} {$bottom} {$left};";
}

// Build hover CSS:
if ( !empty( $attributes['inputHoverColor'] ) ) {
    $cssInputHover .= 'background-color: '.esc_attr( $attributes['inputHoverColor'] ).' !important';
}
if ( !empty( $attributes['buttonHoverColor'] ) ) {
    $cssButtonHover .= 'background-color: '.esc_attr( $attributes['buttonHoverColor'] ).' !important';
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$blockID = sanitize_html_class( $blockID );

$css = "
#{$blockID}.bandcamp-form button { $cssButton }
#{$blockID}.bandcamp-form button:hover { $cssButtonHover }
#{$blockID}.bandcamp-form input { $cssInput }
#{$blockID}.bandcamp-form input:hover { $cssInputHover }
";

//if ( !$is_editor ) {
//	wp_add_inline_style( 'blocks-for-bandcamp-bandcamp-form-style', $css );
//} else {
	echo "<style>".wp_kses($css,[])."</style>";
//}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////?>

<div <?php echo wp_kses_post(!$is_editor ? $wrapper_attributes : 'id="'.$anchor.'"'); ?>>
    <div id="<?php echo esc_attr($blockID); ?>" class="bandcamp-form bandcamp-form-layout-<?php echo esc_attr( $layout_style ); ?>">
        <form action="https://www.bandcamp.com/yum?" method="get">
            <input type="text" name="code" placeholder="<?php echo esc_attr( $input_placeholder_text ); ?>" />
            <button <?php echo esc_attr($is_editor ? 'disabled' : ''); ?> class="wp-element-button" type="submit">
                <?php echo esc_html( $button_text ); ?>
            </button>
        </form>
    </div>
</div>