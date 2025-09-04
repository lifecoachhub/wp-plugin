<?php
/**
 * Admin functionality for LifeCoachHub plugin
 *
 * @package LifeCoachHub\Admin
 */

namespace LifeCoachHub\Admin;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Admin class for LifeCoachHub
 */
class LifeCoachHub_Admin
{

	/**
	 * App URL
	 *
	 * @var string
	 */
	private $app_url;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Initialize app URL from constant
		$this->app_url = LIFECOACHHUB_APP_URL;

		// Add menu
		add_action('admin_menu', array($this, 'add_admin_menu'));

		add_action(
			'in_admin_header',
			function () {
				if (isset($_GET['page']) && 'lifecoachhub' === sanitize_text_field($_GET['page'])) { // phpcs:ignore
					remove_all_actions('admin_notices');
					remove_all_actions('all_admin_notices');
				}
			},
			999
		);

		// Enqueue admin scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

		// Ajax handlers for proxy
		add_action('wp_ajax_lifecoachhub_proxy', array($this, 'handle_proxy_request'));

		// Handle API key callback and disconnect - moved to admin_init for early processing
		add_action('admin_init', array($this, 'handle_api_callback'));
		add_action('admin_init', array($this, 'handle_disconnect'));

		// Add admin notices
		add_action('admin_notices', array($this, 'admin_notices'));
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu()
	{
		// Create custom menu icon using SVG data URI
		$menu_icon = 'data:image/svg+xml;base64,' . base64_encode('<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_6005_74418)"><path d="M43.0953 12.25H41.6266V25.4688C41.6243 27.4157 40.8498 29.2823 39.4731 30.659C38.0964 32.0357 36.2298 32.8102 34.2828 32.8125H22.925L18.0781 37.2188H29.6181L40.434 45.8712C40.542 45.9575 40.6723 46.0115 40.8097 46.027C40.9472 46.0426 41.0862 46.019 41.2108 45.959C41.3354 45.899 41.4406 45.805 41.5141 45.6878C41.5877 45.5707 41.6267 45.4352 41.6266 45.2969V37.2188H43.0953C44.264 37.2188 45.3847 36.7545 46.211 35.9282C47.0374 35.1019 47.5016 33.9811 47.5016 32.8125V16.6562C47.5016 15.4876 47.0374 14.3669 46.211 13.5406C45.3847 12.7142 44.264 12.25 43.0953 12.25Z" fill="#65C467"/><path d="M34.2812 1.96875H4.90625C3.73764 1.96875 2.61689 2.43298 1.79056 3.25931C0.964229 4.08564 0.5 5.20639 0.5 6.375L0.5 25.4688C0.5 26.6374 0.964229 27.7581 1.79056 28.5844C2.61689 29.4108 3.73764 29.875 4.90625 29.875H7.84375V40.8906C7.84383 41.0328 7.88518 41.1719 7.96278 41.291C8.04038 41.4102 8.1509 41.5042 8.28091 41.5618C8.41093 41.6193 8.55485 41.6379 8.69521 41.6152C8.83557 41.5925 8.96633 41.5296 9.07162 41.4341L21.7881 29.875H34.2812C35.4499 29.875 36.5706 29.4108 37.3969 28.5844C38.2233 27.7581 38.6875 26.6374 38.6875 25.4688V6.375C38.6875 5.20639 38.2233 4.08564 37.3969 3.25931C36.5706 2.43298 35.4499 1.96875 34.2812 1.96875Z" fill="#0C8CE9"/></g><defs><clipPath id="clip0_6005_74418"><rect width="47" height="47" fill="white" transform="translate(0.5 0.5)"/></clipPath></defs></svg>');

		// Main app page
		add_menu_page(
			__('Life Coach Hub', 'lifecoachhub'),
			__('Life Coach Hub', 'lifecoachhub'),
			'manage_options',
			'lifecoachhub',
			array($this, 'render_admin_page'),
			$menu_icon,
			30
		);

		// Settings page
		add_submenu_page(
			'lifecoachhub',
			__('Settings', 'lifecoachhub'),
			__('Settings', 'lifecoachhub'),
			'manage_options',
			'lifecoachhub-settings',
			array($this, 'render_settings_page')
		);
	}

