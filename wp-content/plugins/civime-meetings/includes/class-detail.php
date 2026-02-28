<?php
/**
 * Meeting Detail Controller
 *
 * Fetches a single meeting from the Access100 API and exposes
 * sanitised data for the meeting-detail template.
 *
 * @package CiviMe_Meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Meetings_Detail {

	private string $state_id;
	private array|WP_Error $meeting;
	private string $ics_url = '';

	public function __construct() {
		$raw = get_query_var( 'civime_meeting_id', '' );

		// Accept only alphanumeric characters, hyphens, and underscores (max 64 chars).
		// Reject anything else to prevent path traversal or encoded-slash attacks against the API.
		$this->state_id = preg_match( '/^[a-zA-Z0-9_-]{1,64}$/', $raw ) ? $raw : '';

		$this->fetch_data();
	}

	/**
	 * Pull a single meeting from the API.
	 *
	 * A missing state_id is treated as a 404 immediately so the template
	 * never sends a malformed request to the upstream server.
	 */
	private function fetch_data(): void {
		if ( '' === $this->state_id ) {
			$this->meeting = new WP_Error( '404', __( 'No meeting ID provided.', 'civime-meetings' ) );
			return;
		}

		$response = civime_api()->get_meeting( $this->state_id );

		if ( is_wp_error( $response ) ) {
			$this->meeting = $response;
			error_log( 'CiviMe Meetings detail API error [' . str_replace( [ "\n", "\r" ], '', $this->state_id ) . ']: ' . $response->get_error_message() );
			return;
		}

		$this->meeting = CiviMe_Meetings_Data_Mapper::map_meeting_detail( $response['data'] ?? [] );

		$this->ics_url = home_url( '/meetings/' . rawurlencode( $this->state_id ) . '/ics/' );
	}

	/**
	 * Return the flat meeting data array.
	 *
	 * Returns an empty array on any error state so templates can safely
	 * access keys without additional is_wp_error() guards.
	 */
	public function get_meeting(): array {
		return is_wp_error( $this->meeting ) ? [] : $this->meeting;
	}

	public function get_state_id(): string {
		return $this->state_id;
	}

	public function get_ics_url(): string {
		return $this->ics_url;
	}

	public function has_error(): bool {
		return is_wp_error( $this->meeting );
	}

	/**
	 * True when the API explicitly signalled that this meeting does not exist.
	 *
	 * Checks both the primary error code and any additional codes so that
	 * upstream errors of the form "http_404" or "not_found_404" also match.
	 */
	public function is_not_found(): bool {
		if ( ! is_wp_error( $this->meeting ) ) {
			return false;
		}

		foreach ( $this->meeting->get_error_codes() as $code ) {
			if ( str_contains( (string) $code, '404' ) || str_contains( (string) $code, 'not_found' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return a user-safe error message, never leaking raw API details.
	 */
	public function get_error_message(): string {
		if ( ! is_wp_error( $this->meeting ) ) {
			return '';
		}

		return __( 'Meeting data is temporarily unavailable. Please check back soon.', 'civime-meetings' );
	}

	/**
	 * Build the <title> string from council name and formatted date.
	 *
	 * Used by the document_title_parts filter wired up inside the template.
	 * Falls back gracefully when either field is missing.
	 */
	public function get_page_title(): string {
		$meeting = $this->get_meeting();

		if ( empty( $meeting ) ) {
			return __( 'Meeting Detail', 'civime-meetings' );
		}

		$council_name = $meeting['council_name'] ?? '';
		$date_string  = $meeting['date'] ?? '';

		if ( '' === $council_name && '' === $date_string ) {
			return __( 'Meeting Detail', 'civime-meetings' );
		}

		if ( '' === $date_string ) {
			return $council_name;
		}

		// wp_date() respects the site timezone setting.
		$formatted_date = wp_date( 'F j, Y', strtotime( $date_string ) );

		if ( '' === $council_name ) {
			return $formatted_date;
		}

		return $council_name . ' — ' . $formatted_date;
	}
}
