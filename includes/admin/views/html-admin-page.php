<?php
/**
 * Admin Page View
 *
 * @package LifeCoachHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get connection status and API key
$api_key = get_option( 'lifecoachhub_api_key' );
$connection_status = get_option( 'lifecoachhub_connection_status' );
$connected_at = get_option( 'lifecoachhub_connected_at' );
$is_connected = $api_key && 'success' === $connection_status;

// Build app URL with API key and source parameter if available
$app_url = 'http://localhost:8000/launchpad'; // 'https://app.lifecoachhub.com/';
if ( $api_key ) {
	$app_url = add_query_arg( array(
		'api_key' => $api_key,
		'source'  => 'external_connection'
	), $app_url );
}

// If connected successfully, show only iframe
if ( $is_connected ) : ?>
	<div class="lifecoachhub-clean-iframe-container">
		<iframe 
			src="<?php echo esc_url( $app_url ); ?>" 
			id="lifecoachhub-clean-iframe"
			title="<?php esc_attr_e( 'LifeCoach Hub Application', 'lifecoachhub-app' ); ?>"
			class="lifecoachhub-clean-iframe"
			sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation"
		></iframe>
	</div>
<?php else : ?>
	<!-- Show setup interface if not connected -->
	<div class="wrap lifecoachhub-admin-wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<div class="lifecoachhub-app-container">
			<div class="lifecoachhub-tabs">
				<button class="lifecoachhub-tab active" data-tab="options">
					<?php esc_html_e( 'Access Options', 'lifecoachhub-app' ); ?>
				</button>
				<button class="lifecoachhub-tab" data-tab="app">
					<?php esc_html_e( 'Try Embedded App', 'lifecoachhub-app' ); ?>
				</button>
			</div>
			
			<div class="lifecoachhub-tab-content active" id="tab-options">
				<h2><?php esc_html_e( 'LifeCoach Hub Application', 'lifecoachhub-app' ); ?></h2>
				
				<p><?php esc_html_e( 'Some websites may restrict embedding in iframes due to security policies. Here are multiple ways to access the LifeCoach Hub application:', 'lifecoachhub-app' ); ?></p>
				
				<div class="lifecoachhub-options">
					<div class="lifecoachhub-option">
						<h3><?php esc_html_e( 'Option 1: Open in New Tab', 'lifecoachhub-app' ); ?></h3>
						<p><?php esc_html_e( 'This will open the application in a new browser tab.', 'lifecoachhub-app' ); ?></p>
						<a href="<?php echo esc_url( $app_url ); ?>" class="button button-primary" target="_blank">
							<?php esc_html_e( 'Open in New Tab', 'lifecoachhub-app' ); ?>
						</a>
					</div>
					
					<div class="lifecoachhub-option">
						<h3><?php esc_html_e( 'Option 2: Try Embedded View', 'lifecoachhub-app' ); ?></h3>
						<p><?php esc_html_e( 'Try viewing the application embedded within this page. This may not work on all websites due to security restrictions.', 'lifecoachhub-app' ); ?></p>
						<button class="button show-embedded-app">
							<?php esc_html_e( 'Try Embedded View', 'lifecoachhub-app' ); ?>
						</button>
					</div>
				</div>
				
				<div class="lifecoachhub-notice">
					<h3><?php esc_html_e( 'Technical Note', 'lifecoachhub-app' ); ?></h3>
					<p><?php esc_html_e( 'Modern web browsers and websites implement security features like "X-Frame-Options" and "Content-Security-Policy" that may prevent websites from being embedded within frames on other domains.', 'lifecoachhub-app' ); ?></p>
					<p><?php esc_html_e( 'If the embedded view doesn\'t work, please use the "Open in New Tab" option instead.', 'lifecoachhub-app' ); ?></p>
				</div>
			</div>
			
			<div class="lifecoachhub-tab-content" id="tab-app">
				<div class="lifecoachhub-iframe-container">
					<div id="loading-indicator" class="loading-spinner">
						<div class="spinner"></div>
						<p><?php esc_html_e( 'Loading LifeCoach Hub...', 'lifecoachhub-app' ); ?></p>
					</div>
					
					<iframe 
						src="about:blank" 
						data-src="<?php echo esc_url( $app_url ); ?>" 
						id="lifecoachhub-iframe"
						title="<?php esc_attr_e( 'LifeCoach Hub Application', 'lifecoachhub-app' ); ?>"
						class="lifecoachhub-iframe"
						sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation"
					></iframe>

					<div id="iframe-error" class="iframe-error" style="display:none;">
						<h3><?php esc_html_e( 'Unable to load the application in embedded mode', 'lifecoachhub-app' ); ?></h3>
						<p><?php esc_html_e( 'The LifeCoach Hub application cannot be embedded due to security restrictions.', 'lifecoachhub-app' ); ?></p>
						<a href="<?php echo esc_url( $app_url ); ?>" class="button button-primary" target="_blank">
							<?php esc_html_e( 'Open in New Tab Instead', 'lifecoachhub-app' ); ?>
						</a>
						<button class="button back-to-options">
							<?php esc_html_e( 'Back to Options', 'lifecoachhub-app' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
