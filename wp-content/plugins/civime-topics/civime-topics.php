<?php
/**
 * Plugin Name: CiviMe Topics
 * Plugin URI: https://civi.me
 * Description: "What Matters to Me" topic picker — lets users select policy topics instead of browsing 300+ councils.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.2
 * Author: Patrick Gartside
 * Author URI: https://civi.me
 * License: GPL-2.0-or-later
 * Text Domain: civime-topics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CIVIME_TOPICS_VERSION', '0.1.0' );
define( 'CIVIME_TOPICS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CIVIME_TOPICS_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( string $class_name ): void {
	if ( ! str_starts_with( $class_name, 'CiviMe_Topics_' ) ) {
		return;
	}

	$suffix    = substr( $class_name, strlen( 'CiviMe_Topics_' ) );
	$file_name = 'class-' . strtolower( str_replace( '_', '-', $suffix ) ) . '.php';
	$file_path = CIVIME_TOPICS_PATH . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

add_action( 'plugins_loaded', 'civime_topics_init' );

function civime_topics_init(): void {
	if ( ! function_exists( 'civime_api' ) ) {
		add_action( 'admin_notices', function (): void {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'CiviMe Topics requires the CiviMe Core plugin to be installed and activated.', 'civime-topics' )
				. '</p></div>';
		} );
		return;
	}

	new CiviMe_Topics_Router();
	new CiviMe_Topics_Picker();
}

register_activation_hook( __FILE__, function (): void {
	// Register rewrite rules before flushing so they're included in the generated rules.
	( new CiviMe_Topics_Router() )->register_rewrite_rules();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function (): void {
	flush_rewrite_rules();
} );

add_action( 'wp_enqueue_scripts', function (): void {
	$route = get_query_var( 'civime_route' );

	// Enqueue on topic pages and on meetings pages (for topic filter bar)
	$topic_routes   = [ 'topic-picker', 'topic-page' ];
	$meeting_routes = [ 'meetings-list' ];

	if ( in_array( $route, $topic_routes, true ) ) {
		wp_enqueue_style(
			'civime-topics-css',
			CIVIME_TOPICS_URL . 'assets/css/topics.css',
			[ 'civime-theme' ],
			CIVIME_TOPICS_VERSION
		);

		wp_enqueue_script(
			'civime-topics-js',
			CIVIME_TOPICS_URL . 'assets/js/topics.js',
			[],
			CIVIME_TOPICS_VERSION,
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);
	}

	// Also load topics JS on meetings pages for filter integration
	if ( in_array( $route, $meeting_routes, true ) ) {
		wp_enqueue_style(
			'civime-topics-css',
			CIVIME_TOPICS_URL . 'assets/css/topics.css',
			[ 'civime-theme' ],
			CIVIME_TOPICS_VERSION
		);

		wp_enqueue_script(
			'civime-topics-js',
			CIVIME_TOPICS_URL . 'assets/js/topics.js',
			[],
			CIVIME_TOPICS_VERSION,
			[
				'strategy'  => 'defer',
				'in_footer' => true,
			]
		);
	}
} );
