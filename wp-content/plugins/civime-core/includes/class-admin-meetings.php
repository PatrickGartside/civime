<?php
/**
 * CiviMe Admin Meetings
 *
 * Registers the Meetings submenu under the CiviMe admin menu and provides
 * a view of meetings from the Access100 API with link-checking and inline
 * meeting update capabilities.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Admin_Meetings {

	private const MENU_SLUG   = 'civime-meetings';
	private const PARENT_SLUG = 'civime';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_post_civime_check_meeting_links', [ $this, 'handle_check_links' ] );
		add_action( 'admin_post_civime_update_meeting', [ $this, 'handle_update_meeting' ] );
	}

	/**
	 * Register the Meetings submenu under the CiviMe parent menu.
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Meetings', 'civime-core' ),
			__( 'Meetings', 'civime-core' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Handle the "Check Links" form submission.
	 */
	public function handle_check_links(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_check_meeting_links' );

		$result = civime_api()->check_admin_meeting_links();

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'check_failed' ] ) );
			exit;
		}

		set_transient( 'civime_broken_links', $result, HOUR_IN_SECONDS );

		wp_redirect( $this->admin_url( [ 'civime_checked' => '1' ] ) );
		exit;
	}

	/**
	 * Handle the inline "Update Meeting" form submission.
	 */
	public function handle_update_meeting(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_update_meeting' );

		$meeting_id   = isset( $_POST['meeting_id'] ) ? absint( $_POST['meeting_id'] ) : 0;
		$new_state_id = isset( $_POST['new_state_id'] ) ? sanitize_text_field( wp_unslash( $_POST['new_state_id'] ) ) : '';

		if ( $meeting_id === 0 || $new_state_id === '' ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_input', 'civime_checked' => '1' ] ) );
			exit;
		}

		$result = civime_api()->update_admin_meeting( $meeting_id, [ 'state_id' => $new_state_id ] );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'update_failed', 'civime_checked' => '1' ] ) );
			exit;
		}

		// Clear cached broken links so re-check shows fresh results
		delete_transient( 'civime_broken_links' );

		wp_redirect( $this->admin_url( [ 'civime_updated' => '1', 'civime_checked' => '1' ] ) );
		exit;
	}

	/**
	 * Output the meetings list page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require CIVIME_CORE_PATH . 'admin/meetings-page.php';
	}

	/**
	 * Build an admin URL for the meetings page with optional query args.
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
