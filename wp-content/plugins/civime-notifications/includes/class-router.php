<?php
/**
 * CiviMe Notifications Router
 *
 * Registers rewrite rules and dispatches virtual URLs to plugin templates.
 * Routes are registered at init priority 11, after the meetings plugin's
 * catch-all at default priority 10. Since add_rewrite_rule('top') prepends,
 * later-registered rules appear earlier in the compiled array and match first.
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Notifications_Router {

	public function __construct() {
		add_action( 'init', [ $this, 'register_rewrite_rules' ], 11 );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		add_filter( 'template_include', [ $this, 'load_template' ] );
		add_filter( 'document_title_parts', [ $this, 'set_page_title' ] );
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );
		add_action( 'wp', [ $this, 'set_200_status' ] );
	}

	public function register_query_vars( array $vars ): array {
		$vars[] = 'civime_notif_route';
		return $vars;
	}

	public function register_rewrite_rules(): void {
		// The /meetings/subscribe rule is registered by the meetings router
		// to ensure correct ordering above the meetings catch-all pattern.
		add_rewrite_rule( '^notifications/manage/?$', 'index.php?civime_notif_route=manage', 'top' );
		add_rewrite_rule( '^notifications/confirmed/?$', 'index.php?civime_notif_route=confirmed', 'top' );
		add_rewrite_rule( '^notifications/unsubscribed/?$', 'index.php?civime_notif_route=unsubscribed', 'top' );
	}

	public function load_template( string $template ): string {
		$route = get_query_var( 'civime_notif_route' );

		$route_template_map = [
			'subscribe'    => CIVIME_NOTIFICATIONS_PATH . 'templates/subscribe.php',
			'manage'       => CIVIME_NOTIFICATIONS_PATH . 'templates/manage.php',
			'confirmed'    => CIVIME_NOTIFICATIONS_PATH . 'templates/confirmed.php',
			'unsubscribed' => CIVIME_NOTIFICATIONS_PATH . 'templates/unsubscribed.php',
		];

		if ( isset( $route_template_map[ $route ] ) && file_exists( $route_template_map[ $route ] ) ) {
			return $route_template_map[ $route ];
		}

		return $template;
	}

	public function set_200_status(): void {
		if ( ! get_query_var( 'civime_notif_route' ) ) {
			return;
		}

		status_header( 200 );

		global $wp_query;
		$wp_query->is_404 = false;
	}

	public function set_page_title( array $title ): array {
		$route = get_query_var( 'civime_notif_route' );

		$titles = [
			'subscribe'    => __( 'Get Notified', 'civime-notifications' ),
			'manage'       => __( 'Manage Notifications', 'civime-notifications' ),
			'confirmed'    => __( 'Subscription Confirmed', 'civime-notifications' ),
			'unsubscribed' => __( 'Unsubscribed', 'civime-notifications' ),
		];

		if ( isset( $titles[ $route ] ) ) {
			$title['title'] = $titles[ $route ];
		}

		return $title;
	}

	public function add_body_classes( array $classes ): array {
		$route = get_query_var( 'civime_notif_route' );

		if ( ! $route ) {
			return $classes;
		}

		$classes[] = 'civime-notifications-page';

		$route_class_map = [
			'subscribe'    => 'civime-subscribe',
			'manage'       => 'civime-manage',
			'confirmed'    => 'civime-confirmed',
			'unsubscribed' => 'civime-unsubscribed',
		];

		if ( isset( $route_class_map[ $route ] ) ) {
			$classes[] = $route_class_map[ $route ];
		}

		return $classes;
	}
}
