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
	$redirect_url = home_url('/');
	if (isset($_SERVER['REQUEST_URI'])) {
		$request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
		$redirect_url = home_url($request_uri);
	}
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
						<title><?php esc_html_e('Loading', 'lifecoachhub'); ?></title>
						<path d="M0 16q0 0.832 0.576 1.44t1.44 0.576h4q0.8 0 1.408-0.576t0.576-1.44-0.576-1.408-1.408-0.576h-4q-0.832 0-1.44 0.576t-0.576 1.408zM4.096 25.888q0 0.832 0.608 1.44t1.408 0.576 1.408-0.576l2.816-2.816q0.608-0.608 0.608-1.408t-0.608-1.44-1.408-0.576-1.408 0.576l-2.816 2.848q-0.608 0.576-0.608 1.376zM4.096 6.112q0 0.832 0.608 1.408l2.816 2.848q0.576 0.576 1.408 0.576t1.408-0.576 0.608-1.44-0.608-1.408l-2.816-2.816q-0.576-0.608-1.408-0.608t-1.408 0.608-0.608 1.408zM14.016 30.016q0 0.832 0.576 1.408t1.408 0.576 1.408-0.576 0.608-1.408v-4q0-0.832-0.608-1.408t-1.408-0.608-1.408 0.608-0.576 1.408v4zM14.016 6.016q0 0.832 0.576 1.408t1.408 0.576 1.408-0.576 0.608-1.408v-4q0-0.832-0.608-1.408t-1.408-0.608-1.408 0.608-0.576 1.408v4zM21.056 23.104q0 0.8 0.608 1.408l2.816 2.816q0.576 0.576 1.408 0.576t1.44-0.576 0.576-1.44-0.576-1.376l-2.848-2.848q-0.576-0.576-1.408-0.576t-1.408 0.576-0.608 1.44zM21.056 8.928q0 0.832 0.608 1.44t1.408 0.576 1.408-0.576l2.848-2.848q0.576-0.576 0.576-1.408t-0.576-1.408-1.44-0.608-1.408 0.608l-2.816 2.816q-0.608 0.576-0.608 1.408zM24 16q0 0.832 0.576 1.44t1.44 0.576h4q0.8 0 1.408-0.576t0.576-1.44-0.576-1.408-1.408-0.576h-4q-0.832 0-1.44 0.576t-0.576 1.408z"></path>
					</svg>
				</span>
				<br>
				<?php esc_html_e('Loading Life Coach Hub...', 'lifecoachhub'); ?>
			</p>
		</div>

		<iframe src="<?php echo esc_url($app_url); ?>" id="lifecoachhub-clean-iframe"
			title="<?php esc_attr_e('Life Coach Hub Application', 'lifecoachhub'); ?>"
			class="lifecoachhub-clean-iframe"
			sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation"
			style="display: none;"></iframe>

		<div id="iframe-fallback" style="display: none; text-align: center; padding: 50px;">
			<h3><?php esc_html_e('Unable to load embedded view', 'lifecoachhub'); ?></h3>
			<p><?php esc_html_e('The application cannot be embedded due to security restrictions.', 'lifecoachhub'); ?>
			</p>
			<a href="<?php echo esc_url($app_url); ?>" class="button button-primary" target="_blank">
				<?php esc_html_e('Open in New Tab', 'lifecoachhub'); ?>
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
						// Reload iframe with fresh auth parameters
						updateIframeUrl(iframe.src);
					}
				}
			});
		});
	</script>
