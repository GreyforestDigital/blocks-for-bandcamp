<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BlocksForBandcamp_init {

	///////////////////////////////////////////////////////////////////////////
	function __construct()
	{
		add_action('init',							[$this,'gf_blocks_for_bandcamp__required_notice'] );
		add_action('init',							[$this,'gf_blocks_for_bandcamp__load_blocks'] );
		add_action('plugins_loaded',				[$this,'gf_blocks_for_bandcamp__include_functions'] );
		add_action('wp_enqueue_scripts',			[$this,'gf_blocks_for_bandcamp__stylesheets'] );
		add_action('admin_enqueue_scripts',			[$this,'gf_blocks_for_bandcamp__stylesheets'] );
		add_action('enqueue_block_editor_assets',	[$this,'gf_blocks_for_bandcamp__stylesheets'] );
		add_filter('block_categories_all',			[$this,'gf_blocks_for_bandcamp__custom_block_category'] );
		add_filter('wp_enqueue_scripts',			[$this,'gf_blocks_for_bandcamp__frontend_scripts'] );
	}
	///////////////////////////////////////////////////////////////////////////
	public function gf_blocks_for_bandcamp__load_blocks()
	{
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-album' );
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-embed' );
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-form' );
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-merch' );
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-miniplayer' );
	}
	///////////////////////////////////////////////////////////////////////////
	public function gf_blocks_for_bandcamp__include_functions()
	{ 
		include(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH.'/src/functions.php');
	}
	///////////////////////////////////////////////////////////////////////////
	public function gf_blocks_for_bandcamp__custom_block_category( $categories ) {
		$categories[] = array(
			'slug'  => 'blocks-for-bandcamp',
			'title' => __( 'Bandcamp', 'blocks-for-bandcamp' ),
			'icon'  => 'tree',
		);
		return $categories;
	}
	///////////////////////////////////////////////////////////////////////////
	public function gf_blocks_for_bandcamp__stylesheets()
	{

		if (is_admin()) {
			wp_register_style(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-stylesheet', GF_BLOCKS_FOR_BANDCAMP_PLUGIN_URL.'/assets/css/css.css','',GF_BLOCKS_FOR_BANDCAMP_PLUGIN_VERSION,'');
			wp_enqueue_style(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-stylesheet');
		}
		
		if ( !is_admin() &&
			(
			has_block('blocks-for-bandcamp/bandcamp-album',get_the_ID()) || 
			has_block('blocks-for-bandcamp/bandcamp-embed',get_the_ID()) ||
			has_block('blocks-for-bandcamp/bandcamp-form',get_the_ID()) ||
			has_block('blocks-for-bandcamp/bandcamp-merch',get_the_ID()) ||
			has_block('blocks-for-bandcamp/bandcamp-miniplayer',get_the_ID())
			)
		) {
			wp_register_style(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-stylesheet', GF_BLOCKS_FOR_BANDCAMP_PLUGIN_URL.'/assets/css/css.css','',GF_BLOCKS_FOR_BANDCAMP_PLUGIN_VERSION,'');
			wp_enqueue_style(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-stylesheet');
		}
	}
	///////////////////////////////////////////////////////////////////////////
	public function gf_blocks_for_bandcamp__frontend_scripts()
	{
		if ( has_block('blocks-for-bandcamp/bandcamp-album',get_the_ID()) || has_block('blocks-for-bandcamp/bandcamp-merch',get_the_ID()) ) {
			wp_register_script(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-script', GF_BLOCKS_FOR_BANDCAMP_PLUGIN_URL.'/assets/js/js.js', '', GF_BLOCKS_FOR_BANDCAMP_PLUGIN_VERSION, true);
			wp_enqueue_script(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-script');
		}
	}
	///////////////////////////////////////////////////////////////////////////
	public function gf_blocks_for_bandcamp__required_notice()
	{
		if (!function_exists('register_block_type_from_metadata')) {
			function gf_blocks_for_bandcamp__notice() {
				echo '<div class="notice notice-error"><p>Notice: Blocks for Bandcamp requires Gutenberg to be active. Plugin has been deactivated.</p></div>';
			}
			deactivate_plugins(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_BASENAME);
			add_action( 'admin_notices', 'gf_blocks_for_bandcamp__notice' );
			return;
		}
	}
	///////////////////////////////////////////////////////////////////////////

}