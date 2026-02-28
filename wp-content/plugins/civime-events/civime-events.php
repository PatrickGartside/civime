<?php
/**
 * Plugin Name: CiviMe Events
 * Plugin URI: https://civi.me
 * Description: Community events for civic engagement — letter writing parties, info sessions, ambassador meetups, and more.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Patrick Gartside
 * Author URI: https://civi.me
 * License: GPL-2.0-or-later
 * Text Domain: civime-events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIVIME_EVENTS_VERSION', '0.1.0' );
define( 'CIVIME_EVENTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CIVIME_EVENTS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( string $class_name ): void {
	if ( ! str_starts_with( $class_name, 'CiviMe_Events_' ) ) {
		return;
	}

	$suffix    = substr( $class_name, strlen( 'CiviMe_Events_' ) );
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $suffix ) ) . '.php';
	$file_path = CIVIME_EVENTS_PATH . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

add_action( 'init', function (): void {
	new CiviMe_Events_Post_Type();
} );

add_action( 'plugins_loaded', function (): void {
	new CiviMe_Events_Meta_Box();
} );

register_activation_hook( __FILE__, function (): void {
	// Register CPT + taxonomy before flushing so rules are generated.
	( new CiviMe_Events_Post_Type() )->register();
	CiviMe_Events_Post_Type::seed_terms();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function (): void {
	flush_rewrite_rules();
} );

add_action( 'wp_enqueue_scripts', function (): void {
	if ( ! is_post_type_archive( 'civime_event' ) && ! is_tax( 'event_type' ) && ! is_singular( 'civime_event' ) ) {
		return;
	}

	wp_enqueue_style(
		'civime-events-css',
		CIVIME_EVENTS_URL . 'assets/css/events.css',
		[ 'civime-theme' ],
		CIVIME_EVENTS_VERSION
	);
} );
