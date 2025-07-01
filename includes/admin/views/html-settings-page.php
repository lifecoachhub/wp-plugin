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

// Build app URL with source parameter
$app_url = LIFECOACHHUB_APP_URL;

if ( ! $is_connected ) {
    $app_url = $app_url . '/login';

    // For connection, add redirect URL and external connection flag
    $redirect_url = admin_url( 'admin.php?page=lifecoachhub' );
    $app_url = add_query_arg( array(
        'redirect_url' => urlencode( $redirect_url ),
        'external_connection' => '1'
    ), $app_url );
}
?>

<div class="wrap lifecoachhub-admin-wrap">
    <h1><?php esc_html_e( 'Life Coach Hub Settings', 'lifecoachhub' ); ?></h1>

    <?php settings_errors( 'lifecoachhub_settings' ); ?>

    <div class="lifecoachhub-settings-container">
        <form method="post" action="">
            <?php wp_nonce_field( 'lifecoachhub_save_settings', 'lifecoachhub_settings_nonce' ); ?>

            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'Connection Status', 'lifecoachhub' ); ?>
                        </th>
                        <td>
                            <?php if ( $is_connected ) : ?>
                                <span class="lifecoachhub-status-connected">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e( 'Connected', 'lifecoachhub' ); ?>
                                </span>
                                <?php if ( ! empty( $connected_at ) ) : ?>
                                    <p class="description">
                                        <?php
                                        /* translators: %s: date and time of connection */
                                        printf(
                                            esc_html__( 'Connected since: %s', 'lifecoachhub' ),
                                            esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $connected_at ) ) )
                                        );
                                        ?>
                                    </p>
                                <?php endif; ?>
                                <p>
                                    <button type="submit"
                                        name="lifecoachhub_disconnect"
                                        id="lifecoachhub_disconnect"
                                        class="button button-secondary"
                                        onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to disconnect from Life Coach Hub? This will remove your API key and all connection data.', 'lifecoachhub' ); ?>');">
                                        <?php esc_html_e( 'Disconnect', 'lifecoachhub' ); ?>
                                    </button>
                                </p>
                            <?php else : ?>
                                <span class="lifecoachhub-status-disconnected">
                                    <span class="dashicons dashicons-no-alt"></span>
                                    <?php esc_html_e( 'Disconnected', 'lifecoachhub' ); ?>
                                </span>
                                <p class="description">
                                    <?php esc_html_e( 'Please enter your API key to connect with Life Coach Hub or use the Connect button below.', 'lifecoachhub' ); ?>
                                </p>
                                <p>
                                    <a href="<?php echo esc_url( $app_url ); ?>" class="button button-primary">
                                        <?php esc_html_e( 'Connect', 'lifecoachhub' ); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'Quick Links', 'lifecoachhub' ); ?>
                        </th>
                        <td>
                            <p>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=lifecoachhub' ) ); ?>" class="button">
                                    <?php esc_html_e( 'Go to Life Coach Hub Dashboard', 'lifecoachhub' ); ?>
                                </a>

                                <a href="<?php echo esc_url( LIFECOACHHUB_APP_URL ); ?>" class="button" target="_blank">
                                    <?php esc_html_e( 'Visit Life Coach Hub Website', 'lifecoachhub' ); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