	/**
	 * Handle API key callback from external app
	 */
	public function handle_api_callback()
	{
		// Only handle on our admin page
		if (!isset($_GET['page']) || 'lifecoachhub' !== $_GET['page']) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Check if we have API key and connection status
		if (isset($_GET['lifecoach_api_key']) && isset($_GET['connection_status'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$api_key = sanitize_text_field(wp_unslash($_GET['lifecoach_api_key'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$connection_status = sanitize_text_field(wp_unslash($_GET['connection_status'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Store API key and connection status
			update_option('lifecoachhub_api_key', $api_key);
			update_option('lifecoachhub_connection_status', $connection_status);
			update_option('lifecoachhub_connected_at', current_time('mysql'));

			// Redirect to clean URL
			$redirect_url = admin_url('admin.php?page=lifecoachhub');
			if ('success' === $connection_status) {
				$redirect_url = add_query_arg('connected', '1', $redirect_url);
			}

			wp_safe_redirect($redirect_url);
			exit;
		}
	}

	/**
	 * Handle disconnect action
	 */
	public function handle_disconnect()
	{
		// Only handle on settings page
		if (!isset($_GET['page']) || 'lifecoachhub-settings' !== $_GET['page']) {
			return;
		}

		// Check if disconnect button was clicked
		if (!isset($_POST['lifecoachhub_disconnect'])) {
			return;
		}

		// Verify nonce
		if (
			!isset($_POST['lifecoachhub_settings_nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lifecoachhub_settings_nonce'])), 'lifecoachhub_save_settings')
		) {
			add_settings_error('lifecoachhub_settings', 'invalid_nonce', __('Security verification failed. Please try again.', 'lifecoachhub'), 'error');
			return;
		}

		// Generate disconnect URL
		$external_app_url = LIFECOACHHUB_APP_URL . '/external/connection/disconnect?redirect_url=' . urlencode(admin_url('admin.php?page=lifecoachhub-settings&disconnected=1')) . '&api_key=' . get_option('lifecoachhub_api_key');

		// Delete all connection related options
		delete_option('lifecoachhub_api_key');
		delete_option('lifecoachhub_connection_status');
		delete_option('lifecoachhub_connected_at');

		// Redirect to external app for disconnection.
		wp_redirect($external_app_url);
		exit;
	}

	/**
	 * Show admin notices
	 */
	public function admin_notices()
	{
		$screen = get_current_screen();
		if (!$screen || 'toplevel_page_lifecoachhub' !== $screen->id) {
			return;
		}

		// Check if connected - if so, don't show any notices to keep page clean
		$api_key = get_option('lifecoachhub_api_key');
		$connection_status = get_option('lifecoachhub_connection_status');
		$is_connected = $api_key && 'success' === $connection_status;

		if ($is_connected) {
			return; // Don't show any notices when connected
		}

		// Show connection success message (safe to read GET param for display)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (isset($_GET['connected']) && '1' === $_GET['connected']) {
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p>' . esc_html__('Successfully connected to Life Coach Hub!', 'lifecoachhub') . '</p>';
			echo '</div>';
		}

		// Show connection status for non-connected states
		if ($connection_status && $api_key) {
			if ('success' !== $connection_status) {
				echo '<div class="notice notice-error">';
				echo '<p>' . esc_html__('Connection to Life Coach Hub failed. Please try again.', 'lifecoachhub') . '</p>';
				echo '</div>';
			}
		}
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function admin_scripts($hook)
	{
		// Load on both main plugin page and settings page.
		if ('toplevel_page_lifecoachhub' !== $hook && 'life-coach-hub_page_lifecoachhub-settings' !== $hook) {
			return;
		}

		// Get connection status and API key.
		$api_key = get_option('lifecoachhub_api_key');
		$connection_status = get_option('lifecoachhub_connection_status');
		$is_connected = $api_key && 'success' === $connection_status;

		// Enqueue admin styles
		wp_enqueue_style(
			'lifecoachhub-admin',
			LIFECOACHHUB_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			'1.0.0'
		);

		// Enqueue admin page specific styles.
		if ('toplevel_page_lifecoachhub' === $hook) {
			// Add body class for conditional styling when not connected.
			if (!$is_connected) {
				add_filter('admin_body_class', function($classes) {
					return $classes . ' lifecoachhub-admin-not-connected';
				});

				wp_enqueue_style(
					'lifecoachhub-admin-page',
					LIFECOACHHUB_PLUGIN_URL . 'assets/css/admin-not-connected.css',
					array(),
					'1.0.0'
				);
			}
		}

		// Enqueue general admin script.
		wp_enqueue_script(
			'lifecoachhub-admin-js',
			LIFECOACHHUB_PLUGIN_URL . 'assets/js/admin.js',
			array('jquery'),
			'1.0.0',
			true
		);

		// Only enqueue iframe script if connected and on main page.
		if ($is_connected && 'toplevel_page_lifecoachhub' === $hook) {
			wp_enqueue_script(
				'lifecoachhub-admin-iframe',
				LIFECOACHHUB_PLUGIN_URL . 'assets/js/admin-iframe.js',
				array(),
				'1.0.0',
				true
			);

			// Pass configuration data to iframe script.
			wp_localize_script(
				'lifecoachhub-admin-iframe',
				'lifecoachHubConfig',
				array(
					'baseUrl' => LIFECOACHHUB_APP_URL,
					'apiKey' => $api_key,
					'adminPageUrl' => admin_url('admin.php?page=lifecoachhub'),
					'connectorScriptUrl' => LIFECOACHHUB_PLUGIN_URL . 'assets/js/external-connector.js'
				)
			);
		}

		// Get stored API key for general admin script.
		$app_url = $this->app_url . '/launchpad';

		// Add API key and source parameter to app URL if available.
		if ($api_key) {
			$app_url = add_query_arg(array(
				'api_key' => $api_key,
				'source' => 'external_connection',
				'embedded' => '1'
			), $app_url);
		}

		// Localize general admin script.
		wp_localize_script(
			'lifecoachhub-admin-js',
			'lifecoachhubData',
			array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('lifecoachhub_proxy_nonce'),
				'appUrl' => $app_url,
				'apiKey' => $api_key,
				'baseAppUrl' => $this->app_url,
			)
		);
	}

	/**
	 * Handle proxy requests to the app
	 */
	public function handle_proxy_request()
	{
		// Check nonce for security
		if (!isset($_REQUEST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['nonce'])), 'lifecoachhub_proxy_nonce')) {
			wp_send_json_error(array('message' => 'Invalid security token'), 403);
		}

		// Make sure user has permission
		if (!current_user_can('manage_options')) {
			wp_send_json_error(array('message' => 'Permission denied'), 403);
		}

		// Get target URL
		$target_path = isset($_REQUEST['path']) ? sanitize_text_field(wp_unslash($_REQUEST['path'])) : '';
		$url = trailingslashit($this->app_url) . ltrim($target_path, '/');

		// Set up the request
		$args = array(
			'timeout' => 60,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
			'headers' => array(
				'Referer' => home_url(),
				'Origin' => home_url(),
			),
		);

		// Add API key to headers if available
		$api_key = get_option('lifecoachhub_api_key');
		if ($api_key) {
			$args['headers']['Authorization'] = 'Bearer ' . $api_key;
			$args['headers']['X-API-Key'] = $api_key;
		}

		// Get method and add body if needed
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))) : 'GET';
		$args['method'] = $method;

		if ('POST' === $method && isset($_POST) && !empty($_POST)) {
			$args['body'] = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		// Make the request
		$response = wp_remote_request($url, $args);

		// Check for errors
		if (is_wp_error($response)) {
			wp_send_json_error(array('message' => $response->get_error_message()), 500);
		}

		// Get the response body
		$body = wp_remote_retrieve_body($response);
		$status = wp_remote_retrieve_response_code($response);
		$headers = wp_remote_retrieve_headers($response);

		// Return JSON response with the content
		wp_send_json(
			array(
				'body' => $body,
				'status' => $status,
				'headers' => $headers,
			),
			$status
		);
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page()
	{
		// Security check
		if (!current_user_can('manage_options')) {
			return;
		}

		include_once LIFECOACHHUB_PLUGIN_DIR . 'includes/Admin/views/html-admin-page.php';
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page()
	{
		// Security check
		if (!current_user_can('manage_options')) {
			return;
		}

		// Handle form submission
		$this->handle_settings_form();

		// Load settings page template
		include_once LIFECOACHHUB_PLUGIN_DIR . 'includes/Admin/views/html-settings-page.php';
	}

	/**
	 * Handle settings form submission
	 */
	private function handle_settings_form()
	{
		// Check if form was submitted
		if (!isset($_POST['lifecoachhub_settings_submit'])) {
			return;
		}

		// Verify nonce
		if (
			!isset($_POST['lifecoachhub_settings_nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lifecoachhub_settings_nonce'])), 'lifecoachhub_save_settings')
		) {
			add_settings_error('lifecoachhub_settings', 'invalid_nonce', __('Security verification failed. Please try again.', 'lifecoachhub'), 'error');
			return;
		}

		// Get API key from form
		$api_key = isset($_POST['lifecoachhub_api_key']) ? sanitize_text_field(wp_unslash($_POST['lifecoachhub_api_key'])) : '';

		// Save settings
		update_option('lifecoachhub_api_key', $api_key);

		// If API key was provided, update connection status
		if (!empty($api_key)) {
			update_option('lifecoachhub_connection_status', 'manual');
			update_option('lifecoachhub_connected_at', current_time('mysql'));
		} else {
			// If API key was removed, reset connection status
			delete_option('lifecoachhub_connection_status');
			delete_option('lifecoachhub_connected_at');
		}

		add_settings_error('lifecoachhub_settings', 'settings_updated', __('Settings saved successfully.', 'lifecoachhub'), 'success');
	}
}
