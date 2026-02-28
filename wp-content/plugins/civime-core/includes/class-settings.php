<?php
/**
 * CiviMe Settings
 *
 * Registers the plugin settings page and all associated options via the
 * WordPress Settings API.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Settings {

	private const PAGE_SLUG    = 'civime-settings';
	private const OPTION_GROUP = 'civime_options';
	private const SECTION_API  = 'civime_section_api';
	private const SECTION_CACHE = 'civime_section_cache';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_post_civime_clear_cache', [ $this, 'handle_clear_cache' ] );
	}

	/**
	 * Add the settings page under WP Admin > Settings.
	 */
	public function register_admin_menu(): void {
		add_options_page(
			__( 'CiviMe Settings', 'civime-core' ),
			__( 'CiviMe', 'civime-core' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register all settings, sections, and fields.
	 */
	public function register_settings(): void {
		// --- API Settings section ---
		register_setting( self::OPTION_GROUP, 'civime_api_url', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_url' ],
			'default'           => 'https://app.access100.app',
		] );

		register_setting( self::OPTION_GROUP, 'civime_api_key', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_api_key' ],
			'default'           => '',
		] );

		add_settings_section(
			self::SECTION_API,
			__( 'API Connection', 'civime-core' ),
			[ $this, 'render_api_section_description' ],
			self::PAGE_SLUG
		);

		add_settings_field(
			'civime_api_url',
			__( 'API Base URL', 'civime-core' ),
			[ $this, 'render_field_api_url' ],
			self::PAGE_SLUG,
			self::SECTION_API
		);

		add_settings_field(
			'civime_api_key',
			__( 'API Key', 'civime-core' ),
			[ $this, 'render_field_api_key' ],
			self::PAGE_SLUG,
			self::SECTION_API
		);

		// --- Cache Settings section ---
		register_setting( self::OPTION_GROUP, 'civime_cache_ttl', [
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 300,
		] );

		register_setting( self::OPTION_GROUP, 'civime_cache_enabled', [
			'type'              => 'boolean',
			'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
			'default'           => true,
		] );

		add_settings_section(
			self::SECTION_CACHE,
			__( 'Cache Settings', 'civime-core' ),
			[ $this, 'render_cache_section_description' ],
			self::PAGE_SLUG
		);

		add_settings_field(
			'civime_cache_enabled',
			__( 'Enable Caching', 'civime-core' ),
			[ $this, 'render_field_cache_enabled' ],
			self::PAGE_SLUG,
			self::SECTION_CACHE
		);

		add_settings_field(
			'civime_cache_ttl',
			__( 'Cache TTL (seconds)', 'civime-core' ),
			[ $this, 'render_field_cache_ttl' ],
			self::PAGE_SLUG,
			self::SECTION_CACHE
		);
	}

	// =========================================================================
	// Section descriptions
	// =========================================================================

	public function render_api_section_description(): void {
		echo '<p>' . esc_html__( 'Enter the connection details for your Access100 API instance.', 'civime-core' ) . '</p>';
	}

	public function render_cache_section_description(): void {
		echo '<p>' . esc_html__( 'Control how long API responses are cached in WordPress transients.', 'civime-core' ) . '</p>';
	}

	// =========================================================================
	// Field renderers
	// =========================================================================

	public function render_field_api_url(): void {
		$value = civime_get_option( 'civime_api_url', 'https://app.access100.app' );
		printf(
			'<input type="url" id="civime_api_url" name="civime_api_url" value="%s" class="regular-text" placeholder="https://app.access100.app">',
			esc_attr( $value )
		);
		echo '<p class="description">' . esc_html__( 'The base URL of the Access100 API (no trailing slash).', 'civime-core' ) . '</p>';
	}

	public function render_field_api_key(): void {
		// Render a password field — the stored value is shown only as a masked placeholder
		// so that the raw key is never visible in the page source or request logs.
		$has_key = ! empty( civime_get_option( 'civime_api_key', '' ) );
		printf(
			'<input type="password" id="civime_api_key" name="civime_api_key" value="" class="regular-text" autocomplete="new-password" placeholder="%s">',
			$has_key
				? esc_attr__( '(key saved — enter a new value to replace)', 'civime-core' )
				: esc_attr__( 'Enter your API key', 'civime-core' )
		);
		echo '<p class="description">' . esc_html__( 'Your Access100 API key. Leave blank to keep the current key.', 'civime-core' ) . '</p>';
	}

	public function render_field_cache_enabled(): void {
		$checked = (bool) civime_get_option( 'civime_cache_enabled', true );
		printf(
			'<input type="checkbox" id="civime_cache_enabled" name="civime_cache_enabled" value="1" %s>',
			checked( $checked, true, false )
		);
		echo '<label for="civime_cache_enabled"> ' . esc_html__( 'Cache API responses in WordPress transients', 'civime-core' ) . '</label>';
	}

	public function render_field_cache_ttl(): void {
		$value = (int) civime_get_option( 'civime_cache_ttl', 300 );
		printf(
			'<input type="number" id="civime_cache_ttl" name="civime_cache_ttl" value="%d" class="small-text" min="0" step="1">',
			$value
		);
		echo '<p class="description">' . esc_html__( 'How long to cache API responses (in seconds). 0 = no expiry.', 'civime-core' ) . '</p>';
	}

	// =========================================================================
	// Sanitization callbacks
	// =========================================================================

	/**
	 * Sanitize a URL option value.
	 */
	public function sanitize_url( string $value ): string {
		$cleaned = esc_url_raw( trim( $value ) );
		return untrailingslashit( $cleaned );
	}

	/**
	 * Preserve the existing API key when the field is submitted blank.
	 *
	 * This prevents the key from being wiped whenever the settings form is saved
	 * without intentionally changing the key.
	 */
	public function sanitize_api_key( string $value ): string {
		$trimmed = trim( $value );

		if ( $trimmed === '' ) {
			// Keep whatever is already stored.
			return (string) civime_get_option( 'civime_api_key', '' );
		}

		return sanitize_text_field( $trimmed );
	}

	/**
	 * Sanitize a checkbox: returns true when the field value is '1', false otherwise.
	 */
	public function sanitize_checkbox( mixed $value ): bool {
		return $value === '1' || $value === true;
	}

	// =========================================================================
	// Admin actions
	// =========================================================================

	/**
	 * Handle the "Clear Cache" form submission.
	 */
	public function handle_clear_cache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_clear_cache' );

		civime_api()->flush_cache();

		wp_redirect( add_query_arg( [
			'page'           => self::PAGE_SLUG,
			'civime_cleared' => '1',
		], admin_url( 'options-general.php' ) ) );
		exit;
	}

	// =========================================================================
	// Page renderer
	// =========================================================================

	/**
	 * Output the settings page by loading the admin template.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require CIVIME_CORE_PATH . 'admin/settings-page.php';
	}
}
