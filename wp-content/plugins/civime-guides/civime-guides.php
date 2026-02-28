<?php
/**
 * Plugin Name: CiviMe Guides
 * Plugin URI: https://civi.me
 * Description: How-to guides for civic engagement — testimony, letter writing, advocacy, and more.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Patrick Gartside
 * Author URI: https://civi.me
 * License: GPL-2.0-or-later
 * Text Domain: civime-guides
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIVIME_GUIDES_VERSION', '0.1.0' );
define( 'CIVIME_GUIDES_PATH', plugin_dir_path( __FILE__ ) );
define( 'CIVIME_GUIDES_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( string $class_name ): void {
	if ( ! str_starts_with( $class_name, 'CiviMe_Guides_' ) ) {
		return;
	}

	$suffix    = substr( $class_name, strlen( 'CiviMe_Guides_' ) );
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $suffix ) ) . '.php';
	$file_path = CIVIME_GUIDES_PATH . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

add_action( 'init', function (): void {
	new CiviMe_Guides_Post_Type();
} );

register_activation_hook( __FILE__, function (): void {
	// Register CPT + taxonomy before flushing so rules are generated.
	( new CiviMe_Guides_Post_Type() )->register();
	CiviMe_Guides_Post_Type::seed_terms();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function (): void {
	flush_rewrite_rules();
} );

add_action( 'wp_enqueue_scripts', function (): void {
	if ( ! is_post_type_archive( 'civime_guide' ) && ! is_tax( 'guide_category' ) && ! is_singular( 'civime_guide' ) ) {
		return;
	}

	wp_enqueue_style(
		'civime-guides-css',
		CIVIME_GUIDES_URL . 'assets/css/guides.css',
		[ 'civime-theme' ],
		CIVIME_GUIDES_VERSION
	);
} );
