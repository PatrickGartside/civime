<?php
/**
 * CiviMe Meetings Router
 *
 * Registers rewrite rules and dispatches virtual URLs to plugin templates.
 *
 * @package CiviMe_Meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Meetings_Router {

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
		$vars[] = 'civime_meeting_id';
		$vars[] = 'civime_council_slug';
		$vars[] = 'civime_notif_route';
		return $vars;
	}

	public function register_rewrite_rules(): void {
		// Rules are stored in registration order within extra_rules_top.
		// Specific literal patterns must come before catch-all wildcards.
		add_rewrite_rule( '^councils/([^/]+)/?$', 'index.php?civime_route=council-profile&civime_council_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^councils/?$', 'index.php?civime_route=councils-list', 'top' );
		add_rewrite_rule( '^meetings/subscribe/?$', 'index.php?civime_notif_route=subscribe', 'top' );
		add_rewrite_rule( '^meetings/([^/]+)/ics/?$', 'index.php?civime_route=meeting-ics&civime_meeting_id=$matches[1]', 'top' );
		add_rewrite_rule( '^meetings/([^/]+)/?$', 'index.php?civime_route=meeting-detail&civime_meeting_id=$matches[1]', 'top' );
		add_rewrite_rule( '^meetings/?$', 'index.php?civime_route=meetings-list', 'top' );
	}

	public function load_template( string $template ): string {
		$route = get_query_var( 'civime_route' );

		$route_template_map = [
			'meetings-list'   => CIVIME_MEETINGS_PATH . 'templates/meetings-list.php',
			'meeting-detail'  => CIVIME_MEETINGS_PATH . 'templates/meeting-detail.php',
			'councils-list'   => CIVIME_MEETINGS_PATH . 'templates/councils-list.php',
			'council-profile' => CIVIME_MEETINGS_PATH . 'templates/council-profile.php',
		];

		if ( 'meeting-ics' === $route ) {
			$this->serve_ics();
			exit;
		}

		if ( isset( $route_template_map[ $route ] ) && file_exists( $route_template_map[ $route ] ) ) {
			return $route_template_map[ $route ];
		}

		return $template;
	}

	public function set_200_status(): void {
		if ( ! get_query_var( 'civime_route' ) ) {
			return;
		}

		status_header( 200 );

		global $wp_query;
		$wp_query->is_404 = false;
	}

	public function set_page_title( array $title ): array {
		$route = get_query_var( 'civime_route' );

		$titles = [
			'meetings-list'   => __( 'Meetings', 'civime-meetings' ),
			'meeting-detail'  => __( 'Meeting Detail', 'civime-meetings' ),
			'councils-list'   => __( 'Councils', 'civime-meetings' ),
			'council-profile' => __( 'Council Profile', 'civime-meetings' ),
		];

		if ( isset( $titles[ $route ] ) ) {
			$title['title'] = $titles[ $route ];
		}

		return $title;
	}

	/**
	 * Proxy an ICS file download from the Access100 API.
	 *
	 * Fetches the file server-side (with the API key) and streams it
	 * to the browser as a .ics download.
	 */
	private function serve_ics(): void {
		$raw       = get_query_var( 'civime_meeting_id', '' );
		$state_id  = preg_match( '/^[a-zA-Z0-9_-]{1,64}$/', $raw ) ? $raw : '';

		if ( '' === $state_id ) {
			status_header( 404 );
			echo 'Meeting not found.';
			return;
		}

		$api      = civime_api();
		$api_url  = $api->get_meeting_ics_url( $state_id );
		$response = wp_remote_get( $api_url, [
			'timeout' => 15,
			'headers' => [
				'X-API-Key' => civime_get_option( 'civime_api_key', '' ),
			],
		] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			status_header( 502 );
			echo 'Calendar file is temporarily unavailable.';
			return;
		}

		$body = wp_remote_retrieve_body( $response );

		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="meeting-' . $state_id . '.ics"' );
		header( 'Content-Length: ' . strlen( $body ) );
		echo $body;
	}

	public function add_body_classes( array $classes ): array {
		$route = get_query_var( 'civime_route' );

		if ( ! $route ) {
			return $classes;
		}

		$classes[] = 'civime-meetings-page';

		$route_class_map = [
			'meetings-list'   => 'civime-meetings-list',
			'meeting-detail'  => 'civime-meeting-detail',
			'councils-list'   => 'civime-councils-list',
			'council-profile' => 'civime-council-profile',
		];

		if ( isset( $route_class_map[ $route ] ) ) {
			$classes[] = $route_class_map[ $route ];
		}

		return $classes;
	}
}
