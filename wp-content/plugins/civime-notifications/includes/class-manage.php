<?php
/**
 * Manage Preferences Controller
 *
 * Handles the /notifications/manage page where subscribers can update their
 * channel, frequency, and council preferences, or unsubscribe entirely.
 * Authentication is token-based — the manage URL includes both a subscription
 * ID and a secret token (like email unsubscribe links).
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Notifications_Manage {

	private string $subscription_id = '';
	private string $token           = '';
	private array|WP_Error $subscription = [];
	private array|WP_Error $councils    = [];
	private string $message      = '';
	private string $message_type = '';

	private const VALID_CHANNELS    = [ 'email', 'sms' ];
	private const VALID_FREQUENCIES = [ 'immediate', 'daily', 'weekly' ];

	public function __construct() {
		$this->subscription_id = sanitize_text_field( wp_unslash( $_GET['id'] ?? '' ) );
		$this->token           = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );

		// Both are required — without them we can't identify the subscription.
		if ( '' === $this->subscription_id || '' === $this->token ) {
			$this->subscription = new WP_Error(
				'missing_params',
				__( 'This link appears to be incomplete. Please use the manage link from your notification email.', 'civime-notifications' )
			);
			$this->councils = new WP_Error( 'skipped', '' );
			return;
		}

		// Check for post-redirect success flag.
		if ( '1' === sanitize_key( wp_unslash( $_GET['updated'] ?? '' ) ) ) {
			$this->message      = __( 'Your preferences have been updated.', 'civime-notifications' );
			$this->message_type = 'success';
		}

		// Handle POST before fetching display data.
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$this->handle_post();
		}

		$this->fetch_data();
	}

	/**
	 * Process update or unsubscribe POST.
	 */
	private function handle_post(): void {
		if ( ! isset( $_POST['_civime_manage_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_civime_manage_nonce'] ) ), 'civime_manage_subscription' ) ) {
			$this->message      = __( 'Security check failed. Please try again.', 'civime-notifications' );
			$this->message_type = 'error';
			return;
		}

		$action = sanitize_key( $_POST['civime_action'] ?? '' );

		if ( 'unsubscribe' === $action ) {
			$this->handle_unsubscribe();
			return;
		}

		if ( 'update' === $action ) {
			$this->handle_update();
			return;
		}
	}

	private function handle_unsubscribe(): void {
		$result = civime_api()->delete_subscription( $this->subscription_id, $this->token );

		if ( is_wp_error( $result ) ) {
			error_log( 'CiviMe unsubscribe API error: ' . $result->get_error_message() );
			$this->message      = __( 'Something went wrong. Please try again in a moment.', 'civime-notifications' );
			$this->message_type = 'error';
			return;
		}

		wp_safe_redirect( home_url( '/notifications/unsubscribed/' ) );
		exit;
	}

	private function handle_update(): void {
		// --- Sanitize ---

		$raw_channels = isset( $_POST['channels'] ) && is_array( $_POST['channels'] )
			? array_map( 'sanitize_key', $_POST['channels'] )
			: [];

		$channels = array_values( array_intersect( $raw_channels, self::VALID_CHANNELS ) );

		$frequency_raw = sanitize_key( $_POST['frequency'] ?? '' );
		$frequency     = in_array( $frequency_raw, self::VALID_FREQUENCIES, true ) ? $frequency_raw : '';

		$raw_council_ids = isset( $_POST['council_ids'] ) && is_array( $_POST['council_ids'] )
			? array_map( 'absint', $_POST['council_ids'] )
			: [];

		$council_ids = array_values( array_filter( $raw_council_ids, fn( int $id ) => $id > 0 ) );

		// --- Validate ---

		if ( empty( $channels ) ) {
			$this->message      = __( 'Please select at least one notification channel.', 'civime-notifications' );
			$this->message_type = 'error';
			return;
		}

		if ( '' === $frequency ) {
			$this->message      = __( 'Please select a notification frequency.', 'civime-notifications' );
			$this->message_type = 'error';
			return;
		}

		if ( empty( $council_ids ) ) {
			$this->message      = __( 'Please select at least one council to follow.', 'civime-notifications' );
			$this->message_type = 'error';
			return;
		}

		// --- Update via API ---

		$update_result = civime_api()->update_subscription(
			$this->subscription_id,
			$this->token,
			[
				'channels'  => $channels,
				'frequency' => $frequency,
			]
		);

		if ( is_wp_error( $update_result ) ) {
			error_log( 'CiviMe manage update API error: ' . $update_result->get_error_message() );
			$this->message      = __( 'Something went wrong updating your preferences. Please try again.', 'civime-notifications' );
			$this->message_type = 'error';
			return;
		}

		$councils_result = civime_api()->update_subscription_councils(
			$this->subscription_id,
			$this->token,
			$council_ids
		);

		if ( is_wp_error( $councils_result ) ) {
			error_log( 'CiviMe manage councils API error: ' . $councils_result->get_error_message() );
			$this->message      = __( 'Preferences updated, but council list could not be saved. Please try again.', 'civime-notifications' );
			$this->message_type = 'error';
			return;
		}

		// POST-redirect-GET to prevent form resubmission.
		$redirect_url = add_query_arg(
			[
				'id'      => $this->subscription_id,
				'token'   => $this->token,
				'updated' => '1',
			],
			home_url( '/notifications/manage/' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	private function fetch_data(): void {
		if ( is_wp_error( $this->subscription ?? null ) ) {
			return;
		}

		$this->subscription = civime_api()->get_subscription( $this->subscription_id, $this->token );

		if ( is_wp_error( $this->subscription ) ) {
			error_log( 'CiviMe manage fetch API error: ' . $this->subscription->get_error_message() );
		}

		$councils_response = civime_api()->get_councils( [] );
		$this->councils    = is_wp_error( $councils_response ) ? $councils_response : ( $councils_response['data'] ?? [] );
	}

	// --- Template getters ---

	public function has_error(): bool {
		return is_wp_error( $this->subscription );
	}

	public function is_auth_error(): bool {
		if ( ! is_wp_error( $this->subscription ) ) {
			return false;
		}

		foreach ( $this->subscription->get_error_codes() as $code ) {
			$code_str = (string) $code;
			if ( str_contains( $code_str, '401' ) || str_contains( $code_str, '403' ) || str_contains( $code_str, '404' ) || str_contains( $code_str, 'missing_params' ) ) {
				return true;
			}
		}

		return false;
	}

	public function get_error_message(): string {
		if ( ! is_wp_error( $this->subscription ) ) {
			return '';
		}

		if ( $this->subscription->get_error_code() === 'missing_params' ) {
			return $this->subscription->get_error_message();
		}

		return __( 'We could not load your subscription. The link may have expired or be incorrect. Please use the manage link from your most recent notification email.', 'civime-notifications' );
	}

	public function get_subscription(): array {
		return is_wp_error( $this->subscription ) ? [] : ( $this->subscription['data'] ?? [] );
	}

	public function get_councils(): array {
		return is_wp_error( $this->councils ) ? [] : $this->councils;
	}

	public function get_subscription_id(): string {
		return $this->subscription_id;
	}

	public function get_token(): string {
		return $this->token;
	}

	public function get_message(): string {
		return $this->message;
	}

	public function get_message_type(): string {
		return $this->message_type;
	}

	/**
	 * Check whether a council is in the subscriber's current list.
	 */
	public function is_council_subscribed( int $council_id ): bool {
		$sub = $this->get_subscription();
		$ids = $sub['council_ids'] ?? [];

		return in_array( $council_id, array_map( 'intval', $ids ), true );
	}

	/**
	 * Check whether a channel is currently active on the subscription.
	 */
	public function is_channel_active( string $channel ): bool {
		$sub      = $this->get_subscription();
		$channels = $sub['channels'] ?? [];

		return in_array( $channel, $channels, true );
	}

	/**
	 * Get the current frequency setting.
	 */
	public function get_frequency(): string {
		$sub = $this->get_subscription();

		return $sub['frequency'] ?? 'immediate';
	}

	/**
	 * Build the manage page URL with current auth params, useful for form actions.
	 */
	public function get_manage_url(): string {
		return add_query_arg(
			[
				'id'    => $this->subscription_id,
				'token' => $this->token,
			],
			home_url( '/notifications/manage/' )
		);
	}
}
