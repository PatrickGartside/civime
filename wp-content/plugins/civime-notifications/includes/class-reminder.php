<?php
/**
 * Reminder AJAX Handler
 *
 * Processes the "Remind me about this meeting" form submission via AJAX.
 * Validates input, checks honeypot, and calls the Access100 API to create
 * a one-time meeting reminder with double opt-in confirmation.
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Notifications_Reminder {

	public function __construct() {
		add_action( 'wp_ajax_civime_create_reminder', [ $this, 'handle' ] );
		add_action( 'wp_ajax_nopriv_civime_create_reminder', [ $this, 'handle' ] );
	}

	/**
	 * Handle the AJAX request to create a meeting reminder.
	 *
	 * @return never
	 */
	public function handle(): void {
		// Nonce verification.
		if ( ! check_ajax_referer( 'civime_reminder', '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Security check failed. Please reload the page and try again.', 'civime-notifications' ) ],
				403
			);
		}

		// Honeypot — bots fill hidden fields, humans don't.
		$honeypot = sanitize_text_field( wp_unslash( $_POST['website'] ?? '' ) );
		if ( '' !== $honeypot ) {
			// Silently pretend success so the bot doesn't retry.
			wp_send_json_success( [
				'message' => __( 'Check your email to confirm your reminder.', 'civime-notifications' ),
			] );
		}

		// Sanitize input.
		$email            = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$meeting_state_id = sanitize_text_field( wp_unslash( $_POST['meeting_state_id'] ?? '' ) );

		// Validate email.
		if ( ! is_email( $email ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Please enter a valid email address.', 'civime-notifications' ) ],
				400
			);
		}

		// Validate meeting state ID format (e.g. BOE-2026-03-15).
		if ( '' === $meeting_state_id || ! preg_match( '/^[A-Za-z0-9._-]+$/', $meeting_state_id ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Invalid meeting identifier.', 'civime-notifications' ) ],
				400
			);
		}

		// Call the Access100 API.
		$result = civime_api()->create_reminder( [
			'email'            => $email,
			'meeting_state_id' => $meeting_state_id,
		] );

		if ( is_wp_error( $result ) ) {
			error_log( 'CiviMe reminder API error: ' . $result->get_error_message() );
			wp_send_json_error(
				[ 'message' => __( 'Something went wrong. Please try again in a moment.', 'civime-notifications' ) ],
				500
			);
		}

		wp_send_json_success( [
			'message' => __( 'Check your email to confirm your reminder.', 'civime-notifications' ),
		] );
	}
}
