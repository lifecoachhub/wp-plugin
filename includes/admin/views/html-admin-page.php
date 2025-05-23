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
?>
<div class="wrap lifecoachhub-admin-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="lifecoachhub-app-container">
		<div class="lifecoachhub-app-info">
			<p><?php esc_html_e( 'Due to browser security policies, we cannot embed the LifeCoach Hub application directly within WordPress.', 'lifecoachhub-app' ); ?></p>
			
			<p><strong><?php esc_html_e( 'Please choose one of the following options to access the application:', 'lifecoachhub-app' ); ?></strong></p>
			
			<div class="lifecoachhub-buttons">
				<a href="<?php echo esc_url( home_url( 'lifecoachhub-app' ) ); ?>" class="button button-primary" target="_blank">
					<?php esc_html_e( 'Open in New Tab', 'lifecoachhub-app' ); ?>
				</a>
				
				<a href="https://app.lifecoachhub.com/" class="button" target="_blank">
					<?php esc_html_e( 'Go to LifeCoach Hub Website', 'lifecoachhub-app' ); ?>
				</a>
			</div>
			
			<div class="lifecoachhub-notice">
				<h3><?php esc_html_e( 'Why this happens?', 'lifecoachhub-app' ); ?></h3>
				<p><?php esc_html_e( 'Modern web browsers implement a security feature called "X-Frame-Options" or "Content-Security-Policy" that can prevent websites from being embedded within iframes on other domains. This is a security measure to protect against clickjacking attacks.', 'lifecoachhub-app' ); ?></p>
				
				<p><?php esc_html_e( 'The LifeCoach Hub application has this security feature enabled, which is why we cannot embed it directly within your WordPress admin.', 'lifecoachhub-app' ); ?></p>
			</div>
		</div>
	</div>
</div>
