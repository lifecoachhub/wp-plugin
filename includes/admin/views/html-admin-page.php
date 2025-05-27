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
$app_url = 'http://localhost:8000'; // 'https://app.lifecoachhub.com/';
// http://localhost:8000/login?external_connection=1&redirect_url=http%3A%2F%2Flifecoachhub.local%2Fwp-admin%2Fadmin.php%3Fpage%3Dlifecoachhub

if ( $api_key ) {
	$app_url = $app_url . '/launchpad';
	// $app_url = $app_url . '/coach/clients/stream';

	$app_url = add_query_arg( array(
		'api_key' => $api_key,
		'source'  => 'external_connection'
	), $app_url );
} else {
	$app_url = $app_url . '/login';

	// For connection, add redirect URL and external connection flag
	$redirect_url = home_url( $_SERVER['REQUEST_URI'] );
	$app_url = add_query_arg( array(
		'redirect_url' => urlencode( $redirect_url ),
		'external_connection' => '1'
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
	<!-- Show simple connect interface if not connected -->
	<div class="wrap lifecoachhub-admin-wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<div class="lifecoachhub-connect-container">
			<div class="lifecoachhub-connect-box">
				<h2><?php esc_html_e( 'Connect to LifeCoach Hub', 'lifecoachhub-app' ); ?></h2>
				<p><?php esc_html_e( 'You are not connected to LifeCoach Hub. Please connect to access the application.', 'lifecoachhub-app' ); ?></p>
				
				<p>
					<a href="<?php echo esc_url( $app_url ); ?>" class="button button-primary">
						<?php esc_html_e( 'Connect', 'lifecoachhub-app' ); ?>
					</a>
				</p>
			</div>
		</div>
	</div>
<?php endif; ?>
