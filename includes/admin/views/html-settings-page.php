<?php
/**
 * Admin Settings Page View
 *
 * @package LifeCoachHub
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$api_key = get_option( 'lifecoachhub_api_key', '' );
$connection_status = get_option( 'lifecoachhub_connection_status', '' );
$connected_at = get_option( 'lifecoachhub_connected_at', '' );
$is_connected = ! empty( $api_key ) && ! empty( $connection_status );
?>

<div class="wrap lifecoachhub-admin-wrap">
    <h1><?php esc_html_e( 'LifeCoach Hub Settings', 'lifecoachhub-app' ); ?></h1>
    
    <?php settings_errors( 'lifecoachhub_settings' ); ?>
    
    <div class="lifecoachhub-settings-container">
        <form method="post" action="">
            <?php wp_nonce_field( 'lifecoachhub_save_settings', 'lifecoachhub_settings_nonce' ); ?>
            
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="lifecoachhub_api_key"><?php esc_html_e( 'API Key', 'lifecoachhub-app' ); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                id="lifecoachhub_api_key" 
                                name="lifecoachhub_api_key" 
                                value="<?php echo esc_attr( $api_key ); ?>" 
                                class="regular-text" 
                                placeholder="<?php esc_attr_e( 'Enter your LifeCoach Hub API key', 'lifecoachhub-app' ); ?>"
                            />
                            <p class="description">
                                <?php esc_html_e( 'Enter the API key provided by LifeCoach Hub to connect your WordPress site.', 'lifecoachhub-app' ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'Connection Status', 'lifecoachhub-app' ); ?>
                        </th>
                        <td>
                            <?php if ( $is_connected ) : ?>
                                <span class="lifecoachhub-status-connected">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e( 'Connected', 'lifecoachhub-app' ); ?>
                                </span>
                                <?php if ( ! empty( $connected_at ) ) : ?>
                                    <p class="description">
                                        <?php 
                                        /* translators: %s: date and time of connection */
                                        printf( 
                                            esc_html__( 'Connected since: %s', 'lifecoachhub-app' ), 
                                            esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $connected_at ) ) ) 
                                        ); 
                                        ?>
                                    </p>
                                <?php endif; ?>
                            <?php else : ?>
                                <span class="lifecoachhub-status-disconnected">
                                    <span class="dashicons dashicons-no-alt"></span>
                                    <?php esc_html_e( 'Disconnected', 'lifecoachhub-app' ); ?>
                                </span>
                                <p class="description">
                                    <?php esc_html_e( 'Please enter your API key to connect with LifeCoach Hub.', 'lifecoachhub-app' ); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'Quick Links', 'lifecoachhub-app' ); ?>
                        </th>
                        <td>
                            <p>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=lifecoachhub' ) ); ?>" class="button">
                                    <?php esc_html_e( 'Go to LifeCoach Hub Dashboard', 'lifecoachhub-app' ); ?>
                                </a>
                                
                                <a href="http://localhost:8000" class="button" target="_blank">
                                    <?php esc_html_e( 'Visit LifeCoach Hub Website', 'lifecoachhub-app' ); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" 
                    name="lifecoachhub_settings_submit" 
                    id="lifecoachhub_settings_submit" 
                    class="button button-primary" 
                    value="<?php esc_attr_e( 'Save Settings', 'lifecoachhub-app' ); ?>"
                />
            </p>
        </form>
    </div>
</div>
