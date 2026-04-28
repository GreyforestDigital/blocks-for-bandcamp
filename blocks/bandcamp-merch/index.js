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

var photoVersion = [
	{ label: 'Cropped', value: 'cropped' },
	{ label: 'Uncropped', value: 'uncropped' }
];

registerBlockType('blocks-for-bandcamp/bandcamp-merch', {
 	icon: {
		background: '#1da0c3', 
		foreground: '#ffffff',
		src: customIcon__bandcamp_embed
	},    
    edit: function (props) {

        var attributes = props.attributes;
        var setAttributes = props.setAttributes;

		// lightweight local UI state
		var _useState_loading = useState(false), loading = _useState_loading[0], setLoading = _useState_loading[1];
		var _useState_error   = useState(''),    error   = _useState_error[0],   setError   = _useState_error[1];
		var _useState_readonly = useState(false), isReadonly = _useState_readonly[0], setReadonly = _useState_readonly[1];

		useEffect(() => {
			if (!attributes.blockID) {
				setAttributes({ blockID: 'wp-block-' + Math.random().toString(36).slice(2, 12) });
			}
		}, []);
		
		useEffect(function () {
			setReadonly(!!attributes.albumID);
		}, [attributes.albumID]);

		// Call your REST route to read a <meta> tag from the Bandcamp page
		function fetchAlbumId() {
			var url  = attributes.url || '';

			if ( ! url ) { return; }

			setLoading(true); setError('');

			apiFetch({
				path: '/blocks-for-bandcamp/v1/meta',
				method: 'POST',
				data: { url: url, name: 'bc-page-properties' }
			}).then(function(res){
				// expects { ok:true, value: '123456' }
				var value = res && res.value ? String(res.value) : '';
				setAttributes({ albumID: value });
				setReadonly(true);
				setLoading(false);
			}).catch(function(e){
				//setAttributes({ albumID: '' });
				setReadonly(false);
				setError(e && e.message ? e.message : 'Failed to fetch album ID.');
				setLoading(false);
			});
		}


		var InspectorPanel = createElement(InspectorControls, null,
			createElement(PanelBody, null,
				createElement(TextareaControl, {
					label: "Album URL",
					value: attributes.url,
					readOnly: isReadonly,
					onChange: function (value) {
						return setAttributes({ url: value });
					},
					style: {wordBreak:'break-all'},
					rows:3
				}),
				createElement('small',{},
					'Enter album URL, then click "Fetch Album ID" to sync the album ID',
					createElement('br'),
					createElement('br')
				),					
				!attributes.albumID && createElement(Button, {
					variant: 'secondary',
					onClick: fetchAlbumId,
					style: {margin:"0 0 10px 0"},
				}, 'Fetch Album ID'),
				loading ? createElement(Spinner, null) : null,
				error ? createElement(Notice, { status: 'error', isDismissible: false }, error) : null,
				attributes.albumID && createElement('div',{style:{display:'flex',justifyContent:'space-between',alignItems:'center',margin:'0 0 10px 0'}}, 
					createElement(Button,{style:{background:'green',color:'white',boxShadow:'none'},disabled:true},'✔ SYNCED'),
					createElement(Button, {
						variant: 'secondary',
						onClick: function() { setAttributes({albumID: ''}); },
						style: {margin:"0",color:"red",borderColor:"red",boxShadow:"inset 0 0 0 1px red"},
					}, 'Clear'),	
				),	
				createElement(TextControl, {
					label: "Album ID",
					value: attributes.albumID,
					readOnly: true,
					onChange: function (value) {
						return setAttributes({ albumID: value });
					},
					style: {opacity:'0.5'}
				}),
				createElement(SelectControl, {
					label: 'Single Album or All Products?',
					value: attributes.single_or_all,
					options: [ { label: 'Single Album', value: 'single' }, { label: 'All Products', value: 'all' } ],
					onChange: function(val) { setAttributes({ single_or_all: val }); }
				}),	
				createElement(SelectControl, {
					label: 'Layout Style',
					value: attributes.layout_style,
					options: [ { label: 'Cards', value: 'cards' }, { label: 'List', value: 'list' } ],
					onChange: function(val) { setAttributes({ layout_style: val }); }
				}),	
				attributes.layout_style == 'cards' && createElement('label', {className:'bandcamp-label'},'Layout Columns'),
				attributes.layout_style == 'cards' && createElement(ButtonGroup, {style:{marginBottom:'16px'}},
					[1, 2, 3, 4, 5, 6].map( function( val ) {
						return createElement(Button,{
							isPressed: attributes.layout_columns === val,
							onClick: function() {
								setAttributes({ layout_columns: val });
							},
							key: val
						},val);
					})
				),					
				createElement('label', {className:'bandcamp-label'},'Photo Version'),
				createElement(ButtonGroup, {style:{marginBottom:'16px'}},
					photoVersion.map( function( option ) {
						return createElement(Button,{
							isPressed: attributes.photo_version === option.value,
							onClick: function() {
								setAttributes({ photo_version: option.value });
							},
							key: option.value
						},option.label);
					})
				),
				createElement('br'),
				attributes.photo_version == 'uncropped' && createElement('small',{},
					'NOTICE: Uncropped images are the full resolution photo uploaded to Bandcamp for this item. Too many will lead to large page load times.',
					createElement('br')
				),
				createElement('br'),
				createElement('label', {className:'bandcamp-label'},'DISPLAY OPTIONS'),
				createElement(ToggleControl, {
					label: 'Display Album Artist?', 
					checked: attributes.display_album_artist, 
					onChange: function(value) { setAttributes({ display_album_artist: value }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Album Title?', 
					checked: attributes.display_album_title, 
					onChange: function(value) { setAttributes({ display_album_title: value }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Description?', 
					checked: attributes.display_description, 
					onChange: function(value) { setAttributes({ display_description: value }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Edition Size?', 
					checked: attributes.display_edition_size, 
					onChange: function(value) { setAttributes({ display_edition_size: value }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Media Type?', 
					checked: attributes.display_media_type, 
					onChange: function(value) { setAttributes({ display_media_type: value }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Photos?', 
					checked: attributes.display_photos, 
					onChange: function(value) { setAttributes({ display_photos: value }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Price?', 
					checked: attributes.display_price, 
					onChange: function(value) { setAttributes({ display_price: value }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Quantity Available?', 
					checked: attributes.display_quantity_available, 
					onChange: function(value) { setAttributes({ display_quantity_available: value }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Sold Out Items?', 
					checked: attributes.display_sold_out_items, 
					onChange: function(value) { setAttributes({ display_sold_out_items: value }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Purchase Button?', 
					checked: attributes.display_purchase_button, 
					onChange: function(value) { setAttributes({ display_purchase_button: value }); } 
				}),
				attributes.display_purchase_button && createElement('h3', null, "Purchase Button : BG Color"),
				attributes.display_purchase_button && createElement(ColorPalette, {
					label: "Purchase Button : BG Color",
					value: attributes.style_purchase_button_bgcolor,
					onChange: function(value) { setAttributes({ style_purchase_button_bgcolor: value }); },
					disableAlpha: true
				}),
				attributes.display_purchase_button && createElement('h3', null, "Purchase Button : Text Color"),
				attributes.display_purchase_button && createElement(ColorPalette, {
					label: "Purchase Button : Text Color",
					value: attributes.style_purchase_button_textcolor,
					onChange: function(value) { setAttributes({ style_purchase_button_textcolor: value }); },
					disableAlpha: true
				}),				
			
			)
		);

		return createElement(Fragment,null,
			InspectorPanel,
			createElement('div', useBlockProps(),
				createElement(ServerSideRender, {
					block: "blocks-for-bandcamp/bandcamp-merch",
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
