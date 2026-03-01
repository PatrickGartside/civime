<?php
/**
 * Plugin Name: CiviMe Core
 * Plugin URI: https://civi.me
 * Description: Core utilities for Civi.Me — API client, settings, caching for the Access100 data backend.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Patrick Gartside
 * Author URI: https://civi.me
 * License: GPL-2.0-or-later
 * Text Domain: civime-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIVIME_CORE_VERSION', '0.1.0' );
define( 'CIVIME_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'CIVIME_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload classes from the includes/ directory.
 *
 * Maps class names using the WordPress file-naming convention:
 * CiviMe_Foo_Bar → includes/class-foo-bar.php
 */
spl_autoload_register( function ( string $class_name ): void {
	if ( ! str_starts_with( $class_name, 'CiviMe_' ) ) {
		return;
	}

	$without_prefix = substr( $class_name, strlen( 'CiviMe_' ) );
	$slug           = strtolower( str_replace( '_', '-', $without_prefix ) );
	$file           = CIVIME_CORE_PATH . 'includes/class-' . $slug . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Bootstrap the plugin on 'plugins_loaded' so all WordPress APIs are ready.
 */
add_action( 'plugins_loaded', 'civime_core_init' );

function civime_core_init(): void {
	// Admin Subscribers registers the top-level CiviMe menu — must come first
	// so that Settings can attach its submenu page to the parent.
	new CiviMe_Admin_Subscribers();
	new CiviMe_Settings();
}

// -------------------------------------------------------------------------
// Public helper functions for use by other plugins / themes
// -------------------------------------------------------------------------

/**
 * Returns the singleton CiviMe_API_Client instance configured from settings.
 *
 * @return CiviMe_API_Client
 */
function civime_api(): CiviMe_API_Client {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new CiviMe_API_Client(
			civime_get_option( 'civime_api_url', 'https://access100.app' ),
			civime_get_option( 'civime_api_key', '' ),
		);
	}

	return $instance;
}

/**
 * Retrieves a CiviMe plugin option from wp_options.
 *
 * @param string $key     The option key (should include the civime_ prefix).
 * @param mixed  $default Fallback value when the option does not exist.
 * @return mixed
 */
function civime_get_option( string $key, mixed $default = null ): mixed {
	return get_option( $key, $default );
}
