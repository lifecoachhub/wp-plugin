<?php
/**
 * Plugin Name:       Life Coach Hub
 * Description:       Coaching and mentoring platform for coaches and clients.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4.0
 * Tested up to:      6.8
 * Author:            Life Coach Hub
 * Author URI:        https://lifecoachhub.com
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
if ( ! defined( 'LIFECOACHHUB_APP_URL' ) ) {
    define( 'LIFECOACHHUB_APP_URL', LIFECOACHHUB_PROD_URL ); // LIFECOACHHUB_PROD_URL or LIFECOACHHUB_DEV_URL
}

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
 * Check if Composer autoloader exists and load it
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Show notice when Composer autoloader is missing
 */
function lifecoachhub_missing_autoloader_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    echo '<div class="notice notice-error">';
    echo '<p><strong>Life Coach Hub:</strong> ' . esc_html__( 'Composer autoloader not found. Please run "composer install" or contact your administrator.', 'lifecoachhub' ) . '</p>';
    echo '</div>';
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
        $this->init_hooks();
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

        // Add plugin action links
        add_filter( 'plugin_action_links_' . plugin_basename( LIFECOACHHUB_PLUGIN_FILE ), array( $this, 'add_plugin_action_links' ) );

        // Initialize admin if in admin area and dependencies are met
        if ( is_admin() && lifecoachhub_dependencies_met() ) {
            lifecoachhub_safe_instantiate( 'LifeCoachHub\\Admin\\LifeCoachHub_Admin' );
        }
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
        // Check if blocks manifest exists before trying to register
        $manifest_file = __DIR__ . '/build/blocks-manifest.php';
        if ( ! file_exists( $manifest_file ) ) {
            return;
        }

        /**
         * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
         * based on the registered block metadata.
         */
        if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
            wp_register_block_types_from_metadata_collection( __DIR__ . '/build', $manifest_file );
            return;
        }

        if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
            wp_register_block_metadata_collection( __DIR__ . '/build', $manifest_file );
        }

        $manifest_data = require $manifest_file;
        foreach ( array_keys( $manifest_data ) as $block_type ) {
            $block_path = __DIR__ . "/build/{$block_type}";
            if ( file_exists( $block_path ) ) {
                register_block_type( $block_path );
            }
        }
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing plugin action links
     * @return array Modified plugin action links
     */
    public function add_plugin_action_links( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=lifecoachhub-settings' ) ),
            esc_html__( 'Settings', 'lifecoachhub' )
        );

        // Add Dashboard link.
        $dashboard_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=lifecoachhub' ) ),
            esc_html__( 'Dashboard', 'lifecoachhub' )
        );

        array_unshift( $links, $dashboard_link );
        array_unshift( $links, $settings_link );

        return $links;
    }
}

// Initialize the plugin
function lifecoachhub() {
    return LifeCoachHub::get_instance();
}

// Start the plugin
lifecoachhub();
