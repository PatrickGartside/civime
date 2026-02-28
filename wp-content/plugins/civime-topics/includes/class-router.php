<?php
/**
 * CiviMe Topics Router
 *
 * Registers rewrite rules and dispatches virtual URLs to plugin templates.
 *
 * @package CiviMe_Topics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Topics_Router {

	public function __construct() {
		add_action( 'init', [ $this, 'register_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
		add_filter( 'template_include', [ $this, 'load_template' ] );
		add_filter( 'document_title_parts', [ $this, 'set_page_title' ] );
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );
		add_action( 'wp', [ $this, 'set_200_status' ] );
	}

	public function register_query_vars( array $vars ): array {
		$vars[] = 'civime_route';
		$vars[] = 'civime_topic_slug';
		return $vars;
	}

	public function register_rewrite_rules(): void {
		add_rewrite_rule( '^what-matters/?$', 'index.php?civime_route=topic-picker', 'top' );
		add_rewrite_rule( '^topics/?$', 'index.php?civime_route=topic-picker', 'top' );
		add_rewrite_rule( '^topics/([^/]+)/?$', 'index.php?civime_route=topic-page&civime_topic_slug=$matches[1]', 'top' );
	}

	public function load_template( string $template ): string {
		$route = get_query_var( 'civime_route' );

		$route_template_map = [
			'topic-picker' => CIVIME_TOPICS_PATH . 'templates/topic-picker.php',
			'topic-page'   => CIVIME_TOPICS_PATH . 'templates/topic-page.php',
		];

		if ( isset( $route_template_map[ $route ] ) && file_exists( $route_template_map[ $route ] ) ) {
			return $route_template_map[ $route ];
		}

		return $template;
	}

	public function set_200_status(): void {
		$route = get_query_var( 'civime_route' );

		if ( ! in_array( $route, [ 'topic-picker', 'topic-page' ], true ) ) {
			return;
		}

		status_header( 200 );

		global $wp_query;
		$wp_query->is_404 = false;
	}

	public function set_page_title( array $title ): array {
		$route = get_query_var( 'civime_route' );

		if ( 'topic-picker' === $route ) {
			$title['title'] = __( 'What Matters to Me', 'civime-topics' );
		} elseif ( 'topic-page' === $route ) {
			$slug = get_query_var( 'civime_topic_slug' );
			$title['title'] = ucwords( str_replace( '-', ' ', $slug ) ) . ' — ' . __( 'Topics', 'civime-topics' );
		}

		return $title;
	}

	public function add_body_classes( array $classes ): array {
		$route = get_query_var( 'civime_route' );

		if ( 'topic-picker' === $route ) {
			$classes[] = 'civime-topics-page';
			$classes[] = 'civime-topic-picker';
		} elseif ( 'topic-page' === $route ) {
			$classes[] = 'civime-topics-page';
			$classes[] = 'civime-topic-detail';
		}

		return $classes;
	}
}
