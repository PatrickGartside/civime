<?php
/**
 * CiviMe Events — Post Type & Taxonomy Registration
 *
 * @package CiviMe_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Events_Post_Type {

	public function __construct() {
		$this->register();
		add_filter( 'template_include', [ $this, 'load_template' ] );
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );
		add_action( 'pre_get_posts', [ $this, 'modify_archive_query' ] );
	}

	public function register(): void {
		$this->register_post_type();
		$this->register_taxonomy();
	}

	private function register_post_type(): void {
		$labels = [
			'name'               => __( 'Events', 'civime-events' ),
			'singular_name'      => __( 'Event', 'civime-events' ),
			'add_new'            => __( 'Add New Event', 'civime-events' ),
			'add_new_item'       => __( 'Add New Event', 'civime-events' ),
			'edit_item'          => __( 'Edit Event', 'civime-events' ),
			'new_item'           => __( 'New Event', 'civime-events' ),
			'view_item'          => __( 'View Event', 'civime-events' ),
			'search_items'       => __( 'Search Events', 'civime-events' ),
			'not_found'          => __( 'No events found.', 'civime-events' ),
			'not_found_in_trash' => __( 'No events found in Trash.', 'civime-events' ),
			'all_items'          => __( 'All Events', 'civime-events' ),
			'archives'           => __( 'Event Archives', 'civime-events' ),
			'menu_name'          => __( 'Events', 'civime-events' ),
		];

		register_post_type( 'civime_event', [
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => true,
			'rewrite'       => [ 'slug' => 'events', 'with_front' => false ],
			'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ],
			'menu_icon'     => 'dashicons-calendar-alt',
			'menu_position' => 26,
			'show_in_rest'  => true,
		] );
	}

	private function register_taxonomy(): void {
		$labels = [
			'name'          => __( 'Event Types', 'civime-events' ),
			'singular_name' => __( 'Event Type', 'civime-events' ),
			'search_items'  => __( 'Search Types', 'civime-events' ),
			'all_items'     => __( 'All Types', 'civime-events' ),
			'parent_item'   => __( 'Parent Type', 'civime-events' ),
			'edit_item'     => __( 'Edit Type', 'civime-events' ),
			'update_item'   => __( 'Update Type', 'civime-events' ),
			'add_new_item'  => __( 'Add New Type', 'civime-events' ),
			'new_item_name' => __( 'New Type Name', 'civime-events' ),
			'menu_name'     => __( 'Event Types', 'civime-events' ),
		];

		register_taxonomy( 'event_type', 'civime_event', [
			'labels'       => $labels,
			'hierarchical' => true,
			'public'       => true,
			'rewrite'      => [ 'slug' => 'events/type', 'with_front' => false ],
			'show_in_rest' => true,
		] );
	}

	/**
	 * Seed default taxonomy terms on plugin activation.
	 */
	public static function seed_terms(): void {
		$defaults = [
			'Letter Writing Party',
			'Info Session',
			'Ambassador Meetup',
			'Town Hall',
			'Community Forum',
		];

		foreach ( $defaults as $term ) {
			if ( ! term_exists( $term, 'event_type' ) ) {
				wp_insert_term( $term, 'event_type' );
			}
		}
	}

	/**
	 * Modify the main archive query to order by event date and filter by upcoming/past/island.
	 */
	public function modify_archive_query( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( 'civime_event' ) && ! $query->is_tax( 'event_type' ) ) {
			return;
		}

		$today    = current_time( 'Y-m-d' );
		$show     = isset( $_GET['show'] ) ? sanitize_text_field( wp_unslash( $_GET['show'] ) ) : '';
		$island   = isset( $_GET['island'] ) ? sanitize_text_field( wp_unslash( $_GET['island'] ) ) : '';

		$meta_query = [];

		if ( 'past' === $show ) {
			$meta_query[] = [
				'key'     => '_civime_event_date',
				'value'   => $today,
				'compare' => '<',
				'type'    => 'DATE',
			];
			$query->set( 'order', 'DESC' );
		} else {
			$meta_query[] = [
				'key'     => '_civime_event_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			];
			$query->set( 'order', 'ASC' );
		}

		$valid_islands = CiviMe_Events_Archive::get_valid_islands();
		if ( '' !== $island && isset( $valid_islands[ $island ] ) ) {
			$meta_query[] = [
				'key'     => '_civime_event_island',
				'value'   => $island,
				'compare' => '=',
			];
		}

		$query->set( 'meta_query', $meta_query );
		$query->set( 'meta_key', '_civime_event_date' );
		$query->set( 'orderby', 'meta_value' );
	}

	public function load_template( string $template ): string {
		if ( is_post_type_archive( 'civime_event' ) || is_tax( 'event_type' ) ) {
			$plugin_template = CIVIME_EVENTS_PATH . 'templates/archive-event.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		if ( is_singular( 'civime_event' ) ) {
			$plugin_template = CIVIME_EVENTS_PATH . 'templates/single-event.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	public function add_body_classes( array $classes ): array {
		if ( is_post_type_archive( 'civime_event' ) || is_tax( 'event_type' ) ) {
			$classes[] = 'civime-events-page';
			$classes[] = 'civime-events-archive';
		}

		if ( is_singular( 'civime_event' ) ) {
			$classes[] = 'civime-events-page';
			$classes[] = 'civime-events-single';
		}

		return $classes;
	}
}
