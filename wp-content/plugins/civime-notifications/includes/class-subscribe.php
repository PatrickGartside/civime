<?php
/**
 * Subscribe Form Controller
 *
 * Handles both the GET (render form) and POST (process submission) for the
 * /meetings/subscribe page. On successful submission, redirects back with
 * a ?submitted=1 flag so the template shows a confirmation message.
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Notifications_Subscribe {

	private array|WP_Error $councils = [];
	private array $topics            = [];
	private array $topic_council_map = [];
	private array $errors            = [];
	private array $form_data         = [];
	private bool $submitted          = false;
	private bool $is_single_council  = false;
	private array $single_council    = [];

	private const VALID_CHANNELS    = [ 'email', 'sms' ];
	private const VALID_FREQUENCIES = [ 'immediate', 'daily', 'weekly' ];

	public function __construct() {
		$council_id = isset( $_GET['council_id'] ) ? absint( $_GET['council_id'] ) : 0;

		// Check for the post-redirect success flag.
		if ( '1' === sanitize_key( wp_unslash( $_GET['submitted'] ?? '' ) ) ) {
			$this->submitted = true;

			// Fetch single council so the success template can show the council name.
			if ( $council_id > 0 ) {
				$this->fetch_single_council( $council_id );
			}

			return;
		}

		// Handle POST submission before fetching display data.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$this->handle_post();

			// A successful submission redirects, so we only reach here on error.
		}

		$this->set_defaults();

		if ( $council_id > 0 ) {
			$this->fetch_single_council( $council_id );
		}

		// Fall back to full form if single-council fetch failed or no council_id.
		if ( ! $this->is_single_council ) {
			$this->fetch_councils();
			$this->fetch_topics();
		}
	}

	/**
	 * Process the subscribe form submission.
	 *
	 * Validates input, calls the Access100 API, and either redirects on
	 * success or populates $this->errors for the template to display.
	 */
	private function handle_post(): void {
		// Nonce verification.
		if ( ! isset( $_POST['_civime_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_civime_nonce'] ) ), 'civime_subscribe' ) ) {
			$this->errors[] = __( 'Security check failed. Please try again.', 'civime-notifications' );
			$this->preserve_form_data();
			return;
		}

		// Honeypot — bots fill hidden fields, humans don't.
		$honeypot = sanitize_text_field( wp_unslash( $_POST['website'] ?? '' ) );
		if ( '' !== $honeypot ) {
			// Silently pretend success so the bot doesn't retry.
			$this->do_success_redirect();
			return;
		}

		// Rate limiting — 5 attempts per 5 minutes per IP.
		$rate_key = 'civime_rl_subscribe_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
		$count    = (int) get_transient( $rate_key );
		if ( $count >= 5 ) {
			$this->errors[] = __( 'Too many requests. Please wait a moment and try again.', 'civime-notifications' );
			$this->preserve_form_data();
			return;
		}
		set_transient( $rate_key, $count + 1, 300 );

		$this->preserve_form_data();

		// --- Sanitize ---

		$raw_channels = isset( $_POST['channels'] ) && is_array( $_POST['channels'] )
			? array_map( 'sanitize_key', $_POST['channels'] )
			: [];

		$channels = array_values( array_intersect( $raw_channels, self::VALID_CHANNELS ) );

		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		$phone_raw = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
		$phone     = $this->normalize_phone( $phone_raw );

		$raw_council_ids = isset( $_POST['council_ids'] ) && is_array( $_POST['council_ids'] )
			? array_map( 'absint', $_POST['council_ids'] )
			: [];

		$council_ids = array_values( array_filter( $raw_council_ids, fn( int $id ) => $id > 0 ) );

		$frequency_raw = sanitize_key( $_POST['frequency'] ?? '' );
		$frequency     = in_array( $frequency_raw, self::VALID_FREQUENCIES, true ) ? $frequency_raw : '';

		// --- Validate ---

		if ( empty( $channels ) ) {
			$this->errors[] = __( 'Please select at least one notification channel (email or text message).', 'civime-notifications' );
		}

		if ( in_array( 'email', $channels, true ) && ! is_email( $email ) ) {
			$this->errors[] = __( 'Please enter a valid email address.', 'civime-notifications' );
		}

		if ( in_array( 'sms', $channels, true ) && '' === $phone ) {
			$this->errors[] = __( 'Please enter a valid US phone number (10 digits).', 'civime-notifications' );
		}

		if ( empty( $council_ids ) ) {
			$this->errors[] = __( 'Please select at least one council to follow.', 'civime-notifications' );
		}

		if ( '' === $frequency ) {
			$this->errors[] = __( 'Please select how often you want to be notified.', 'civime-notifications' );
		}

		if ( ! empty( $this->errors ) ) {
			return;
		}

		// --- Submit to API ---

		$payload = [
			'channels'    => $channels,
			'council_ids' => $council_ids,
			'frequency'   => $frequency,
		];

		if ( in_array( 'email', $channels, true ) ) {
			$payload['email'] = $email;
		}

		if ( in_array( 'sms', $channels, true ) ) {
			$payload['phone'] = $phone;
		}

		$result = civime_api()->create_subscription( $payload );

		if ( is_wp_error( $result ) ) {
			error_log( 'CiviMe subscribe API error: ' . $result->get_error_message() );
			$this->errors[] = __( 'Something went wrong. Please try again in a moment.', 'civime-notifications' );
			return;
		}

		$this->do_success_redirect();
	}

	/**
	 * Fetch a single council by ID for streamlined single-council mode.
	 *
	 * On failure (invalid ID, API down), leaves is_single_council false so
	 * the constructor falls back to the full form.
	 */
	private function fetch_single_council( int $council_id ): void {
		$response = civime_api()->get_council( $council_id );

		if ( is_wp_error( $response ) ) {
			return;
		}

		$this->is_single_council = true;
		$this->single_council    = $response;

		// Ensure the council is pre-selected in form data.
		if ( ! in_array( $council_id, $this->form_data['council_ids'] ?? [], true ) ) {
			$this->form_data['council_ids'][] = $council_id;
		}
	}

	/**
	 * Redirect to the same page with a success flag (POST-redirect-GET).
	 *
	 * Preserves council_id so the success page can show contextual messaging.
	 *
	 * @return never
	 */
	private function do_success_redirect(): void {
		$url = add_query_arg( 'submitted', '1', home_url( '/meetings/subscribe/' ) );

		$council_id = isset( $_GET['council_id'] ) ? absint( $_GET['council_id'] ) : 0;
		if ( $council_id > 0 ) {
			$url = add_query_arg( 'council_id', $council_id, $url );
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Save submitted values so the form can re-populate on validation error.
	 */
	private function preserve_form_data(): void {
		$this->form_data = [
			'email'       => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'phone'       => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'channels'    => isset( $_POST['channels'] ) && is_array( $_POST['channels'] )
				? array_map( 'sanitize_key', $_POST['channels'] )
				: [],
			'council_ids' => isset( $_POST['council_ids'] ) && is_array( $_POST['council_ids'] )
				? array_map( 'absint', $_POST['council_ids'] )
				: [],
			'frequency'   => sanitize_key( $_POST['frequency'] ?? '' ),
		];
	}

	/**
	 * Set default form values for the initial GET request.
	 *
	 * Respects a ?council_id parameter so "Get Notified" links on meeting
	 * and council pages can pre-select the relevant council.
	 */
	private function set_defaults(): void {
		if ( ! empty( $this->form_data ) ) {
			return;
		}

		$preselect_council = isset( $_GET['council_id'] ) ? absint( $_GET['council_id'] ) : 0;

		$this->form_data = [
			'email'       => '',
			'phone'       => '',
			'channels'    => [ 'email' ],
			'council_ids' => $preselect_council > 0 ? [ $preselect_council ] : [],
			'frequency'   => 'immediate',
		];
	}

	private function fetch_councils(): void {
		$response = civime_api()->get_councils( [] );

		if ( is_wp_error( $response ) ) {
			$this->councils = $response;
		} else {
			$this->councils = $response['data'] ?? [];
		}
	}

	/**
	 * Fetch topics and build a map of topic_slug → council IDs.
	 *
	 * Uses a composite transient to avoid N+1 API calls on every page load.
	 * Each topic must be fetched individually to get its associated councils,
	 * so caching the assembled result saves significant latency.
	 */
	private function fetch_topics(): void {
		$cache_key = 'civime_cache_topic_council_map';
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			$this->topics           = $cached['topics'] ?? [];
			$this->topic_council_map = $cached['map'] ?? [];
			return;
		}

		$response = civime_api()->get_topics();

		if ( is_wp_error( $response ) ) {
			return;
		}

		$this->topics = $response['data'] ?? [];

		foreach ( $this->topics as $topic ) {
			$slug           = $topic['slug'] ?? '';
			$topic_response = civime_api()->get_topic( $slug );

			if ( is_wp_error( $topic_response ) ) {
				continue;
			}

			$councils    = $topic_response['councils'] ?? [];
			$council_ids = array_map( fn( array $c ) => (int) ( $c['id'] ?? 0 ), $councils );
			$this->topic_council_map[ $slug ] = array_values( array_filter( $council_ids ) );
		}

		set_transient( $cache_key, [
			'topics' => $this->topics,
			'map'    => $this->topic_council_map,
		], 900 );
	}

	/**
	 * Normalize a US phone number to E.164 format (+1XXXXXXXXXX).
	 *
	 * Accepts common formats: (808) 555-1234, 808-555-1234, 8085551234, +18085551234.
	 * Returns empty string if the input is not a valid 10-digit US number.
	 */
	private function normalize_phone( string $raw ): string {
		$digits = preg_replace( '/\D/', '', $raw );

		// Strip leading country code.
		if ( strlen( $digits ) === 11 && str_starts_with( $digits, '1' ) ) {
			$digits = substr( $digits, 1 );
		}

		if ( strlen( $digits ) !== 10 ) {
			return '';
		}

		return '+1' . $digits;
	}

	// --- Template getters ---

	public function is_submitted(): bool {
		return $this->submitted;
	}

	public function get_errors(): array {
		return $this->errors;
	}

	public function has_errors(): bool {
		return ! empty( $this->errors );
	}

	public function get_councils(): array {
		return is_wp_error( $this->councils ) ? [] : $this->councils;
	}

	public function has_councils_error(): bool {
		return is_wp_error( $this->councils );
	}

	public function get_form_data(): array {
		return $this->form_data;
	}

	/**
	 * Check whether a council ID should be pre-checked in the form.
	 */
	public function is_council_selected( int $council_id ): bool {
		return in_array( $council_id, $this->form_data['council_ids'] ?? [], true );
	}

	/**
	 * Check whether a channel is currently selected.
	 */
	public function is_channel_selected( string $channel ): bool {
		return in_array( $channel, $this->form_data['channels'] ?? [], true );
	}

	/**
	 * Return all topics for the topic picker chips.
	 */
	public function get_topics(): array {
		return $this->topics;
	}

	/**
	 * Return the topic→council_ids map for client-side filtering.
	 */
	public function get_topic_council_map(): array {
		return $this->topic_council_map;
	}

	/**
	 * Whether the form is in single-council mode.
	 */
	public function is_single_council(): bool {
		return $this->is_single_council;
	}

	/**
	 * Return the single council data array.
	 */
	public function get_single_council(): array {
		return $this->single_council;
	}

	/**
	 * Return the full subscribe URL without council_id.
	 */
	public function get_full_subscribe_url(): string {
		return home_url( '/meetings/subscribe/' );
	}
}
