<?php
/**
 * Controller for the councils list view.
 *
 * Fetches and filters council data from the Access100 API, isolating
 * all data-access logic from the template layer.
 *
 * @package CiviMe_Meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Meetings_Councils_List {

	private string $search = '';
	private string $county = '';
	private array|WP_Error $councils;

	public function __construct() {
		$this->search  = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
		$this->county  = $this->sanitize_county( $_GET['county'] ?? '' );
		$this->fetch_data();
	}

	/**
	 * Rejects any county value not in the explicit whitelist to prevent
	 * arbitrary strings from reaching the API query.
	 */
	private function sanitize_county( string $value ): string {
		$allowed = [ 'state', 'honolulu', 'maui', 'hawaii', 'kauai' ];

		return in_array( $value, $allowed, true ) ? $value : '';
	}

	private function fetch_data(): void {
		$args = [];

		if ( $this->search !== '' ) {
			$args['q'] = $this->search;
		}

		if ( $this->county !== '' ) {
			$args['county'] = $this->county;
		}

		$this->councils = civime_api()->get_councils( $args );
	}

	/**
	 * Returns the council records, or an empty array when the API call failed.
	 * Callers should check has_error() to distinguish empty results from failures.
	 */
	public function get_councils(): array {
		if ( is_wp_error( $this->councils ) ) {
			return [];
		}

		return array_map(
			[ CiviMe_Meetings_Data_Mapper::class, 'map_council' ],
			$this->councils['data'] ?? []
		);
	}

	public function get_search(): string {
		return $this->search;
	}

	public function get_county(): string {
		return $this->county;
	}

	public function has_filters(): bool {
		return $this->search !== '' || $this->county !== '';
	}

	public function has_error(): bool {
		return is_wp_error( $this->councils );
	}

	public function get_error_message(): string {
		if ( ! is_wp_error( $this->councils ) ) {
			return '';
		}

		error_log( 'CiviMe Councils API error: ' . $this->councils->get_error_message() );

		return __( 'Council data is temporarily unavailable. Please check back soon.', 'civime-meetings' );
	}
}
