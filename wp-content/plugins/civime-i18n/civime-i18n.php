<?php
/**
 * Plugin Name: CiviMe I18n
 * Plugin URI: https://civi.me
 * Description: Multilingual support for Hawaii's 15 OLA languages — locale switching, language picker, hreflang SEO tags.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Patrick Gartside
 * Author URI: https://civi.me
 * License: GPL-2.0-or-later
 * Text Domain: civime-i18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIVIME_I18N_VERSION', '0.1.0' );
define( 'CIVIME_I18N_PATH', plugin_dir_path( __FILE__ ) );
define( 'CIVIME_I18N_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( string $class_name ): void {
	if ( ! str_starts_with( $class_name, 'CiviMe_I18n_' ) ) {
		return;
	}

	$suffix    = substr( $class_name, strlen( 'CiviMe_I18n_' ) );
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $suffix ) ) . '.php';
	$file_path = CIVIME_I18N_PATH . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

/**
 * Bootstrap early on plugins_loaded (priority 5) so locale is active
 * before other plugins load their text domains at default priority.
 */
add_action( 'plugins_loaded', 'civime_i18n_init', 5 );

function civime_i18n_init(): void {
	$locale = new CiviMe_I18n_Locale();
	$locale->init();

	new CiviMe_I18n_Page_Content();
	new CiviMe_I18n_Hreflang();

	if ( is_admin() ) {
		new CiviMe_I18n_Admin_Languages();
	}
}

add_action( 'wp_enqueue_scripts', function (): void {
	wp_enqueue_style(
		'civime-i18n-css',
		CIVIME_I18N_URL . 'assets/css/i18n.css',
		[ 'civime-theme' ],
		CIVIME_I18N_VERSION
	);
} );
