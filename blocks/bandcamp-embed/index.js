var { useState, useEffect } = wp.element;
var apiFetch = wp.apiFetch;
var AlignmentToolbar = wp.blockEditor.AlignmentToolbar;
var BlockControls = wp.blockEditor.BlockControls;
var BorderControl = wp.components.BorderControl;
var BoxControl = wp.components.BoxControl;
var Button = wp.components.Button;
var ButtonGroup = wp.components.ButtonGroup;
var ColorPalette = wp.blockEditor.ColorPalette || wp.components.ColorPalette;
var ColorPicker = wp.components.ColorPicker;
var createElement = wp.element.createElement;
var Fragment = wp.element.Fragment;
var FontSizePicker  = wp.components.FontSizePicker;
var InnerBlocks = wp.blockEditor.InnerBlocks;
var InspectorControls = wp.blockEditor.InspectorControls;
var MediaUpload = wp.blockEditor.MediaUpload;
var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
var PanelBody = wp.components.PanelBody;
var registerBlockType = wp.blocks.registerBlockType;
var RangeControl = wp.components.RangeControl;
var RichText = wp.blockEditor.RichText;
var SelectControl = wp.components.SelectControl;
var ServerSideRender  = wp.serverSideRender;
var TextareaControl = wp.components.TextareaControl;
var TextControl = wp.components.TextControl;
var ToggleControl = wp.components.ToggleControl;
var Toolbar = wp.components.Toolbar;
var UnitControl = wp.components.__experimentalUnitControl || wp.components.UnitControl;
var useBlockProps = wp.blockEditor.useBlockProps;
var useSelect = wp.data.useSelect;
var Spinner = wp.components.Spinner;
var Notice = wp.components.Notice;

var customIcon__bandcamp_embed = createElement(
	'svg',
	{ xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24' },
	createElement('path', {
		d: 'M10.1,8.9l-3.3,6h7l3.3-6H10.1z M21.6,11.9c0-5.3-4.3-9.6-9.6-9.6s-9.6,4.3-9.6,9.6s4.3,9.6,9.6,9.6 	S21.6,17.2,21.6,11.9z M12,20.9c-5,0-9-4-9-9s4-9,9-9s9,4,9,9S17,20.9,12,20.9z'
	})
);

registerBlockType('blocks-for-bandcamp/bandcamp-embed', {
 	icon: {
		background: '#1da0c3', 
		foreground: '#ffffff',
		src: customIcon__bandcamp_embed
	},    
    edit: function (props) {

        var attributes = props.attributes;
        var setAttributes = props.setAttributes;

		useEffect(function () {
			if (attributes.previewMode && typeof window.initBandcamp === 'function') {


			}
		}, [attributes.previewMode]);

		var previewToggle = createElement(Fragment, null,
			createElement(BlockControls, null,
				createElement(AlignmentToolbar, {
					value: attributes.textAlign,
					onChange: function (value) {
						return setAttributes({ textAlign: value });
					}
				})
			)
		);

		var InspectorPanel = createElement(InspectorControls, null,
			createElement(PanelBody, null,
				createElement(SelectControl, {
					label: "Embed Type",
					value: attributes.embedType,
					options: [
						{ label: 'Shortcode', value: 'shortcode' },
						{ label: 'iFrame', value: 'iframe' },
						{ label: 'URL', value: 'url' }
					],
					onChange: function (value) {
						return setAttributes({ embedType: value });
					}
				}),
				createElement(TextareaControl, {
					label: "Embed Code",
					value: attributes.code,
					onChange: function (value) {
						return setAttributes({ code: value });
					}
				})				
			)
		);

		return createElement(Fragment,null,
			previewToggle,
			InspectorPanel,
			createElement('div', useBlockProps(),
				createElement(ServerSideRender, {
					block: "blocks-for-bandcamp/bandcamp-embed",
					httpMethod: "POST",
					attributes: attributes
				})
			)
		);

    },
    save: function () {
        return null; // Rendered server-side
    }
});
