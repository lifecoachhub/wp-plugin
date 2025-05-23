<?php
/**
 * Plugin Name:       Life Coach Hub
 * Description:       Coaching and mentoring platform for coaches and clients.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lifecoachhub-app
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
