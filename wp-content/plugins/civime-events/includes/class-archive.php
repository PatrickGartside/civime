<?php
/**
 * CiviMe Events — Archive Controller
 *
 * @package CiviMe_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Events_Archive {

	/** @var WP_Term|null */
	private ?WP_Term $active_type = null;

	/** @var WP_Term[] */
	private array $types = [];

	private string $active_island = '';

	private bool $showing_past = false;

	public function __construct() {
		$queried = get_queried_object();

		if ( $queried instanceof WP_Term && 'event_type' === $queried->taxonomy ) {
			$this->active_type = $queried;
		}

		$terms = get_terms( [
			'taxonomy'   => 'event_type',
			'hide_empty' => false,
		] );

		$this->types = is_array( $terms ) ? $terms : [];

		// Island filter.
		$island = isset( $_GET['island'] ) ? sanitize_text_field( wp_unslash( $_GET['island'] ) ) : '';
		$valid  = self::get_valid_islands();
		if ( isset( $valid[ $island ] ) ) {
			$this->active_island = $island;
		}

		// Past/upcoming toggle.
		$show = isset( $_GET['show'] ) ? sanitize_text_field( wp_unslash( $_GET['show'] ) ) : '';
		$this->showing_past = ( 'past' === $show );
	}

	/**
	 * @return array<string, string> slug => label
	 */
	public static function get_valid_islands(): array {
		return [
			'oahu'          => 'O&#x02BB;ahu',
			'maui'          => 'Maui',
			'hawaii-island' => 'Hawai&#x02BB;i Island',
			'kauai'         => 'Kaua&#x02BB;i',
			'online'        => 'Online',
		];
	}

	public function get_active_type(): ?WP_Term {
		return $this->active_type;
	}

	/**
	 * @return WP_Term[]
	 */
	public function get_types(): array {
		return $this->types;
	}

	public function get_active_island(): string {
		return $this->active_island;
	}

	public function is_showing_past(): bool {
		return $this->showing_past;
	}

	/**
	 * Format an event date for display.
	 */
	public static function format_event_date( string $date_string ): string {
		if ( '' === $date_string ) {
			return '';
		}
		return wp_date( 'l, F j, Y', strtotime( $date_string ) );
	}

	/**
	 * Format an event time for display.
	 */
	public static function format_event_time( string $time_string, string $end_time = '' ): string {
		if ( '' === $time_string ) {
			return '';
		}

		$formatted = wp_date( 'g:i A', strtotime( $time_string ) );

		if ( '' !== $end_time ) {
			$formatted .= " \u{2013} " . wp_date( 'g:i A', strtotime( $end_time ) );
		}

		return $formatted;
	}

	/**
	 * Get the island label from its slug.
	 */
	public static function get_island_label( string $slug ): string {
		$islands = self::get_valid_islands();
		return $islands[ $slug ] ?? '';
	}
}
