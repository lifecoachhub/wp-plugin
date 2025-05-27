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
	 * App URL
	 *
	 * @var string
	 */
	private $app_url = 'http://localhost:8000/launchpad'; // 'https://app.lifecoachhub.com/';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add menu
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		
		// Enqueue admin scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		
		// Ajax handlers for proxy
		add_action( 'wp_ajax_lifecoachhub_proxy', array( $this, 'handle_proxy_request' ) );
		
		// Handle API key callback
		add_action( 'admin_init', array( $this, 'handle_api_callback' ) );
		
		// Add admin notices
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
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
	 * Handle API key callback from external app
	 */
	public function handle_api_callback() {
		// Only handle on our admin page
		if ( ! isset( $_GET['page'] ) || 'lifecoachhub' !== $_GET['page'] ) {
			return;
		}
		
		// Check if we have API key and connection status
		if ( isset( $_GET['lifecoach_api_key'] ) && isset( $_GET['connection_status'] ) ) {
			$api_key = sanitize_text_field( wp_unslash( $_GET['lifecoach_api_key'] ) );
			$connection_status = sanitize_text_field( wp_unslash( $_GET['connection_status'] ) );
			
			// Store API key and connection status
			update_option( 'lifecoachhub_api_key', $api_key );
			update_option( 'lifecoachhub_connection_status', $connection_status );
			update_option( 'lifecoachhub_connected_at', current_time( 'mysql' ) );
			
			// Redirect to clean URL
			$redirect_url = admin_url( 'admin.php?page=lifecoachhub' );
			if ( 'success' === $connection_status ) {
				$redirect_url = add_query_arg( 'connected', '1', $redirect_url );
			}
			
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}
	
	/**
	 * Show admin notices
	 */
	public function admin_notices() {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_lifecoachhub' !== $screen->id ) {
			return;
		}
		
		// Check if connected - if so, don't show any notices to keep page clean
		$api_key = get_option( 'lifecoachhub_api_key' );
		$connection_status = get_option( 'lifecoachhub_connection_status' );
		$is_connected = $api_key && 'success' === $connection_status;
		
		if ( $is_connected ) {
			return; // Don't show any notices when connected
		}
		
		// Show connection success message
		if ( isset( $_GET['connected'] ) && '1' === $_GET['connected'] ) {
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p>' . esc_html__( 'Successfully connected to LifeCoach Hub!', 'lifecoachhub-app' ) . '</p>';
			echo '</div>';
		}
		
		// Show connection status for non-connected states
		if ( $connection_status && $api_key ) {
			if ( 'success' !== $connection_status ) {
				echo '<div class="notice notice-error">';
				echo '<p>' . esc_html__( 'Connection to LifeCoach Hub failed. Please try again.', 'lifecoachhub-app' ) . '</p>';
				echo '</div>';
			}
		}
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
		
		wp_enqueue_script(
			'lifecoachhub-admin-js',
			LIFECOACHHUB_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);
		
		// Get stored API key
		$api_key = get_option( 'lifecoachhub_api_key' );
		$app_url = $this->app_url;
		
		// Add API key and source parameter to app URL if available
		if ( $api_key ) {
			$app_url = add_query_arg( array(
				'api_key' => $api_key,
				'source'  => 'external_connection'
			), $app_url );
		}
		
		wp_localize_script(
			'lifecoachhub-admin-js',
			'lifecoachhubData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'lifecoachhub_proxy_nonce' ),
				'appUrl'  => $app_url,
				'apiKey'  => $api_key,
			)
		);
	}

	/**
	 * Handle proxy requests to the app
	 */
	public function handle_proxy_request() {
		// Check nonce for security
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'lifecoachhub_proxy_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid security token' ), 403 );
		}
		
		// Make sure user has permission
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
		}
		
		// Get target URL
		$target_path = isset( $_REQUEST['path'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['path'] ) ) : '';
		$url = trailingslashit( $this->app_url ) . ltrim( $target_path, '/' );
		
		// Set up the request
		$args = array(
			'timeout'     => 60,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent'  => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
			'headers'     => array(
				'Referer' => home_url(),
			),
		);
		
		// Add API key to headers if available
		$api_key = get_option( 'lifecoachhub_api_key' );
		if ( $api_key ) {
			$args['headers']['Authorization'] = 'Bearer ' . $api_key;
			$args['headers']['X-API-Key'] = $api_key;
		}
		
		// Get method and add body if needed
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		$args['method'] = $method;
		
		if ( 'POST' === $method && isset( $_POST ) && ! empty( $_POST ) ) {
			$args['body'] = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		
		// Make the request
		$response = wp_remote_request( $url, $args );
		
		// Check for errors
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ), 500 );
		}
		
		// Get the response body
		$body = wp_remote_retrieve_body( $response );
		$status = wp_remote_retrieve_response_code( $response );
		$headers = wp_remote_retrieve_headers( $response );
		
		// Return JSON response with the content
		wp_send_json(
			array(
				'body'    => $body,
				'status'  => $status,
				'headers' => $headers,
			),
			$status
		);
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
