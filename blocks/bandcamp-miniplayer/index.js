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

var customIcon__bandcamp_miniplayer = createElement(
	'svg',
	{ xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 24 24' },
	createElement('path', {
		d: 'M10.1,8.9l-3.3,6h7l3.3-6H10.1z M21.6,11.9c0-5.3-4.3-9.6-9.6-9.6s-9.6,4.3-9.6,9.6s4.3,9.6,9.6,9.6 	S21.6,17.2,21.6,11.9z M12,20.9c-5,0-9-4-9-9s4-9,9-9s9,4,9,9S17,20.9,12,20.9z'
	})
);

registerBlockType('blocks-for-bandcamp/bandcamp-miniplayer', {
 	icon: {
		background: '#1da0c3', 
		foreground: '#ffffff',
		src: customIcon__bandcamp_miniplayer
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

				createElement(TextControl, {
					label: "Track Number (0 for \"Featured Track\")",
					value: attributes.trackNumber,
					type: 'number',
					min:0,
					max:999,
					onChange: function (value) {
						if (value == undefined || value == '') { value = "0"; }
						return setAttributes({ trackNumber: value });
					}
				}),
				createElement( 'label', { className: 'bandcamp-label' }, 'Aspect Ratio' ),
				createElement('div', {style:{display:'grid',gridTemplateColumns:'1fr 30px 1fr',gridGap:'6px'}},
					createElement(TextControl, {
						type: 'number',
						value: attributes.aspectRatioWidth,
						onChange: function(val) { setAttributes({ aspectRatioWidth: val }); }
					}),
					createElement('div',{style:{textAlign:'center',lineHeight:'30px'}},'x'),
					createElement(TextControl, {
						type: 'number',
						value: attributes.aspectRatioHeight,
						onChange: function(val) { setAttributes({ aspectRatioHeight: val }); }
					}),
				),
				createElement(RangeControl, {
					label: 'Art Opacity', 
					value: attributes.artOpacity,
					step: 0.01,
					min: 0,
					max: 1,
					onChange: function(val) { setAttributes({ artOpacity: val }); } 
				}),
				createElement(RangeControl, {
					label: 'Art Opacity (Hover)', 
					value: attributes.artOpacityHover,
					step: 0.01,
					min: 0,
					max: 1,
					onChange: function(val) { setAttributes({ artOpacityHover: val }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Track Title?', 
					checked: attributes.display_track_title, 
					onChange: function(val) { setAttributes({ display_track_title: val }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Artist?', 
					checked: attributes.display_album_artist, 
					onChange: function(val) { setAttributes({ display_album_artist: val }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Album Title?', 
					checked: attributes.display_album_title, 
					onChange: function(val) { setAttributes({ display_album_title: val }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Album Link?', 
					checked: attributes.display_album_link, 
					onChange: function(val) { setAttributes({ display_album_link: val }); } 
				}),
				createElement(ToggleControl, {
					label: 'Display Progress Bar?', 
					checked: attributes.display_progress_bar, 
					onChange: function(val) { setAttributes({ display_progress_bar: val }); } 
				}),
				createElement( 'label', { className: 'bandcamp-label' }, 'Background Color' ),
				createElement(ColorPalette, {
					value: attributes.backgroundColor,
					onChange: function (val) {
						setAttributes({ backgroundColor: val });
					},
					disableAlpha: false
				}),
				createElement( 'label', { className: 'bandcamp-label' }, 'Text Color' ),
				createElement(ColorPalette, {
					value: attributes.textColor,
					onChange: function (val) {
						setAttributes({ textColor: val });
					},
					disableAlpha: false
				}),	
				createElement( 'label', { className: 'bandcamp-label' }, 'Play Button Color' ),
				createElement(ColorPalette, {
					value: attributes.buttonColor,
					onChange: function (val) {
						setAttributes({ buttonColor: val });
					},
					disableAlpha: false
				}),	
				createElement( 'label', { className: 'bandcamp-label' }, 'Progress Bar Color' ),
				createElement(ColorPalette, {
					value: attributes.progressBarColor,
					onChange: function (val) {
						setAttributes({ progressBarColor: val });
					},
					disableAlpha: false
				})
			)
		);

		return createElement(Fragment,null,
			InspectorPanel,
			createElement('div', useBlockProps(),
				createElement(ServerSideRender, {
					block: "blocks-for-bandcamp/bandcamp-miniplayer",
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
