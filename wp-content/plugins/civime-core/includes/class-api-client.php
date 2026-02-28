<?php
/**
 * CiviMe API Client
 *
 * Wraps all Access100 API endpoints with caching and structured error handling.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_API_Client {

	private const CACHE_PREFIX    = 'civime_cache_';
	private const DEFAULT_TTL     = 300;
	private const REQUEST_TIMEOUT = 15;

	private string $api_base_url;
	private string $api_key;

	/**
	 * @param string $api_base_url Base URL of the Access100 API (e.g. https://access100.app).
	 * @param string $api_key      API key sent via X-API-Key header on every request.
	 */
	public function __construct( string $api_base_url, string $api_key ) {
		$this->api_base_url = untrailingslashit( $api_base_url );
		$this->api_key      = $api_key;
	}

	// =========================================================================
	// Meetings
	// =========================================================================

	/**
	 * List meetings with optional filters.
	 *
	 * @param array{
	 *   date_from?: string,
	 *   date_to?: string,
	 *   council_id?: int,
	 *   q?: string,
	 *   county?: string,
	 *   limit?: int,
	 *   offset?: int,
	 * } $args
	 * @return array|WP_Error
	 */
	public function get_meetings( array $args = [] ): array|WP_Error {
		return $this->cached_get( '/api/v1/meetings', $args );
	}

	/**
	 * Fetch a single meeting by its state-scoped ID.
	 *
	 * @param string $state_id
	 * @return array|WP_Error
	 */
	public function get_meeting( string $state_id ): array|WP_Error {
		return $this->cached_get( '/api/v1/meetings/' . rawurlencode( $state_id ) );
	}

	/**
	 * Fetch the AI-generated summary for a meeting.
	 *
	 * @param string $state_id
	 * @return array|WP_Error
	 */
	public function get_meeting_summary( string $state_id ): array|WP_Error {
		return $this->cached_get( '/api/v1/meetings/' . rawurlencode( $state_id ) . '/summary' );
	}

	/**
	 * Returns the direct ICS download URL for a meeting (no HTTP call needed).
	 *
	 * @param string $state_id
	 * @return string
	 */
	public function get_meeting_ics_url( string $state_id ): string {
		return $this->api_base_url . '/api/v1/meetings/' . rawurlencode( $state_id ) . '/ics';
	}

	// =========================================================================
	// Councils
	// =========================================================================

	/**
	 * List councils with optional filters.
	 *
	 * @param array{
	 *   q?: string,
	 *   parent_id?: int,
	 *   has_upcoming?: bool,
	 * } $args
	 * @return array|WP_Error
	 */
	public function get_councils( array $args = [] ): array|WP_Error {
		return $this->cached_get( '/api/v1/councils', $args );
	}

	/**
	 * Fetch a single council by ID.
	 *
	 * @param int $id
	 * @return array|WP_Error
	 */
	public function get_council( int $id ): array|WP_Error {
		return $this->cached_get( '/api/v1/councils/' . $id );
	}

	/**
	 * List meetings for a specific council.
	 *
	 * @param int   $id
	 * @param array $args Optional filters (same shape as get_meetings).
	 * @return array|WP_Error
	 */
	public function get_council_meetings( int $id, array $args = [] ): array|WP_Error {
		return $this->cached_get( '/api/v1/councils/' . $id . '/meetings', $args );
	}

	// =========================================================================
	// Topics
	// =========================================================================

	/**
	 * List all topics with council counts.
	 *
	 * @return array|WP_Error
	 */
	public function get_topics(): array|WP_Error {
		return $this->cached_get( '/api/v1/topics' );
	}

	/**
	 * Fetch a single topic by slug.
	 *
	 * @param string $slug Topic slug (e.g. 'environment').
	 * @return array|WP_Error
	 */
	public function get_topic( string $slug ): array|WP_Error {
		return $this->cached_get( '/api/v1/topics/' . rawurlencode( $slug ) );
	}

	/**
	 * List meetings for councils mapped to a topic.
	 *
	 * @param string $slug Topic slug.
	 * @param array  $args Optional filters (limit, offset, date_from).
	 * @return array|WP_Error
	 */
	public function get_topic_meetings( string $slug, array $args = [] ): array|WP_Error {
		return $this->cached_get( '/api/v1/topics/' . rawurlencode( $slug ) . '/meetings', $args );
	}

	// =========================================================================
	// Council Profiles
	// =========================================================================

	/**
	 * Fetch a council's full profile.
	 *
	 * @param int $id Council ID.
	 * @return array|WP_Error
	 */
	public function get_council_profile( int $id ): array|WP_Error {
		return $this->cached_get( '/api/v1/councils/' . $id . '/profile' );
	}

	/**
	 * Lookup a council by its URL slug.
	 *
	 * @param string $slug Council profile slug.
	 * @return array|WP_Error
	 */
	public function get_council_by_slug( string $slug ): array|WP_Error {
		return $this->cached_get( '/api/v1/councils/slug/' . rawurlencode( $slug ) );
	}

	/**
	 * Fetch legal authority references for a council.
	 *
	 * @param int $id Council ID.
	 * @return array|WP_Error
	 */
	public function get_council_authority( int $id ): array|WP_Error {
		return $this->cached_get( '/api/v1/councils/' . $id . '/authority' );
	}

	/**
	 * Fetch board member list for a council.
	 *
	 * @param int $id Council ID.
	 * @return array|WP_Error
	 */
	public function get_council_members( int $id ): array|WP_Error {
		return $this->cached_get( '/api/v1/councils/' . $id . '/members' );
	}

	/**
	 * Fetch open vacancies for a council.
	 *
	 * @param int $id Council ID.
	 * @return array|WP_Error
	 */
	public function get_council_vacancies( int $id ): array|WP_Error {
		return $this->cached_get( '/api/v1/councils/' . $id . '/vacancies' );
	}

	// =========================================================================
	// Subscriptions
	// =========================================================================

	/**
	 * Create a new alert subscription.
	 *
	 * Automatically injects `source=civime` into every creation payload.
	 *
	 * @param array{
	 *   email?: string,
	 *   phone?: string,
	 *   channels: string[],
	 *   council_ids: int[],
	 *   frequency?: string,
	 *   source?: string,
	 * } $data
	 * @return array|WP_Error
	 */
	public function create_subscription( array $data ): array|WP_Error {
		// Always tag where the subscription originated.
		$data['source'] = 'civime';

		return $this->request( 'POST', '/api/v1/subscriptions', [ 'body' => $data ] );
	}

	/**
	 * Fetch an existing subscription.
	 *
	 * @param string $id    Subscription UUID.
	 * @param string $token Magic-link token for authentication.
	 * @return array|WP_Error
	 */
	public function get_subscription( string $id, string $token ): array|WP_Error {
		return $this->request( 'GET', '/api/v1/subscriptions/' . rawurlencode( $id ), [
			'query' => [ 'token' => $token ],
		] );
	}

	/**
	 * Partially update a subscription's fields.
	 *
	 * @param string $id    Subscription UUID.
	 * @param string $token Magic-link token for authentication.
	 * @param array  $data  Fields to update.
	 * @return array|WP_Error
	 */
	public function update_subscription( string $id, string $token, array $data ): array|WP_Error {
		return $this->request( 'PATCH', '/api/v1/subscriptions/' . rawurlencode( $id ), [
			'query' => [ 'token' => $token ],
			'body'  => $data,
		] );
	}

	/**
	 * Replace the full list of councils on a subscription.
	 *
	 * @param string $id          Subscription UUID.
	 * @param string $token       Magic-link token for authentication.
	 * @param int[]  $council_ids Replacement council ID list.
	 * @return array|WP_Error
	 */
	public function update_subscription_councils( string $id, string $token, array $council_ids ): array|WP_Error {
		return $this->request( 'PUT', '/api/v1/subscriptions/' . rawurlencode( $id ) . '/councils', [
			'query' => [ 'token' => $token ],
			'body'  => [ 'council_ids' => $council_ids ],
		] );
	}

	/**
	 * Delete a subscription.
	 *
	 * @param string $id    Subscription UUID.
	 * @param string $token Magic-link token for authentication.
	 * @return array|WP_Error
	 */
	public function delete_subscription( string $id, string $token ): array|WP_Error {
		return $this->request( 'DELETE', '/api/v1/subscriptions/' . rawurlencode( $id ), [
			'query' => [ 'token' => $token ],
		] );
	}

	// =========================================================================
	// Health / Stats
	// =========================================================================

	/**
	 * Check the API health status.
	 *
	 * This endpoint is always called live — never served from cache — so that
	 * the settings page always reflects the current API state.
	 *
	 * @return array|WP_Error
	 */
	public function get_health(): array|WP_Error {
		return $this->request( 'GET', '/api/v1/health' );
	}

	/**
	 * Fetch aggregate platform statistics.
	 *
	 * @return array|WP_Error
	 */
	public function get_stats(): array|WP_Error {
		return $this->cached_get( '/api/v1/stats' );
	}

	// =========================================================================
	// Cache management
	// =========================================================================

	/**
	 * Flush cached API responses.
	 *
	 * Passing null clears every civime_cache_* transient found in wp_options.
	 * Passing a specific endpoint clears only its transient.
	 *
	 * @param string|null $endpoint e.g. '/api/v1/meetings' to clear just that one.
	 * @return void
	 */
	public function flush_cache( ?string $endpoint = null ): void {
		global $wpdb;

		if ( null !== $endpoint ) {
			// Derive the same key the cached_get call would have produced.
			$cache_key = self::CACHE_PREFIX . md5( $endpoint );
			delete_transient( $cache_key );
			return;
		}

		// Bulk-delete all CiviMe transients by matching the option name prefix.
		$transient_prefix = '_transient_' . self::CACHE_PREFIX;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $transient_prefix ) . '%'
		) );

		// Also clean up the timeout entries WordPress stores alongside each transient.
		$timeout_prefix = '_transient_timeout_' . self::CACHE_PREFIX;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $timeout_prefix ) . '%'
		) );
	}

	// =========================================================================
	// Internal helpers
	// =========================================================================

	/**
	 * Wrap a GET request with WordPress transient caching.
	 *
	 * Errors are never stored in the cache so a transient failure does not
	 * persist beyond the current request.
	 *
	 * @param string   $endpoint Relative API path.
	 * @param array    $args     Query-string parameters.
	 * @param int|null $ttl      Override TTL in seconds; null uses the setting.
	 * @return array|WP_Error
	 */
	private function cached_get( string $endpoint, array $args = [], ?int $ttl = null ): array|WP_Error {
		$caching_enabled = (bool) civime_get_option( 'civime_cache_enabled', true );

		if ( ! $caching_enabled ) {
			return $this->request( 'GET', $endpoint, [ 'query' => $args ] );
		}

		$cache_key = self::CACHE_PREFIX . md5( $endpoint . serialize( $args ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$response = $this->request( 'GET', $endpoint, [ 'query' => $args ] );

		if ( ! is_wp_error( $response ) ) {
			$ttl_seconds = $ttl ?? (int) civime_get_option( 'civime_cache_ttl', self::DEFAULT_TTL );
			set_transient( $cache_key, $response, $ttl_seconds );
		}

		return $response;
	}

	/**
	 * Execute an HTTP request against the Access100 API.
	 *
	 * Accepted $args keys:
	 *   - query (array)  — appended to the URL as query-string parameters.
	 *   - body  (array)  — JSON-encoded request body for POST/PATCH/PUT.
	 *
	 * @param string $method   HTTP verb (GET, POST, PATCH, PUT, DELETE).
	 * @param string $endpoint Relative path including leading slash.
	 * @param array  $args     Optional query and body arguments.
	 * @return array|WP_Error Parsed JSON payload on success, WP_Error on failure.
	 */
	private function request( string $method, string $endpoint, array $args = [] ): array|WP_Error {
		$url = $this->api_base_url . $endpoint;

		if ( ! empty( $args['query'] ) ) {
			$url = add_query_arg( $args['query'], $url );
		}

		$request_args = [
			'method'  => strtoupper( $method ),
			'timeout' => self::REQUEST_TIMEOUT,
			'headers' => [
				'X-API-Key'  => $this->api_key,
				'Accept'     => 'application/json',
				'User-Agent' => 'CiviMe-WordPress/' . CIVIME_CORE_VERSION,
			],
		];

		$body_methods = [ 'POST', 'PATCH', 'PUT' ];
		if ( in_array( $request_args['method'], $body_methods, true ) && ! empty( $args['body'] ) ) {
			$request_args['headers']['Content-Type'] = 'application/json';
			$request_args['body']                    = wp_json_encode( $args['body'] );
		}

		$http_response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $http_response ) ) {
			return $http_response;
		}

		$status_code   = wp_remote_retrieve_response_code( $http_response );
		$response_body = wp_remote_retrieve_body( $http_response );
		$parsed        = json_decode( $response_body, associative: true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'civime_api_invalid_json',
				sprintf( 'API returned non-JSON response (HTTP %d).', $status_code )
			);
		}

		if ( $status_code < 200 || $status_code >= 300 ) {
			$api_message = $parsed['message']
				?? ( is_string( $parsed['error'] ?? null ) ? $parsed['error'] : null )
				?? 'Unknown API error';
			return new WP_Error(
				'civime_api_error_' . $status_code,
				sprintf( 'API error (HTTP %d): %s', $status_code, $api_message )
			);
		}

		return $parsed;
	}
}
