<?php
/**
 * CiviMe Guides — Single Controller
 *
 * @package CiviMe_Guides
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Guides_Single {

	/** @var WP_Term[] */
	private array $categories = [];

	private int $reading_time = 1;

	/** @var WP_Post[] */
	private array $related = [];

	public function __construct() {
		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$terms = wp_get_post_terms( $post->ID, 'guide_category' );
		$this->categories = is_array( $terms ) ? $terms : [];

		$this->reading_time = CiviMe_Guides_Post_Type::reading_time( $post->post_content );

		$this->load_related( $post );
	}

	private function load_related( WP_Post $post ): void {
		if ( empty( $this->categories ) ) {
			return;
		}

		$term_ids = wp_list_pluck( $this->categories, 'term_id' );

		$query = new WP_Query( [
			'post_type'      => 'civime_guide',
			'posts_per_page' => 3,
			'post__not_in'   => [ $post->ID ],
			'tax_query'      => [
				[
					'taxonomy' => 'guide_category',
					'field'    => 'term_id',
					'terms'    => $term_ids,
				],
			],
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		] );

		$this->related = $query->posts;
	}

	/**
	 * @return WP_Term[]
	 */
	public function get_categories(): array {
		return $this->categories;
	}

	public function get_reading_time(): int {
		return $this->reading_time;
	}

	/**
	 * @return WP_Post[]
	 */
	public function get_related(): array {
		return $this->related;
	}
}
