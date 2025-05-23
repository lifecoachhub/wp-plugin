<?php
/**
 * LifeCoachHub Admin Class
 *
 * @package LifeCoachHub
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * LifeCoachHub_Admin Class
 */
class LifeCoachHub_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add menu
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		
		// Enqueue admin scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		
		// Add redirect endpoint
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_action( 'parse_request', array( $this, 'handle_endpoint' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'LifeCoach Hub', 'lifecoachhub-app' ),
			__( 'LifeCoach Hub', 'lifecoachhub-app' ),
			'manage_options',
			'lifecoachhub',
			array( $this, 'render_admin_page' ),
			'dashicons-groups',
			30
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function admin_scripts( $hook ) {
		if ( 'toplevel_page_lifecoachhub' !== $hook ) {
			return;
		}

		wp_enqueue_style( 
			'lifecoachhub-admin', 
			LIFECOACHHUB_PLUGIN_URL . 'assets/css/admin.css', 
			array(), 
			'1.0.0' 
		);
	}

	/**
	 * Add custom endpoint for redirection
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( 'lifecoachhub-app', EP_ROOT );
		
		// This helps to ensure our endpoint works
		if ( ! get_option( 'lifecoachhub_flush_rewrite' ) ) {
			flush_rewrite_rules();
			update_option( 'lifecoachhub_flush_rewrite', true );
		}
	}

	/**
	 * Handle the endpoint and redirect to the app
	 */
	public function handle_endpoint( $wp ) {
		if ( isset( $wp->query_vars['lifecoachhub-app'] ) ) {
			// Add proper security checks here
			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Unauthorized access', 'Unauthorized', array( 'response' => 401 ) );
			}
			
			// Redirect to the external application
			wp_redirect( 'https://app.lifecoachhub.com/' );
			exit;
		}
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		// Security check
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		include_once LIFECOACHHUB_PLUGIN_DIR . 'includes/admin/views/html-admin-page.php';
	}
}
