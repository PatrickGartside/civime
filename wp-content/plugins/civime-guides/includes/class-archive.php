<?php
/**
 * CiviMe Guides — Archive Controller
 *
 * @package CiviMe_Guides
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Guides_Archive {

	/** @var WP_Term|null */
	private ?WP_Term $active_category = null;

	/** @var WP_Term[] */
	private array $categories = [];

	public function __construct() {
		$queried = get_queried_object();

		if ( $queried instanceof WP_Term && 'guide_category' === $queried->taxonomy ) {
			$this->active_category = $queried;
		}

		$terms = get_terms( [
			'taxonomy'   => 'guide_category',
			'hide_empty' => false,
		] );

		$this->categories = is_array( $terms ) ? $terms : [];
	}

	public function get_active_category(): ?WP_Term {
		return $this->active_category;
	}

	/**
	 * @return WP_Term[]
	 */
	public function get_categories(): array {
		return $this->categories;
	}

	public function get_current_page(): int {
		return max( 1, (int) get_query_var( 'paged', 1 ) );
	}
}
