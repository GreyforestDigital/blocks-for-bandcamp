<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class BlocksForBandcamp_Cache {

	private $logger;
	private $plugin;

	///////////////////////////////////////////////////////////////////////////
	public function __construct($plugin) {

		$this->plugin = $plugin;
		$this->logger = $plugin->class_logging;

		add_action( 'admin_notices', 	[ $this, 'show_clear_cache_notice' ] );
		add_action( 'admin_post_'.$this->plugin->plugin_data['key'].'_clear_cache', [ $this, 'handle_clear_cache' ] );

	}

	///////////////////////////////////////////////////////////////////////////
	/*
	* Get JSON array of current transients for table
	*/
	public function get_transients_list( $search = '' ) {
		global $wpdb;

		$prefix         = '_transient_';
		$timeout_prefix = '_transient_timeout_';

		// Build SQL depending on whether we’re filtering
		if ( ! empty( $search ) ) {
			$query = $wpdb->prepare("
				SELECT option_name, option_value
				FROM $wpdb->options
				WHERE option_name LIKE %s
				OR option_name LIKE %s
				ORDER BY option_name ASC
			",
			"%{$prefix}{$search}%",
			"%{$timeout_prefix}{$search}%"
			);
		} else {
			$query = $wpdb->prepare("
				SELECT option_name, option_value
				FROM $wpdb->options
				WHERE option_name LIKE %s
				OR option_name LIKE %s
				ORDER BY option_name ASC
			",
			'_transient_%',
			'_transient_timeout_%'
			);
		}

		$results = $wpdb->get_results( $query );
		$transients = [];

		foreach ( $results as $row ) {

			// If it’s a timeout row
			if ( str_starts_with( $row->option_name, $timeout_prefix ) ) {
				$key = str_replace( $timeout_prefix, '', $row->option_name );
				$expires = (int) $row->option_value;

				if ( ! isset( $transients[ $key ] ) ) {
					$transients[ $key ] = [ 'key' => $key ];
				}

				$transients[ $key ]['expires'] = $expires ? wp_date( 'Y-m-d H:i:s', $expires ) : 'never';
			}

			// If it’s the actual transient value
			elseif ( str_starts_with( $row->option_name, $prefix ) ) {
				$key = str_replace( $prefix, '', $row->option_name );
				$searchkey = str_replace( $prefix.$search.'_', '', $row->option_name );
				$value = maybe_unserialize( $row->option_value );

				if ( ! isset( $transients[ $key ] ) ) {
					$transients[ $key ] = [ 'key' => $key ];
				}

				$transients[ $key ]['endpoint'] = explode('_',$searchkey,3)[1];
				$transients[ $key ]['id']       = explode('_',$searchkey,3)[2];

				// Calculate approximate size
				$raw_value  = maybe_serialize( $row->option_value );
				$size_bytes = strlen( $raw_value );

				$transients[ $key ]['size_bytes'] = $size_bytes;
				$transients[ $key ]['size_human'] = size_format( $size_bytes );
			}
		}

		return wp_json_encode( array_values( $transients ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	///////////////////////////////////////////////////////////////////////////
    /**
     * Handle "Clear Cache" button submission
     */
	public function handle_clear_cache() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized user' );
		}

		check_admin_referer( 'gf_clear_cache_action', 'gf_clear_cache_nonce' );

		global $wpdb;
		$like_1 = $wpdb->esc_like( '_transient_'.$this->plugin->plugin_data['key'] ) . '%';
		$like_2 = $wpdb->esc_like( '_transient_timeout_'.$this->plugin->plugin_data['key'] ) . '%';

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				WHERE option_name LIKE %s
				OR option_name LIKE %s",
				$like_1,
				$like_2
			)
		);

		wp_cache_flush();

		set_transient( 'gf_clear_cache_notice', true, 30 );

		$this->logger->log('info','Transient cache manually cleared','Function: handle_clear_cache()');

		wp_safe_redirect( admin_url( 'options-general.php?page='.$this->plugin->plugin_data['key'] ) );
		exit;
	}

	///////////////////////////////////////////////////////////////////////////
    /**
     * Show registered settings errors if any
     */
	public function show_clear_cache_notice() {
		if ( get_transient( 'gf_clear_cache_notice' ) ) {
			delete_transient( 'gf_clear_cache_notice' );
			wp_admin_notice('Cache cleared successfully!',['type'=>'success','dismissible'=>true]);
		}
	}

}
