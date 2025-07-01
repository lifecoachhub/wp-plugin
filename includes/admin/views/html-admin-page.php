<?php
/**
 * Admin Page View
 *
 * @package LifeCoachHub
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Get connection status and API key
$api_key = get_option('lifecoachhub_api_key');
$connection_status = get_option('lifecoachhub_connection_status');
$connected_at = get_option('lifecoachhub_connected_at');
$is_connected = $api_key && 'success' === $connection_status;

// Build app URL with API key and source parameter if available
$app_url = LIFECOACHHUB_APP_URL;
$base_app_url = $app_url;

if ($api_key) {
	$app_url = $app_url . '/launchpad';
	$app_url = add_query_arg(array(
		'api_key' => $api_key,
		'source' => 'external_connection',
		'embedded' => '1'  // Let the app know it's embedded
	), $app_url);
} else {
	$app_url = $app_url . '/login';

	// For connection, add redirect URL and external connection flag
	$redirect_url = home_url($_SERVER['REQUEST_URI']);
	$app_url = add_query_arg(array(
		'redirect_url' => urlencode($redirect_url),
		'external_connection' => '1'
	), $app_url);
}

// If connected successfully, show only iframe
if ($is_connected): ?>
	<div class="lifecoachhub-clean-iframe-container">
		<div id="iframe-loading" style="text-align: center; padding: 50px;">
			<p>
				<span class="wp-block-spinner">
					<svg class="lifecoachhub-spinner" fill="#000000" width="800px" height="800px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
						<title><?php esc_html_e('Loading', 'lifecoachhub-app'); ?></title>
						<path d="M0 16q0 0.832 0.576 1.44t1.44 0.576h4q0.8 0 1.408-0.576t0.576-1.44-0.576-1.408-1.408-0.576h-4q-0.832 0-1.44 0.576t-0.576 1.408zM4.096 25.888q0 0.832 0.608 1.44t1.408 0.576 1.408-0.576l2.816-2.816q0.608-0.608 0.608-1.408t-0.608-1.44-1.408-0.576-1.408 0.576l-2.816 2.848q-0.608 0.576-0.608 1.376zM4.096 6.112q0 0.832 0.608 1.408l2.816 2.848q0.576 0.576 1.408 0.576t1.408-0.576 0.608-1.44-0.608-1.408l-2.816-2.816q-0.576-0.608-1.408-0.608t-1.408 0.608-0.608 1.408zM14.016 30.016q0 0.832 0.576 1.408t1.408 0.576 1.408-0.576 0.608-1.408v-4q0-0.832-0.608-1.408t-1.408-0.608-1.408 0.608-0.576 1.408v4zM14.016 6.016q0 0.832 0.576 1.408t1.408 0.576 1.408-0.576 0.608-1.408v-4q0-0.832-0.608-1.408t-1.408-0.608-1.408 0.608-0.576 1.408v4zM21.056 23.104q0 0.8 0.608 1.408l2.816 2.816q0.576 0.576 1.408 0.576t1.44-0.576 0.576-1.44-0.576-1.376l-2.848-2.848q-0.576-0.576-1.408-0.576t-1.408 0.576-0.608 1.44zM21.056 8.928q0 0.832 0.608 1.44t1.408 0.576 1.408-0.576l2.848-2.848q0.576-0.576 0.576-1.408t-0.576-1.408-1.44-0.608-1.408 0.608l-2.816 2.816q-0.608 0.576-0.608 1.408zM24 16q0 0.832 0.576 1.44t1.44 0.576h4q0.8 0 1.408-0.576t0.576-1.44-0.576-1.408-1.408-0.576h-4q-0.832 0-1.44 0.576t-0.576 1.408z"></path>
					</svg>
				</span>
				<br>
				<?php esc_html_e('Loading Life Coach Hub...', 'lifecoachhub-app'); ?>
			</p>
		</div>

		<iframe src="<?php echo esc_url($app_url); ?>" id="lifecoachhub-clean-iframe"
			title="<?php esc_attr_e('Life Coach Hub Application', 'lifecoachhub-app'); ?>"
			class="lifecoachhub-clean-iframe"
			sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation"
			style="display: none;"></iframe>

		<div id="iframe-fallback" style="display: none; text-align: center; padding: 50px;">
			<h3><?php esc_html_e('Unable to load embedded view', 'lifecoachhub-app'); ?></h3>
			<p><?php esc_html_e('The application cannot be embedded due to security restrictions.', 'lifecoachhub-app'); ?>
			</p>
			<a href="<?php echo esc_url($app_url); ?>" class="button button-primary" target="_blank">
				<?php esc_html_e('Open in New Tab', 'lifecoachhub-app'); ?>
			</a>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const iframe = document.getElementById('lifecoachhub-clean-iframe');
			const loading = document.getElementById('iframe-loading');
			const fallback = document.getElementById('iframe-fallback');
			const baseUrl = '<?php echo esc_js($base_app_url); ?>';
			const apiKey = '<?php echo esc_js($api_key); ?>';
			const adminPageUrl = '<?php echo esc_js(admin_url('admin.php?page=lifecoachhub')); ?>';
			let loadTimeout;

			// Function to inject our external connector script into the iframe
			function injectExternalConnector() {
				try {
					const iframeWindow = iframe.contentWindow;
					const iframeDocument = iframeWindow.document;

					// Check if document is accessible (same-origin)
					if (iframeDocument) {
						// Create script element
						const script = iframeDocument.createElement('script');
						script.src = '<?php echo esc_js(LIFECOACHHUB_PLUGIN_URL . 'assets/js/external-connector.js'); ?>?v=' + (new Date()).getTime();
						script.async = true;

						// Append to head or body
						(iframeDocument.head || iframeDocument.body).appendChild(script);

						// Also inject configuration directly
						setTimeout(function () {
							iframeWindow.postMessage({
								type: 'config',
								apiKey: apiKey,
								embedded: true,
								source: 'external_connection',
								wordpressOrigin: window.location.origin
							}, '*');
						}, 500);
					}
				} catch (error) {
					console.warn('Could not inject script directly due to CORS, using postMessage instead', error);

					// Fall back to postMessage for cross-origin iframes
					setTimeout(function () {
						iframe.contentWindow.postMessage({
							type: 'config',
							apiKey: apiKey,
							embedded: true,
							source: 'external_connection',
							wordpressOrigin: window.location.origin
						}, '*');
					}, 1000);
				}
			}

			// Function to update iframe URL with API key parameters
			function updateIframeUrl(path) {
				console.log('updateIframeUrl::path', path)
				// First, determine if this is a full URL or just a path
				let url;
				if (path.startsWith('http')) {
					// It's a full URL
					url = new URL(path);
				} else {
					// It's just a path, prepend base URL
					url = new URL(path.startsWith('/') ? path : '/' + path, baseUrl);
				}

				// Always ensure our required parameters are set
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
				let pathOnly;
				if (path.startsWith('http')) {
					try {
						const pathUrl = new URL(path);
						pathOnly = pathUrl.pathname + pathUrl.search + pathUrl.hash;
					} catch (e) {
						pathOnly = path.startsWith('/') ? path : '/' + path;
					}
				} else {
					pathOnly = path.startsWith('/') ? path : '/' + path;
				}

				const newUrl = adminPageUrl + '&iframe_path=' + encodeURIComponent(pathOnly);

				// Update URL without page reload
				if (window.history && window.history.pushState) {
					window.history.pushState({ iframePath: pathOnly }, '', newUrl);
				}
			}

			// Handle browser back/forward buttons
			window.addEventListener('popstate', function (event) {
				if (event.state && event.state.iframePath) {
					updateIframeUrl(event.state.iframePath);
				}
			});

			// Check if we have an iframe path in URL on page load
			const urlParams = new URLSearchParams(window.location.search);
			const initialPath = urlParams.get('iframe_path');
			if (initialPath) {
				setTimeout(function () {
					updateIframeUrl(initialPath);
				}, 1000);
			}

			// Set a timeout to show fallback if iframe doesn't load
			loadTimeout = setTimeout(function () {
				loading.style.display = 'none';
				fallback.style.display = 'block';
			}, 15000); // 15 second timeout

			iframe.addEventListener('load', function () {
				clearTimeout(loadTimeout);
				loading.style.display = 'none';
				iframe.style.display = 'block';

				// Inject our external connector script
				injectExternalConnector();
			});

			iframe.addEventListener('error', function () {
				clearTimeout(loadTimeout);
				loading.style.display = 'none';
				fallback.style.display = 'block';
			});

			// Listen for navigation messages from iframe
			window.addEventListener('message', function (event) {
				// Be more lenient with origin checking (if running on different ports/domains)
				if (event.origin.startsWith(baseUrl) || event.origin.includes('localhost')) {
					// Handle navigation requests from iframe
					if (event.data && event.data.type === 'navigate') {
						updateIframeUrl(event.data.path);
					}

					// Handle URL changes from iframe
					if (event.data && event.data.type === 'url_changed' || event.data.type === 'location_change') {
						updateBrowserUrl(event.data.path || event.data.url);
					}

					// Handle other iframe communications
					if (event.data && event.data.type === 'ready') {
						// Inject our external connector script again to ensure it's there
						setTimeout(injectExternalConnector, 500);
					}

					// Handle auth refresh requests
					if (event.data && event.data.type === 'refresh_auth') {
						console.log('Refreshing authentication');
						// Reload iframe with fresh auth parameters
						updateIframeUrl(iframe.src);
					}
				}
			});
		});
	</script>
<?php else: ?>
	<!-- Show simple connect interface if not connected -->
	<div class="wrap lifecoachhub-admin-wrap">
		<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

		<div class="lifecoachhub-connect-container">
			<div class="lifecoachhub-connect-box">
				<h2><?php esc_html_e('Connect to Life Coach Hub', 'lifecoachhub-app'); ?></h2>
				<p><?php esc_html_e('You are not connected to Life Coach Hub. Please connect to access the application.', 'lifecoachhub-app'); ?>
				</p>

				<p>
					<a href="<?php echo esc_url($app_url); ?>" class="button button-primary">
						<?php esc_html_e('Connect', 'lifecoachhub-app'); ?>
					</a>
				</p>
			</div>
		</div>
	</div>
<?php endif; ?>