<?php else: ?>
	<style>
		#wpadminbar, #adminmenumain, #adminmenuwrap, #wpfooter {
			display: none;
		}
		.lifecoachhub-admin-wrap {
			margin: -32px 0px;
		}
	</style>
	<!-- Show enhanced connect interface if not connected -->
	<div class="wrap lifecoachhub-admin-wrap">
		<div class="lifecoachhub-welcome-container">
			<!-- Hero Section -->
			<div class="lifecoachhub-hero">
				<div class="lifecoachhub-hero-content">
					<div class="lifecoachhub-logo">
						<img src="<?php echo esc_url(LIFECOACHHUB_PLUGIN_URL . 'assets/images/logo.png'); ?>" alt="Life Coach Hub" />
					</div>
					<h1><?php esc_html_e('Welcome to Life Coach Hub!', 'lifecoachhub'); ?></h1>
					<p class="hero-subtitle"><?php esc_html_e('Transform your coaching practice with our comprehensive platform designed specifically for life coaches.', 'lifecoachhub'); ?></p>
					<div class="hero-buttons">
						<a href="<?php echo esc_url($app_url); ?>" class="button button-primary button-hero">
							<?php esc_html_e('Get Started Now', 'lifecoachhub'); ?>
						</a>
						<a href="<?php echo esc_url(admin_url()); ?>" class="button button-secondary button-hero">
							<?php esc_html_e('Go Back to Dashboard', 'lifecoachhub'); ?>
						</a>
					</div>
				</div>
			</div>

			<!-- Features Grid -->
			<div class="lifecoachhub-features-grid">
				<div class="feature-card">
					<div class="feature-icon">
						<span class="dashicons dashicons-groups"></span>
					</div>
					<h3><?php esc_html_e('Client Management', 'lifecoachhub'); ?></h3>
					<p><?php esc_html_e('Organize and manage your coaching clients with detailed profiles, progress tracking, and comprehensive client histories.', 'lifecoachhub'); ?></p>
				</div>

				<div class="feature-card">
					<div class="feature-icon">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<h3><?php esc_html_e('Session Scheduling', 'lifecoachhub'); ?></h3>
					<p><?php esc_html_e('Effortlessly schedule coaching sessions, send automated reminders, and manage your coaching calendar with ease.', 'lifecoachhub'); ?></p>
				</div>

				<div class="feature-card">
					<div class="feature-icon">
						<span class="dashicons dashicons-book"></span>
					</div>
					<h3><?php esc_html_e('Course Management', 'lifecoachhub'); ?></h3>
					<p><?php esc_html_e('Create, organize, and deliver structured coaching programs and courses to help your clients achieve their goals.', 'lifecoachhub'); ?></p>
				</div>

				<div class="feature-card">
					<div class="feature-icon">
						<span class="dashicons dashicons-admin-users"></span>
					</div>
					<h3><?php esc_html_e('Profile Management', 'lifecoachhub'); ?></h3>
					<p><?php esc_html_e('Build and customize your professional coaching profile to showcase your expertise and attract new clients.', 'lifecoachhub'); ?></p>
				</div>

				<div class="feature-card">
					<div class="feature-icon">
						<span class="dashicons dashicons-admin-tools"></span>
					</div>
					<h3><?php esc_html_e('Coaching Tools', 'lifecoachhub'); ?></h3>
					<p><?php esc_html_e('Access a comprehensive suite of coaching tools including assessments, worksheets, and progress tracking instruments.', 'lifecoachhub'); ?></p>
				</div>

				<div class="feature-card">
					<div class="feature-icon">
						<span class="dashicons dashicons-chart-line"></span>
					</div>
					<h3><?php esc_html_e('Analytics & Insights', 'lifecoachhub'); ?></h3>
					<p><?php esc_html_e('Track your coaching practice performance with detailed analytics and insights to help you grow your business.', 'lifecoachhub'); ?></p>
				</div>
			</div>

			<!-- Benefits Section -->
			<div class="lifecoachhub-benefits">
				<h2><?php esc_html_e('Why Choose Life Coach Hub?', 'lifecoachhub'); ?></h2>
				
				<div class="benefits-grid">
					<div class="benefit-card">
						<div class="benefit-icon">
							<span class="dashicons dashicons-clock"></span>
						</div>
						<h3><?php esc_html_e('Save Time & Increase Efficiency', 'lifecoachhub'); ?></h3>
						<p><?php esc_html_e('Streamline your coaching practice with automated scheduling, client management, and progress tracking tools.', 'lifecoachhub'); ?></p>
					</div>

					<div class="benefit-card">
						<div class="benefit-icon">
							<span class="dashicons dashicons-money-alt"></span>
						</div>
						<h3><?php esc_html_e('Grow Your Revenue', 'lifecoachhub'); ?></h3>
						<p><?php esc_html_e('Expand your coaching business with tools designed to help you attract more clients and increase retention rates.', 'lifecoachhub'); ?></p>
					</div>

					<div class="benefit-card">
						<div class="benefit-icon">
							<span class="dashicons dashicons-businessman"></span>
						</div>
						<h3><?php esc_html_e('Professional Platform', 'lifecoachhub'); ?></h3>
						<p><?php esc_html_e('Present a professional image to your clients with our polished, user-friendly coaching platform.', 'lifecoachhub'); ?></p>
					</div>

					<div class="benefit-card">
						<div class="benefit-icon">
							<span class="dashicons dashicons-smartphone"></span>
						</div>
						<h3><?php esc_html_e('Mobile-Friendly Access', 'lifecoachhub'); ?></h3>
						<p><?php esc_html_e('Access your coaching practice from anywhere with our responsive platform that works on all devices.', 'lifecoachhub'); ?></p>
					</div>

					<div class="benefit-card">
						<div class="benefit-icon">
							<span class="dashicons dashicons-shield"></span>
						</div>
						<h3><?php esc_html_e('Secure & Reliable', 'lifecoachhub'); ?></h3>
						<p><?php esc_html_e('Your client data is protected with enterprise-grade security and reliable cloud infrastructure.', 'lifecoachhub'); ?></p>
					</div>

					<div class="benefit-card">
						<div class="benefit-icon">
							<span class="dashicons dashicons-sos"></span>
						</div>
						<h3><?php esc_html_e('24/7 Support', 'lifecoachhub'); ?></h3>
						<p><?php esc_html_e('Get help when you need it with our dedicated support team available around the clock.', 'lifecoachhub'); ?></p>
					</div>
				</div>
			</div>

			<!-- Trusted Section -->
			<div class="lifecoachhub-trusted">
				<h2><?php esc_html_e('Trusted by Professional Coaches Worldwide', 'lifecoachhub'); ?></h2>
				<p><?php esc_html_e('Join thousands of successful life coaches who have transformed their practice with Life Coach Hub.', 'lifecoachhub'); ?></p>
				
				<div class="trusted-stats">
					<div class="stat-item">
						<div class="stat-number">10,000+</div>
						<div class="stat-label"><?php esc_html_e('Active Coaches', 'lifecoachhub'); ?></div>
					</div>
					<div class="stat-item">
						<div class="stat-number">50,000+</div>
						<div class="stat-label"><?php esc_html_e('Coaching Sessions', 'lifecoachhub'); ?></div>
					</div>
					<div class="stat-item">
						<div class="stat-number">95%</div>
						<div class="stat-label"><?php esc_html_e('Satisfaction Rate', 'lifecoachhub'); ?></div>
					</div>
				</div>
			</div>

			<!-- Call to Action -->
			<div class="lifecoachhub-cta">
				<h2><?php esc_html_e('Ready to Transform Your Coaching Practice?', 'lifecoachhub'); ?></h2>
				<p><?php esc_html_e('Connect your WordPress site to Life Coach Hub and start building your professional coaching business today.', 'lifecoachhub'); ?></p>
				<div class="cta-buttons">
					<a href="<?php echo esc_url($app_url); ?>" class="button button-primary button-hero">
						<?php esc_html_e('Get Started Now', 'lifecoachhub'); ?>
					</a>
					<a href="<?php echo esc_url(admin_url()); ?>" class="button button-secondary button-hero" style="color: #1d2327ba !important;">
						<?php esc_html_e('Go Back to Dashboard', 'lifecoachhub'); ?>
					</a>
				</div>

				<div class="cta-buttons" style="margin-top: 40px;">
					<p style="color: #666; font-size: 14px; margin-bottom: 10px;">
						<?php esc_html_e('Wanna Access the full platform directly in your browser?', 'lifecoachhub'); ?>
					</p>
				</div>
				<div style="margin-top: 0px;">
					<a href="<?php echo esc_url(LIFECOACHHUB_APP_URL); ?>" class="button button-text button-hero" style="margin-top: 0;" target="_blank">
						<?php esc_html_e('Try Life Coach Hub Online', 'lifecoachhub'); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>