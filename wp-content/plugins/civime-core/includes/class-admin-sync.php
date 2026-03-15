<?php
/**
 * CiviMe Admin Meetings Sync
 *
 * Registers the Meetings Sync submenu under the CiviMe admin menu and provides
 * manual scraper triggering via the Access100 API admin endpoints.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Admin_Sync {

	private const MENU_SLUG = 'civime';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_post_civime_trigger_sync', [ $this, 'handle_trigger_sync' ] );
		add_action( 'admin_post_civime_trigger_nco_sync', [ $this, 'handle_trigger_nco_sync' ] );
		add_action( 'admin_post_civime_trigger_honolulu_boards_sync', [ $this, 'handle_trigger_honolulu_boards_sync' ] );
		add_action( 'admin_post_civime_trigger_maui_sync', [ $this, 'handle_trigger_maui_sync' ] );
	}

	/**
	 * Register Meetings Sync as the default CiviMe admin page.
	 *
	 * Using the same slug as the parent menu makes WordPress display this
	 * submenu as the default page when clicking the top-level CiviMe menu.
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Meetings Sync', 'civime-core' ),
			__( 'Meetings Sync', 'civime-core' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Handle manual sync trigger form submission.
	 */
	public function handle_trigger_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_trigger_sync' );

		$result = civime_api()->trigger_admin_scrape();

		if ( is_wp_error( $result ) ) {
			// Check for 429 (too recent)
			if ( str_contains( $result->get_error_code(), '429' ) ) {
				wp_redirect( $this->admin_url( [ 'civime_error' => 'too_recent' ] ) );
				exit;
			}

			wp_redirect( $this->admin_url( [ 'civime_error' => 'api_error' ] ) );
			exit;
		}

		// Flush meetings cache so fresh data appears on next page load
		civime_api()->flush_cache( '/api/v1/meetings' );

		wp_redirect( $this->admin_url( [ 'civime_triggered' => '1' ] ) );
		exit;
	}

	/**
	 * Handle NCO neighborhood board sync trigger form submission.
	 */
	public function handle_trigger_nco_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_trigger_nco_sync' );

		$result = civime_api()->trigger_admin_nco_scrape();

		if ( is_wp_error( $result ) ) {
			if ( str_contains( $result->get_error_code(), '429' ) ) {
				wp_redirect( $this->admin_url( [ 'civime_error' => 'nco_too_recent' ] ) );
				exit;
			}

			wp_redirect( $this->admin_url( [ 'civime_error' => 'nco_api_error' ] ) );
			exit;
		}

		civime_api()->flush_cache( '/api/v1/meetings' );

		wp_redirect( $this->admin_url( [ 'civime_nco_triggered' => '1' ] ) );
		exit;
	}

	/**
	 * Handle Honolulu boards & commissions sync trigger form submission.
	 */
	public function handle_trigger_honolulu_boards_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_trigger_honolulu_boards_sync' );

		$result = civime_api()->trigger_admin_honolulu_boards_scrape();

		if ( is_wp_error( $result ) ) {
			if ( str_contains( $result->get_error_code(), '429' ) ) {
				wp_redirect( $this->admin_url( [ 'civime_error' => 'hnl_boards_too_recent' ] ) );
				exit;
			}

			wp_redirect( $this->admin_url( [ 'civime_error' => 'hnl_boards_api_error' ] ) );
			exit;
		}

		civime_api()->flush_cache( '/api/v1/meetings' );

		wp_redirect( $this->admin_url( [ 'civime_hnl_boards_triggered' => '1' ] ) );
		exit;
	}

	/**
	 * Handle Maui Legistar sync trigger form submission.
	 */
	public function handle_trigger_maui_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_trigger_maui_sync' );

		$result = civime_api()->trigger_admin_maui_scrape();

		if ( is_wp_error( $result ) ) {
			if ( str_contains( $result->get_error_code(), '429' ) ) {
				wp_redirect( $this->admin_url( [ 'civime_error' => 'maui_too_recent' ] ) );
				exit;
			}

			wp_redirect( $this->admin_url( [ 'civime_error' => 'maui_api_error' ] ) );
			exit;
		}

		civime_api()->flush_cache( '/api/v1/meetings' );

		wp_redirect( $this->admin_url( [ 'civime_maui_triggered' => '1' ] ) );
		exit;
	}

	/**
	 * Output the meetings sync page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require CIVIME_CORE_PATH . 'admin/sync-page.php';
	}

	/**
	 * Build an admin URL for the sync page with optional query args.
	 *
	 * @param array $args Additional query parameters.
	 * @return string
	 */
	private function admin_url( array $args = [] ): string {
		return add_query_arg(
			array_merge( [ 'page' => self::MENU_SLUG ], $args ),
			admin_url( 'admin.php' )
		);
	}
}
