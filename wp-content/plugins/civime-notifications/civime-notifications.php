<?php
/**
 * Plugin Name: CiviMe Notifications
 * Plugin URI: https://civi.me
 * Description: Subscription UI and preference management for Civi.Me meeting notifications.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Patrick Gartside
 * Author URI: https://civi.me
 * License: GPL-2.0-or-later
 * Text Domain: civime-notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIVIME_NOTIFICATIONS_VERSION', '0.1.0' );
define( 'CIVIME_NOTIFICATIONS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CIVIME_NOTIFICATIONS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( string $class_name ): void {
	if ( ! str_starts_with( $class_name, 'CiviMe_Notifications_' ) ) {
		return;
	}

	$suffix    = substr( $class_name, strlen( 'CiviMe_Notifications_' ) );
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $suffix ) ) . '.php';
	$file_path = CIVIME_NOTIFICATIONS_PATH . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

add_action( 'plugins_loaded', 'civime_notifications_init' );

function civime_notifications_init(): void {
	if ( ! function_exists( 'civime_api' ) ) {
		add_action( 'admin_notices', function (): void {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'CiviMe Notifications requires the CiviMe Core plugin to be installed and activated.', 'civime-notifications' )
				. '</p></div>';
		} );
		return;
	}

	new CiviMe_Notifications_Router();
	require_once CIVIME_NOTIFICATIONS_PATH . 'includes/shortcodes.php';
}

register_activation_hook( __FILE__, function (): void {
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function (): void {
	flush_rewrite_rules();
} );

add_action( 'wp_enqueue_scripts', function (): void {
	if ( ! get_query_var( 'civime_notif_route' ) ) {
		return;
	}

	wp_enqueue_style(
		'civime-notifications-css',
		CIVIME_NOTIFICATIONS_URL . 'assets/css/notifications.css',
		[ 'civime-theme' ],
		CIVIME_NOTIFICATIONS_VERSION
	);

	wp_enqueue_script(
		'civime-notifications-js',
		CIVIME_NOTIFICATIONS_URL . 'assets/js/notifications.js',
		[],
		CIVIME_NOTIFICATIONS_VERSION,
		[
			'strategy'  => 'defer',
			'in_footer' => true,
		]
	);
} );
