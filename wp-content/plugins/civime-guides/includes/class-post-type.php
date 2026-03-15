<?php
/**
 * CiviMe Guides — Post Type & Taxonomy Registration
 *
 * @package CiviMe_Guides
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Guides_Post_Type {

	public function __construct() {
		$this->register();
		add_filter( 'template_include', [ $this, 'load_template' ] );
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_guides_by_locale' ] );
	}

	public function register(): void {
		$this->register_taxonomy();
		$this->register_post_type();
	}

	private function register_post_type(): void {
		$labels = [
			'name'               => __( 'Guides', 'civime-guides' ),
			'singular_name'      => __( 'Guide', 'civime-guides' ),
			'add_new'            => __( 'Add New Guide', 'civime-guides' ),
			'add_new_item'       => __( 'Add New Guide', 'civime-guides' ),
			'edit_item'          => __( 'Edit Guide', 'civime-guides' ),
			'new_item'           => __( 'New Guide', 'civime-guides' ),
			'view_item'          => __( 'View Guide', 'civime-guides' ),
			'search_items'       => __( 'Search Guides', 'civime-guides' ),
			'not_found'          => __( 'No guides found.', 'civime-guides' ),
			'not_found_in_trash' => __( 'No guides found in Trash.', 'civime-guides' ),
			'all_items'          => __( 'All Guides', 'civime-guides' ),
			'archives'           => __( 'Guide Archives', 'civime-guides' ),
			'menu_name'          => __( 'Guides', 'civime-guides' ),
		];

		register_post_type( 'civime_guide', [
			'labels'          => $labels,
			'public'          => true,
			'has_archive'     => true,
			'rewrite'         => [ 'slug' => 'guides', 'with_front' => false ],
			'supports'        => [ 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ],
			'menu_icon'       => 'dashicons-book-alt',
			'menu_position'   => 25,
			'show_in_rest'    => true,
			'capability_type' => [ 'guide', 'guides' ],
			'map_meta_cap'    => true,
		] );
	}

	private function register_taxonomy(): void {
		$labels = [
			'name'          => __( 'Guide Categories', 'civime-guides' ),
			'singular_name' => __( 'Guide Category', 'civime-guides' ),
			'search_items'  => __( 'Search Categories', 'civime-guides' ),
			'all_items'     => __( 'All Categories', 'civime-guides' ),
			'parent_item'   => __( 'Parent Category', 'civime-guides' ),
			'edit_item'     => __( 'Edit Category', 'civime-guides' ),
			'update_item'   => __( 'Update Category', 'civime-guides' ),
			'add_new_item'  => __( 'Add New Category', 'civime-guides' ),
			'new_item_name' => __( 'New Category Name', 'civime-guides' ),
			'menu_name'     => __( 'Categories', 'civime-guides' ),
		];

		register_taxonomy( 'guide_category', 'civime_guide', [
			'labels'       => $labels,
			'hierarchical' => true,
			'public'       => true,
			'rewrite'      => [ 'slug' => 'guides/category', 'with_front' => false ],
			'show_in_rest' => true,
		] );
	}

	/**
	 * Seed default taxonomy terms on plugin activation.
	 */
	public static function seed_terms(): void {
		$defaults = [
			'Testimony',
			'Voting & Elections',
			'Advocacy',
			'Getting Started',
			'Compliance',
		];

		foreach ( $defaults as $term ) {
			if ( ! term_exists( $term, 'guide_category' ) ) {
				wp_insert_term( $term, 'guide_category' );
			}
		}
	}

	public function load_template( string $template ): string {
		if ( is_post_type_archive( 'civime_guide' ) || is_tax( 'guide_category' ) ) {
			$plugin_template = CIVIME_GUIDES_PATH . 'templates/archive-guide.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		if ( is_singular( 'civime_guide' ) ) {
			$plugin_template = CIVIME_GUIDES_PATH . 'templates/single-guide.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	public function add_body_classes( array $classes ): array {
		if ( is_post_type_archive( 'civime_guide' ) || is_tax( 'guide_category' ) ) {
			$classes[] = 'civime-guides-page';
			$classes[] = 'civime-guides-archive';
		}

		if ( is_singular( 'civime_guide' ) ) {
			$classes[] = 'civime-guides-page';
			$classes[] = 'civime-guides-single';
		}

		return $classes;
	}

	/**
	 * Filter guide archive queries by the active i18n locale.
	 *
	 * English (default): show guides without _civime_guide_lang meta.
	 * Other locales: show guides where _civime_guide_lang matches.
	 */
	public function filter_guides_by_locale( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( 'civime_guide' ) && ! $query->is_tax( 'guide_category' ) ) {
			return;
		}

		$locale = apply_filters( 'civime_i18n_active_slug', 'en' );

		if ( 'en' === $locale ) {
			$query->set( 'meta_query', [
				[
					'key'     => '_civime_guide_lang',
					'compare' => 'NOT EXISTS',
				],
			] );
		} else {
			$query->set( 'meta_query', [
				[
					'key'   => '_civime_guide_lang',
					'value' => $locale,
				],
			] );
		}
	}

	/**
	 * Estimate reading time in minutes.
	 */
	public static function reading_time( string $content ): int {
		$word_count = str_word_count( wp_strip_all_tags( $content ) );
		return max( 1, (int) ceil( $word_count / 200 ) );
	}
}
