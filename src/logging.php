<?php
defined( 'ABSPATH' ) || exit;

class BlocksForBandcamp_Logging {

	private $log_dir;
	private $log_file;
	private $message = false;
	private $args = false;
	private $filesystem;
	private $plugin_slug;

	///////////////////////////////////////////////////////////////////////////
	public function __construct() {

		$this->plugin_slug = 'gf_blocks_for_bandcamp';
		$this->initialize_filesystem();
		$this->initialize_log_storage();

		add_action( 'admin_init', [ $this, 'handle_clear_log' ] );

	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Initialize the WP_Filesystem API and set to class variable.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function initialize_filesystem() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		global $wp_filesystem;
		$this->filesystem = $wp_filesystem;
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Set log directory and file path, create if they don't exist.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function initialize_log_storage() {
		$upload_dir = wp_upload_dir();
		$this->log_dir = trailingslashit( $upload_dir['basedir'] ) . 'plugin-logs/';
		$this->log_file = $this->log_dir . $this->plugin_slug . '.json';

		if ( ! $this->filesystem->is_dir( $this->log_dir ) ) {
			$this->filesystem->mkdir( $this->log_dir );
		}

		if ( ! $this->filesystem->exists( $this->log_file ) ) {
			$empty_log = json_encode( [], JSON_PRETTY_PRINT );
			$this->filesystem->put_contents( $this->log_file, $empty_log, FS_CHMOD_FILE );
		}
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Log an entry to the JSON log file.
	 *
	 * @since 1.0.0
	 * @param string $level   Logging level (e.g. info, warning, error).
	 * @param string $message Message text to log.
	 * @param array  $context Optional additional context (defaults to empty array).
	 * @return bool True on success, false on failure.
	 */
	public function log( $level, $message, $context = [] ) {
		$log_entry = [
			'timestamp' => time(),
			'level'     => $level,
			'message'   => $message,
			'context'   => $context,
		];

		$current_logs = [];

		if ( $this->filesystem->exists( $this->log_file ) ) {
			$raw = $this->filesystem->get_contents( $this->log_file );
			$current_logs = json_decode( $raw, true ) ?: [];
		}

		$current_logs[] = $log_entry;
		$new_contents   = json_encode( $current_logs, JSON_PRETTY_PRINT );

		return $this->filesystem->put_contents( $this->log_file, $new_contents, FS_CHMOD_FILE ) !== false;
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Clear the contents of the log file by overwriting it with an empty array.
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function clear_log() {
		$empty_log = json_encode( [], JSON_PRETTY_PRINT );
		return $this->filesystem->put_contents( $this->log_file, $empty_log, FS_CHMOD_FILE ) !== false;
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Handle admin POST request to clear logs.
	 * Uses nonce verification and updates status message.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_clear_log() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized user' );
		}

		if (
			isset( $_POST['gf_clear_logs'] ) &&
			$_POST['gf_clear_logs'] == $this->plugin_slug &&
			check_admin_referer( 'gf_clear_logs_action', 'gf_clear_logs_nonce' )
		) {
			$clear = $this->clear_log();
			if ( $clear ) {
				$this->message = 'Plugin log successfully cleared.';
				$this->args    = [ 'type' => 'success', 'dismissible' => true ];
			} else {
				$this->message = 'Plugin log not successfully cleared.';
				$this->args    = [ 'type' => 'error', 'dismissible' => true ];
			}
			$this->display_admin_notices();
		}
	}

	///////////////////////////////////////////////////////////////////////////
	/**
	 * Display an admin notice if log clear operation occurred.
	 * Ensures this only runs once during page load.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_admin_notices() {
		static $hasRun = false;

		if ( $hasRun ) {
			return;
		}
		$hasRun = true;

		if ( $this->message && $this->args ) {
			wp_admin_notice( $this->message, $this->args );
		}
	}

}