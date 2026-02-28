<?php
/**
 * Meetings List Controller
 *
 * Prepares filtered, paginated meeting data for the meetings-list template.
 *
 * @package CiviMe_Meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Meetings_List {

	private array $filters       = [];
	private array $all_topics    = [];
	private array|WP_Error $meetings = [];
	private array|WP_Error $councils = [];
	private int $current_page    = 1;
	private int $per_page        = 20;
	private int $total           = 0;

	private const VALID_COUNTIES = [ 'state', 'honolulu', 'maui', 'hawaii', 'kauai' ];

	public function __construct() {
		$this->parse_filters();
		$this->fetch_data();
	}

	/**
	 * Read and sanitize supported $_GET filter parameters.
	 *
	 * Each filter is validated to its expected type/format so the template
	 * and API args are always safe to use without additional escaping logic.
	 */
	private function parse_filters(): void {
		$raw_page   = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
		$date_from  = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to    = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
		$raw_county = isset( $_GET['county'] ) ? sanitize_key( $_GET['county'] ) : '';

		$this->current_page = max( 1, $raw_page );

		// Parse topic slugs from comma-separated query param.
		$topic_slugs = [];
		if ( ! empty( $_GET['topics'] ) ) {
			$raw_topics = explode( ',', sanitize_text_field( wp_unslash( $_GET['topics'] ) ) );
			foreach ( $raw_topics as $ts ) {
				$ts = trim( $ts );
				if ( preg_match( '/^[a-z0-9-]{1,50}$/', $ts ) ) {
					$topic_slugs[] = $ts;
				}
			}
		}

		$this->filters = [
			'q'          => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
			'council_id' => isset( $_GET['council_id'] ) ? absint( $_GET['council_id'] ) : 0,
			'date_from'  => $this->validate_date_string( $date_from ),
			'date_to'    => $this->validate_date_string( $date_to ),
			'county'     => in_array( $raw_county, self::VALID_COUNTIES, true ) ? $raw_county : '',
			'topics'     => $topic_slugs,
		];
	}

	/**
	 * Return the date string only when it matches Y-m-d, otherwise empty string.
	 *
	 * Prevents malformed dates from reaching the API or being echoed into HTML.
	 */
	private function validate_date_string( string $candidate ): string {
		if ( '' === $candidate ) {
			return '';
		}

		$parsed = DateTime::createFromFormat( 'Y-m-d', $candidate );

		return ( $parsed && $parsed->format( 'Y-m-d' ) === $candidate ) ? $candidate : '';
	}

	/**
	 * Fetch meetings (with pagination/filters) and councils (for the dropdown).
	 *
	 * Only non-empty filter values are forwarded to the API so the backend
	 * can treat absent keys as "no filter applied".
	 */
	private function fetch_data(): void {
		$args = [
			'limit'  => $this->per_page,
			'offset' => ( $this->current_page - 1 ) * $this->per_page,
		];

		if ( '' !== $this->filters['q'] ) {
			$args['q'] = $this->filters['q'];
		}

		if ( $this->filters['council_id'] > 0 ) {
			$args['council_id'] = $this->filters['council_id'];
		}

		if ( '' !== $this->filters['date_from'] ) {
			$args['date_from'] = $this->filters['date_from'];
		}

		if ( '' !== $this->filters['date_to'] ) {
			$args['date_to'] = $this->filters['date_to'];
		}

		if ( '' !== $this->filters['county'] ) {
			$args['county'] = $this->filters['county'];
		}

		if ( ! empty( $this->filters['topics'] ) ) {
			$args['topics'] = implode( ',', $this->filters['topics'] );
		}

		$meetings_response = civime_api()->get_meetings( $args );

		if ( is_wp_error( $meetings_response ) ) {
			$this->meetings = $meetings_response;
		} else {
			$this->meetings = array_map(
				[ CiviMe_Meetings_Data_Mapper::class, 'map_meeting_list_item' ],
				$meetings_response['data'] ?? []
			);
			$this->total    = (int) ( $meetings_response['meta']['total'] ?? 0 );
		}

		// Councils are fetched without filters — the dropdown always shows all options.
		$councils_response = civime_api()->get_councils( [] );

		if ( is_wp_error( $councils_response ) ) {
			$this->councils = $councils_response;
		} else {
			$this->councils = array_map(
				[ CiviMe_Meetings_Data_Mapper::class, 'map_council' ],
				$councils_response['data'] ?? []
			);
		}

		// Fetch all topics for the topic picker and active filter display.
		$topics_response = civime_api()->get_topics();
		if ( ! is_wp_error( $topics_response ) ) {
			$this->all_topics = $topics_response['data'] ?? [];
		}
	}

	/**
	 * Return the flat meetings array, or an empty array when the API failed.
	 */
	public function get_meetings(): array {
		return is_wp_error( $this->meetings ) ? [] : $this->meetings;
	}

	/**
	 * Return the councils array for populating the filter dropdown.
	 */
	public function get_councils(): array {
		return is_wp_error( $this->councils ) ? [] : $this->councils;
	}

	public function get_filters(): array {
		return $this->filters;
	}

	/**
	 * Return all topics for the topic picker.
	 */
	public function get_all_topics(): array {
		return $this->all_topics;
	}

	/**
	 * Return topic metadata for the currently active topic filter.
	 *
	 * @return array Array of topic objects with slug, name, icon, etc.
	 */
	public function get_active_topics(): array {
		if ( empty( $this->filters['topics'] ) ) {
			return [];
		}

		return array_values( array_filter( $this->all_topics, function ( array $t ): bool {
			return in_array( $t['slug'] ?? '', $this->filters['topics'], true );
		} ) );
	}

	public function get_current_page(): int {
		return $this->current_page;
	}

	public function get_per_page(): int {
		return $this->per_page;
	}

	public function get_total_count(): int {
		return $this->total;
	}

	public function get_total_pages(): int {
		if ( $this->per_page <= 0 ) {
			return 1;
		}

		return (int) max( 1, ceil( $this->total / $this->per_page ) );
	}

	/**
	 * True when the meetings API call returned a WP_Error — councils errors are
	 * non-fatal since they only affect the filter dropdown.
	 */
	public function has_error(): bool {
		return is_wp_error( $this->meetings );
	}

	/**
	 * Return a user-safe message that avoids leaking internal API details.
	 */
	public function get_error_message(): string {
		if ( ! is_wp_error( $this->meetings ) ) {
			return '';
		}

		// Log the real error for operators but show a generic message to visitors.
		error_log( 'CiviMe Meetings API error: ' . $this->meetings->get_error_message() );

		return __( 'Meeting data is temporarily unavailable. Please check back soon.', 'civime-meetings' );
	}

	/**
	 * Group the flat meetings array by date for chronological section headings.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_meetings_grouped_by_date(): array {
		$grouped = [];

		foreach ( $this->get_meetings() as $meeting ) {
			$date = $meeting['date'] ?? '';

			if ( '' === $date ) {
				continue;
			}

			$grouped[ $date ][] = $meeting;
		}

		// Dates arrive from the API in ascending order, but sort defensively
		// so the display is always chronological regardless of API ordering.
		ksort( $grouped );

		return $grouped;
	}
}
