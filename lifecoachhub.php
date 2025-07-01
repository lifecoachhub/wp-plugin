<?php
/**
 * Plugin Name:       Life Coach Hub
 * Description:       Coaching and mentoring platform for coaches and clients.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4.0
 * Tested up to:      6.8
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lifecoachhub
 *
 * @package LifeCoachHub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'LIFECOACHHUB_PLUGIN_FILE', __FILE__ );
define( 'LIFECOACHHUB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LIFECOACHHUB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Define app URLs - change these based on environment
define( 'LIFECOACHHUB_DEV_URL', 'http://localhost:8000' );
define( 'LIFECOACHHUB_PROD_URL', 'https://app.lifecoachhub.com' );

// Define current app URL - switch between development and production
// For development, use LIFECOACHHUB_DEV_URL
// For production, use LIFECOACHHUB_PROD_URL
define( 'LIFECOACHHUB_APP_URL', LIFECOACHHUB_PROD_URL ); // LIFECOACHHUB_PROD_URL or LIFECOACHHUB_DEV_URL

/**
 * Get the Life Coach Hub app URL
 * 
 * @param string $path Optional path to append to the base URL
 * @return string The complete app URL
 */
function lifecoachhub_get_app_url( $path = '' ) {
	$base_url = LIFECOACHHUB_APP_URL;
	
	if ( ! empty( $path ) ) {
		$base_url = trailingslashit( $base_url ) . ltrim( $path, '/' );
	}
	
	return $base_url;
}

/**
 * Check if we're in development mode
 * 
 * @return bool True if in development mode
 */
function lifecoachhub_is_dev_mode() {
	return LIFECOACHHUB_APP_URL === LIFECOACHHUB_DEV_URL;
}

/**
 * Get both dev and prod URLs for CSP headers
 * 
 * @return string Space-separated URLs for CSP
 */
function lifecoachhub_get_csp_urls() {
	return LIFECOACHHUB_DEV_URL . ' ' . LIFECOACHHUB_PROD_URL;
}

/**
 * Main LifeCoachHub Plugin Class
 */
class LifeCoachHub {

	/**
	 * Instance of this class
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class
	 *
	 * @return object A single instance of this class
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		// Include admin class
		require_once LIFECOACHHUB_PLUGIN_DIR . 'includes/admin/class-lifecoachhub-admin.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Register blocks
		add_action( 'init', array( $this, 'register_blocks' ) );
		
		// Register external connector script
		add_action( 'admin_enqueue_scripts', array( $this, 'register_external_connector' ) );
		
		// Add security headers
		add_action( 'admin_init', array( $this, 'add_security_headers' ) );
	}
	
	/**
	 * Register external connector script
	 */
	public function register_external_connector( $hook ) {
		if ( 'toplevel_page_lifecoachhub' !== $hook ) {
			return;
		}
		
		wp_enqueue_script(
			'lifecoachhub-external-connector',
			LIFECOACHHUB_PLUGIN_URL . 'assets/js/external-connector.js',
			array(),
			'1.0.0',
			true
		);
	}
	
	/**
	 * Add security headers
	 */
	public function add_security_headers() {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_lifecoachhub' !== $screen->id ) {
			return;
		}
		
		// Allow iframe embedding from the external app domain
		header('Content-Security-Policy: frame-ancestors \'self\' ' . lifecoachhub_get_csp_urls());
	}

	/**
	 * Register blocks
	 */
	public function register_blocks() {
		/**
		 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
		 * based on the registered block metadata.
		 */
		if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
			wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
			return;
		}

		if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
			wp_register_block_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
		}

		$manifest_data = require __DIR__ . '/build/blocks-manifest.php';
		foreach ( array_keys( $manifest_data ) as $block_type ) {
			register_block_type( __DIR__ . "/build/{$block_type}" );
		}
	}
}

// Initialize the plugin
function lifecoachhub() {
	return LifeCoachHub::get_instance();
}

// Start the plugin
lifecoachhub();

// Initialize admin
if ( is_admin() ) {
	new LifeCoachHub_Admin();
}
