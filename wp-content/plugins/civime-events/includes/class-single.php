<?php
/**
 * CiviMe Events — Single Controller
 *
 * @package CiviMe_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Events_Single {

	/** @var array<string, string> */
	private array $meta = [];

	private string $google_calendar_url = '';

	/** @var WP_Term[] */
	private array $types = [];

	/** @var WP_Post[] */
	private array $related = [];

	public function __construct() {
		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$this->meta = [
			'date'                  => (string) get_post_meta( $post->ID, '_civime_event_date', true ),
			'time'                  => (string) get_post_meta( $post->ID, '_civime_event_time', true ),
			'end_time'              => (string) get_post_meta( $post->ID, '_civime_event_end_time', true ),
			'location'              => (string) get_post_meta( $post->ID, '_civime_event_location', true ),
			'island'                => (string) get_post_meta( $post->ID, '_civime_event_island', true ),
			'url'                   => (string) get_post_meta( $post->ID, '_civime_event_url', true ),
			'registration_required' => (string) get_post_meta( $post->ID, '_civime_event_registration_required', true ),
		];

		$terms = wp_get_post_terms( $post->ID, 'event_type' );
		$this->types = is_array( $terms ) ? $terms : [];

		$this->build_google_calendar_url( $post );
		$this->load_related( $post );
	}

	private function build_google_calendar_url( WP_Post $post ): void {
		if ( '' === $this->meta['date'] ) {
			return;
		}

		$date_clean = str_replace( '-', '', $this->meta['date'] );

		// Start time.
		$start_datetime = $date_clean;
		if ( '' !== $this->meta['time'] ) {
			$start_datetime = $date_clean . 'T' . str_replace( ':', '', $this->meta['time'] ) . '00';
		}

		// End time — default to 1 hour after start if no end time provided.
		$end_datetime = $date_clean;
		if ( '' !== $this->meta['end_time'] ) {
			$end_datetime = $date_clean . 'T' . str_replace( ':', '', $this->meta['end_time'] ) . '00';
		} elseif ( '' !== $this->meta['time'] ) {
			$start_ts     = strtotime( $this->meta['date'] . ' ' . $this->meta['time'] );
			$end_ts       = $start_ts + 3600;
			$end_datetime = gmdate( 'Ymd\THis', $end_ts );
		}

		$dates = $start_datetime . '/' . $end_datetime;

		$params = [
			'action'   => 'TEMPLATE',
			'text'     => $post->post_title,
			'dates'    => $dates,
			'location' => $this->meta['location'],
			'details'  => wp_strip_all_tags( $post->post_excerpt ?: wp_trim_words( $post->post_content, 50 ) ),
		];

		$this->google_calendar_url = 'https://calendar.google.com/calendar/render?' . http_build_query( $params );
	}

	private function load_related( WP_Post $post ): void {
		if ( empty( $this->types ) ) {
			return;
		}

		$today    = current_time( 'Y-m-d' );
		$term_ids = wp_list_pluck( $this->types, 'term_id' );

		$query = new WP_Query( [
			'post_type'      => 'civime_event',
			'posts_per_page' => 3,
			'post__not_in'   => [ $post->ID ],
			'tax_query'      => [
				[
					'taxonomy' => 'event_type',
					'field'    => 'term_id',
					'terms'    => $term_ids,
				],
			],
			'meta_query'     => [
				[
					'key'     => '_civime_event_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				],
			],
			'meta_key'       => '_civime_event_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		] );

		$this->related = $query->posts;
	}

	/**
	 * @return array<string, string>
	 */
	public function get_meta(): array {
		return $this->meta;
	}

	public function get_google_calendar_url(): string {
		return $this->google_calendar_url;
	}

	/**
	 * @return WP_Term[]
	 */
	public function get_types(): array {
		return $this->types;
	}

	/**
	 * @return WP_Post[]
	 */
	public function get_related(): array {
		return $this->related;
	}
}
