<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BlocksForBandcamp_init {

	public $class_admin;
	public $class_api;
	public $class_cache;
	public $class_logging;
	public $plugin_data = [
		'key'=>'gf_blocks_for_bandcamp',
		'slug'=>'blocks-for-bandcamp',
		'title'=>'Blocks for Bandcamp',
		'classes'=>'BlocksForBandcamp'
	];

	///////////////////////////////////////////////////////////////////////////
	function __construct()
	{
		add_action('init',							[$this,'required_notice'] );
		add_action('init',							[$this,'load_blocks'] );
		add_action('wp_enqueue_scripts',			[$this,'stylesheets'] );
		add_action('admin_enqueue_scripts',			[$this,'stylesheets'] );
		add_action('enqueue_block_editor_assets',	[$this,'stylesheets'] );
		add_filter('block_categories_all',			[$this,'custom_block_category'] );
		add_filter('wp_enqueue_scripts',			[$this,'frontend_scripts'] );
		add_filter('plugin_action_links_'.GF_BLOCKS_FOR_BANDCAMP_PLUGIN_BASENAME, [$this,'plugin_links'] );

		$this->load_classes();
		$this->class_logging = new BlocksForBandcamp_Logging($this);
		$this->class_api 	 = new BlocksForBandcamp_API($this);
		$this->class_cache 	 = new BlocksForBandcamp_Cache($this);
		$this->class_admin 	 = new BlocksForBandcamp_Admin($this);

	}
	///////////////////////////////////////////////////////////////////////////
	public function load_blocks()
	{
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-album' );
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-discography' );
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-embed' );
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-form' );
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-merch' );
		register_block_type_from_metadata( GF_BLOCKS_FOR_BANDCAMP_PLUGIN_PATH . '/blocks/bandcamp-miniplayer' );
	}
	///////////////////////////////////////////////////////////////////////////
	public function load_classes()
	{ 
		include(__DIR__.'/logging.php');
		include(__DIR__.'/admin.php');
		include(__DIR__.'/api.php');
		include(__DIR__.'/cache.php');
		include(__DIR__.'/functions.php');
	}
	///////////////////////////////////////////////////////////////////////////
	public function custom_block_category( $categories ) {
		$categories[] = array(
			'slug'  => 'blocks-for-bandcamp',
			'title' => __( 'Bandcamp', 'blocks-for-bandcamp' ),
			'icon'  => 'tree',
		);
		return $categories;
	}
	///////////////////////////////////////////////////////////////////////////
	public function plugin_links($links)
	{ 
		$settings_link = '<a aria-label="View Settings" href="options-general.php?page='.$this->plugin_data['key'].'">Settings</a>'; 
		array_unshift($links, $settings_link);
		return $links; 
	}
	///////////////////////////////////////////////////////////////////////////
	public function stylesheets()
	{

		if (is_admin()) {
			wp_register_style(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-stylesheet', GF_BLOCKS_FOR_BANDCAMP_PLUGIN_URL.'/assets/css/css.css','',GF_BLOCKS_FOR_BANDCAMP_PLUGIN_VERSION,'');
			wp_enqueue_style(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-stylesheet');
		}
		
		if ( !is_admin() &&
			(
			has_block('blocks-for-bandcamp/bandcamp-album',get_the_ID()) || 
			has_block('blocks-for-bandcamp/bandcamp-discography',get_the_ID()) || 
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
	public function frontend_scripts()
	{
		if ( 
			has_block('blocks-for-bandcamp/bandcamp-album',get_the_ID()) || 
			has_block('blocks-for-bandcamp/bandcamp-merch',get_the_ID()) 
		) {
			wp_register_script(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-script', GF_BLOCKS_FOR_BANDCAMP_PLUGIN_URL.'/assets/js/js.js', '', GF_BLOCKS_FOR_BANDCAMP_PLUGIN_VERSION, true);
			wp_enqueue_script(GF_BLOCKS_FOR_BANDCAMP_PLUGIN_SLUG.'-script');
		}
	}
	///////////////////////////////////////////////////////////////////////////
	public function required_notice()
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