<?php
/**
 * CiviMe Admin Reminders
 *
 * Registers the Reminders submenu under the CiviMe admin menu and provides
 * delete handling for meeting reminders via the Access100 API admin endpoints.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Admin_Reminders {

	private const MENU_SLUG   = 'civime-reminders';
	private const PARENT_SLUG = 'civime';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_post_civime_delete_reminder', [ $this, 'handle_delete' ] );
	}

	/**
	 * Register the Reminders submenu under the CiviMe parent menu.
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Reminders', 'civime-core' ),
			__( 'Reminders', 'civime-core' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Handle hard-delete reminder form submission.
	 */
	public function handle_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_delete_reminder' );

		$reminder_id = isset( $_POST['reminder_id'] ) ? absint( $_POST['reminder_id'] ) : 0;
		if ( ! $reminder_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$result = civime_api()->delete_admin_reminder( $reminder_id );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'civime_deleted' => '1' ] ) );
		exit;
	}

	/**
	 * Output the reminders list page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require CIVIME_CORE_PATH . 'admin/reminders-page.php';
	}

	/**
	 * Build an admin URL for the reminders page with optional query args.
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
