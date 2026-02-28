<?php
/**
 * Plugin Name: CiviMe Meetings
 * Plugin URI: https://civi.me
 * Description: Meeting calendar, detail pages, and council browser — powered by the Access100 API.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Patrick Gartside
 * Author URI: https://civi.me
 * License: GPL-2.0-or-later
 * Text Domain: civime-meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIVIME_MEETINGS_VERSION', '0.1.0' );
define( 'CIVIME_MEETINGS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CIVIME_MEETINGS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( string $class_name ): void {
	if ( ! str_starts_with( $class_name, 'CiviMe_Meetings_' ) ) {
		return;
	}

	$suffix    = substr( $class_name, strlen( 'CiviMe_Meetings_' ) );
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $suffix ) ) . '.php';
	$file_path = CIVIME_MEETINGS_PATH . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

add_action( 'plugins_loaded', 'civime_meetings_init' );

function civime_meetings_init(): void {
	if ( ! function_exists( 'civime_api' ) ) {
		add_action( 'admin_notices', function (): void {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'CiviMe Meetings requires the CiviMe Core plugin to be installed and activated.', 'civime-meetings' )
				. '</p></div>';
		} );
		return;
	}

	new CiviMe_Meetings_Router();
	require_once CIVIME_MEETINGS_PATH . 'includes/shortcodes.php';
}

register_activation_hook( __FILE__, function (): void {
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function (): void {
	flush_rewrite_rules();
} );

add_action( 'wp_enqueue_scripts', function (): void {
	if ( ! get_query_var( 'civime_route' ) ) {
		return;
	}

	wp_enqueue_style(
		'civime-meetings-css',
		CIVIME_MEETINGS_URL . 'assets/css/meetings.css',
		[ 'civime-theme' ],
		CIVIME_MEETINGS_VERSION
	);

	wp_enqueue_script(
		'civime-meetings-js',
		CIVIME_MEETINGS_URL . 'assets/js/meetings.js',
		[],
		CIVIME_MEETINGS_VERSION,
		[
			'strategy'  => 'defer',
			'in_footer' => true,
		]
	);
} );
