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
$base_app_url = $app_url;

if ( $api_key ) {
	$app_url = $app_url . '/launchpad';
	$app_url = add_query_arg( array(
		'api_key' => $api_key,
		'source'  => 'external_connection',
		'embedded' => '1'  // Let the app know it's embedded
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
		<div id="iframe-loading" style="text-align: center; padding: 50px;">
			<p><?php esc_html_e( 'Loading LifeCoach Hub...', 'lifecoachhub-app' ); ?></p>
		</div>
		
		<iframe 
			src="<?php echo esc_url( $app_url ); ?>" 
			id="lifecoachhub-clean-iframe"
			title="<?php esc_attr_e( 'LifeCoach Hub Application', 'lifecoachhub-app' ); ?>"
			class="lifecoachhub-clean-iframe"
			sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation"
			style="display: none;"
		></iframe>
		
		<div id="iframe-fallback" style="display: none; text-align: center; padding: 50px;">
			<h3><?php esc_html_e( 'Unable to load embedded view', 'lifecoachhub-app' ); ?></h3>
			<p><?php esc_html_e( 'The application cannot be embedded due to security restrictions.', 'lifecoachhub-app' ); ?></p>
			<a href="<?php echo esc_url( $app_url ); ?>" class="button button-primary" target="_blank">
				<?php esc_html_e( 'Open in New Tab', 'lifecoachhub-app' ); ?>
			</a>
		</div>
	</div>
	
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const iframe = document.getElementById('lifecoachhub-clean-iframe');
		const loading = document.getElementById('iframe-loading');
		const fallback = document.getElementById('iframe-fallback');
		const baseUrl = '<?php echo esc_js( $base_app_url ); ?>';
		const apiKey = '<?php echo esc_js( $api_key ); ?>';
		const adminPageUrl = '<?php echo esc_js( admin_url( 'admin.php?page=lifecoachhub' ) ); ?>';
		let loadTimeout;
		
		// Function to update iframe URL with API key parameters
		function updateIframeUrl(path) {
			const url = new URL(path, baseUrl);
			url.searchParams.set('api_key', apiKey);
			url.searchParams.set('source', 'external_connection');
			url.searchParams.set('embedded', '1');
			iframe.src = url.toString();
			
			// Update browser URL bar to reflect iframe navigation
			updateBrowserUrl(path);
		}
		
		// Function to update browser URL bar
		function updateBrowserUrl(path) {
			// Extract just the path without domain
			const pathOnly = path.startsWith('/') ? path : '/' + path;
			const newUrl = adminPageUrl + '&iframe_path=' + encodeURIComponent(pathOnly);
			
			// Update URL without page reload
			if (window.history && window.history.pushState) {
				window.history.pushState({iframePath: pathOnly}, '', newUrl);
			}
		}
		
		// Handle browser back/forward buttons
		window.addEventListener('popstate', function(event) {
			if (event.state && event.state.iframePath) {
				updateIframeUrl(event.state.iframePath);
			}
		});
		
		// Check if we have an iframe path in URL on page load
		const urlParams = new URLSearchParams(window.location.search);
		const initialPath = urlParams.get('iframe_path');
		if (initialPath) {
			setTimeout(function() {
				updateIframeUrl(initialPath);
			}, 1000);
		}
		
		// Set a timeout to show fallback if iframe doesn't load
		loadTimeout = setTimeout(function() {
			loading.style.display = 'none';
			fallback.style.display = 'block';
		}, 10000); // 10 second timeout
		
		iframe.addEventListener('load', function() {
			clearTimeout(loadTimeout);
			loading.style.display = 'none';
			iframe.style.display = 'block';
			console.log('LifeCoach Hub iframe loaded successfully');
		});
		
		iframe.addEventListener('error', function() {
			clearTimeout(loadTimeout);
			loading.style.display = 'none';
			fallback.style.display = 'block';
			console.error('LifeCoach Hub iframe failed to load');
		});
		
		// Listen for navigation messages from iframe
		window.addEventListener('message', function(event) {
			if (event.origin === baseUrl) {
				console.log('Message from LifeCoach Hub:', event.data);
				
				// Handle navigation requests from iframe
				if (event.data && event.data.type === 'navigate') {
					updateIframeUrl(event.data.path);
				}
				
				// Handle URL changes from iframe
				if (event.data && event.data.type === 'url_changed') {
					updateBrowserUrl(event.data.path);
				}
				
				// Handle other iframe communications
				if (event.data && event.data.type === 'ready') {
					console.log('LifeCoach Hub app is ready');
				}
			}
		});
		
		// Send initial configuration to iframe when it loads
		iframe.addEventListener('load', function() {
			setTimeout(function() {
				iframe.contentWindow.postMessage({
					type: 'config',
					apiKey: apiKey,
					embedded: true,
					source: 'external_connection'
				}, baseUrl);
			}, 1000);
		});
	});
	</script>
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
