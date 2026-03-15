<?php
/**
 * Notify Page Controller
 *
 * Handles both the GET (render form) and POST (process submission) for the
 * /meetings/{id}/notify page. On successful submission, redirects back with
 * a ?submitted=1 flag so the template shows a confirmation message.
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Notifications_Notify {

	private array|WP_Error $meeting = [];
	private string $state_id        = '';
	private array $errors           = [];
	private string $form_email      = '';
	private bool $submitted         = false;
	private bool $not_found         = false;

	private array $subscribe_errors    = [];
	private array $subscribe_form      = [];
	private bool $subscribe_submitted  = false;

	private const VALID_FREQUENCIES = [ 'immediate', 'daily', 'weekly' ];

	public function __construct() {
		$raw = get_query_var( 'civime_meeting_id', '' );
		$this->state_id = preg_match( '/^[a-zA-Z0-9._-]{1,64}$/', $raw ) ? $raw : '';

		if ( '' === $this->state_id ) {
			$this->not_found = true;
			return;
		}

		// Check for the post-redirect success flags.
		if ( '1' === sanitize_key( wp_unslash( $_GET['submitted'] ?? '' ) ) ) {
			$this->submitted = true;
			$this->fetch_meeting();
			return;
		}

		if ( '1' === sanitize_key( wp_unslash( $_GET['subscribed'] ?? '' ) ) ) {
			$this->subscribe_submitted = true;
			$this->fetch_meeting();
			return;
		}

		// Handle POST submission before fetching display data.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$form_action = sanitize_key( $_POST['form_action'] ?? '' );
			if ( 'subscribe' === $form_action ) {
				$this->handle_subscribe_post();
			} else {
				$this->handle_post();
			}
			// A successful submission redirects, so we only reach here on error.
		}

		$this->fetch_meeting();
		$this->set_subscribe_defaults();
	}

	/**
	 * Process the reminder form submission.
	 */
	private function handle_post(): void {
		// Nonce verification.
		if ( ! isset( $_POST['_civime_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_civime_nonce'] ) ), 'civime_reminder' ) ) {
			$this->errors[] = __( 'Security check failed. Please try again.', 'civime-notifications' );
			$this->preserve_form_data();
			return;
		}

		// Honeypot.
		$honeypot = sanitize_text_field( wp_unslash( $_POST['website'] ?? '' ) );
		if ( '' !== $honeypot ) {
			$this->do_success_redirect();
			return;
		}

		$this->preserve_form_data();

		// Sanitize + validate.
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		if ( ! is_email( $email ) ) {
			$this->errors[] = __( 'Please enter a valid email address.', 'civime-notifications' );
		}

		if ( ! empty( $this->errors ) ) {
			return;
		}

		// Submit to API.
		$result = civime_api()->create_reminder( [
			'email'            => $email,
			'meeting_state_id' => $this->state_id,
		] );

		if ( is_wp_error( $result ) ) {
			error_log( 'CiviMe reminder API error: ' . $result->get_error_message() );
			$this->errors[] = __( 'Something went wrong. Please try again in a moment.', 'civime-notifications' );
			return;
		}

		$this->do_success_redirect();
	}

	/**
	 * Redirect with success flag (POST-redirect-GET).
	 *
	 * @return never
	 */
	private function do_success_redirect(): void {
		wp_safe_redirect( add_query_arg( 'submitted', '1', home_url( '/meetings/' . rawurlencode( $this->state_id ) . '/notify/' ) ) );
		exit;
	}

	/**
	 * Save submitted email so the form can re-populate on validation error.
	 */
	private function preserve_form_data(): void {
		$this->form_email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	}

	/**
	 * Fetch meeting data from the API.
	 */
	private function fetch_meeting(): void {
		$response = civime_api()->get_meeting( $this->state_id );

		if ( is_wp_error( $response ) ) {
			$this->meeting = $response;
			return;
		}

		$this->meeting = CiviMe_Meetings_Data_Mapper::map_meeting_detail( $response['data'] ?? [] );
	}

	/**
	 * Process the inline subscribe form submission.
	 */
	private function handle_subscribe_post(): void {
		if ( ! isset( $_POST['_civime_subscribe_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_civime_subscribe_nonce'] ) ), 'civime_subscribe_inline' ) ) {
			$this->subscribe_errors[] = __( 'Security check failed. Please try again.', 'civime-notifications' );
			$this->preserve_subscribe_form_data();
			return;
		}

		// Honeypot.
		$honeypot = sanitize_text_field( wp_unslash( $_POST['subscribe_website'] ?? '' ) );
		if ( '' !== $honeypot ) {
			$this->do_subscribe_success_redirect();
			return;
		}

		// Rate limiting — 5 attempts per 5 minutes per IP.
		$rate_key = 'civime_rl_notify_sub_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
		$count    = (int) get_transient( $rate_key );
		if ( $count >= 5 ) {
			$this->subscribe_errors[] = __( 'Too many requests. Please wait a moment and try again.', 'civime-notifications' );
			$this->preserve_subscribe_form_data();
			return;
		}
		set_transient( $rate_key, $count + 1, 300 );

		$this->preserve_subscribe_form_data();

		// Sanitize.
		$email     = sanitize_email( wp_unslash( $_POST['subscribe_email'] ?? '' ) );
		$phone_raw = sanitize_text_field( wp_unslash( $_POST['subscribe_phone'] ?? '' ) );
		$phone     = $this->normalize_phone( $phone_raw );
		$frequency = sanitize_key( $_POST['frequency'] ?? '' );

		// Validate.
		if ( ! is_email( $email ) ) {
			$this->subscribe_errors[] = __( 'Please enter a valid email address.', 'civime-notifications' );
		}

		if ( '' !== $phone_raw && '' === $phone ) {
			$this->subscribe_errors[] = __( 'Please enter a valid US phone number (10 digits).', 'civime-notifications' );
		}

		if ( ! in_array( $frequency, self::VALID_FREQUENCIES, true ) ) {
			$this->subscribe_errors[] = __( 'Please select how often you want to be notified.', 'civime-notifications' );
		}

		if ( ! empty( $this->subscribe_errors ) ) {
			return;
		}

		// Need meeting data to get council_id.
		$this->fetch_meeting();
		$m = $this->get_meeting();
		$council_id = absint( $m['council_id'] ?? 0 );

		if ( 0 === $council_id ) {
			$this->subscribe_errors[] = __( 'Could not determine the council for this meeting. Please try again.', 'civime-notifications' );
			return;
		}

		// Build payload.
		$channels = [ 'email' ];
		if ( '' !== $phone ) {
			$channels[] = 'sms';
		}

		$payload = [
			'channels'    => $channels,
			'council_ids' => [ $council_id ],
			'frequency'   => $frequency,
			'email'       => $email,
		];

		if ( '' !== $phone ) {
			$payload['phone'] = $phone;
		}

		$result = civime_api()->create_subscription( $payload );

		if ( is_wp_error( $result ) ) {
			error_log( 'CiviMe inline subscribe API error: ' . $result->get_error_message() );
			$this->subscribe_errors[] = __( 'Something went wrong. Please try again in a moment.', 'civime-notifications' );
			return;
		}

		$this->do_subscribe_success_redirect();
	}

	/**
	 * Redirect with subscribe success flag (POST-redirect-GET).
	 *
	 * @return never
	 */
	private function do_subscribe_success_redirect(): void {
		wp_safe_redirect( add_query_arg( 'subscribed', '1', home_url( '/meetings/' . rawurlencode( $this->state_id ) . '/notify/' ) ) );
		exit;
	}

	/**
	 * Save subscribe form values for re-population on validation error.
	 */
	private function preserve_subscribe_form_data(): void {
		$this->subscribe_form = [
			'email'     => sanitize_email( wp_unslash( $_POST['subscribe_email'] ?? '' ) ),
			'phone'     => sanitize_text_field( wp_unslash( $_POST['subscribe_phone'] ?? '' ) ),
			'frequency' => sanitize_key( $_POST['frequency'] ?? '' ),
		];
	}

	/**
	 * Set default subscribe form values for the initial GET request.
	 */
	private function set_subscribe_defaults(): void {
		if ( ! empty( $this->subscribe_form ) ) {
			return;
		}
		$this->subscribe_form = [ 'email' => '', 'phone' => '', 'frequency' => 'immediate' ];
	}

	/**
	 * Normalize a US phone number to E.164 format (+1XXXXXXXXXX).
	 */
	private function normalize_phone( string $raw ): string {
		$digits = preg_replace( '/\D/', '', $raw );

		if ( strlen( $digits ) === 11 && str_starts_with( $digits, '1' ) ) {
			$digits = substr( $digits, 1 );
		}

		if ( strlen( $digits ) !== 10 ) {
			return '';
		}

		return '+1' . $digits;
	}

	// --- Template getters ---

	public function is_not_found(): bool {
		return $this->not_found || ( is_wp_error( $this->meeting ) && str_contains( $this->meeting->get_error_code(), '404' ) );
	}

	public function has_error(): bool {
		return is_wp_error( $this->meeting );
	}

	public function is_submitted(): bool {
		return $this->submitted;
	}

	public function get_errors(): array {
		return $this->errors;
	}

	public function has_errors(): bool {
		return ! empty( $this->errors );
	}

	public function get_meeting(): array {
		return is_wp_error( $this->meeting ) ? [] : $this->meeting;
	}

	public function get_state_id(): string {
		return $this->state_id;
	}

	public function get_form_email(): string {
		return $this->form_email;
	}

	public function is_subscribe_submitted(): bool {
		return $this->subscribe_submitted;
	}

	public function get_subscribe_errors(): array {
		return $this->subscribe_errors;
	}

	public function has_subscribe_errors(): bool {
		return ! empty( $this->subscribe_errors );
	}

	public function get_subscribe_form(): array {
		return $this->subscribe_form;
	}

	/**
	 * Build the subscribe URL for this meeting's council.
	 */
	public function get_subscribe_url(): string {
		$m = $this->get_meeting();
		$council_id = absint( $m['council_id'] ?? 0 );

		if ( $council_id > 0 ) {
			return add_query_arg( 'council_id', $council_id, home_url( '/meetings/subscribe/' ) );
		}

		return home_url( '/meetings/subscribe/' );
	}

	/**
	 * Get formatted meeting date for display.
	 */
	public function get_formatted_date(): string {
		$m = $this->get_meeting();

		if ( empty( $m['date'] ) ) {
			return '';
		}

		$date = wp_date( 'l, F j, Y', strtotime( $m['date'] ) );

		if ( ! empty( $m['time'] ) ) {
			$date .= ' · ' . wp_date( 'g:i A', strtotime( $m['time'] ) );
		}

		return $date;
	}

	/**
	 * Page title for document_title_parts filter.
	 */
	public function get_page_title(): string {
		$m = $this->get_meeting();
		$council = $m['council_name'] ?? '';

		if ( $council ) {
			return sprintf( __( 'Get Notified — %s', 'civime-notifications' ), $council );
		}

		return __( 'Get Notified', 'civime-notifications' );
	}
}
