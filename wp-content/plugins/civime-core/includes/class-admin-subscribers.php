<?php
/**
 * CiviMe Admin Subscribers
 *
 * Registers the top-level CiviMe admin menu and provides full CRUD for
 * subscribers via the Access100 API admin endpoints.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Admin_Subscribers {

	private const MENU_SLUG = 'civime';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_post_civime_deactivate_subscriber', [ $this, 'handle_deactivate' ] );
		add_action( 'admin_post_civime_create_subscriber', [ $this, 'handle_create' ] );
		add_action( 'admin_post_civime_update_subscriber', [ $this, 'handle_update' ] );
		add_action( 'admin_post_civime_delete_subscriber', [ $this, 'handle_delete' ] );
	}

	/**
	 * Register the top-level CiviMe menu with Subscribers as the first submenu.
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'CiviMe', 'civime-core' ),
			__( 'CiviMe', 'civime-core' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ],
			'dashicons-megaphone',
			30
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Subscribers', 'civime-core' ),
			__( 'Subscribers', 'civime-core' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	// =========================================================================
	// Form handlers
	// =========================================================================

	/**
	 * Handle "Add Subscriber" form submission.
	 */
	public function handle_create(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_create_subscriber' );

		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$frequency   = isset( $_POST['frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['frequency'] ) ) : 'immediate';
		$council_ids = isset( $_POST['council_ids'] ) && is_array( $_POST['council_ids'] )
			? array_map( 'absint', $_POST['council_ids'] )
			: [];

		if ( empty( $email ) || empty( $council_ids ) ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'missing_fields' ] ) );
			exit;
		}

		$result = civime_api()->create_admin_subscriber( [
			'email'       => $email,
			'channels'    => [ 'email' ],
			'council_ids' => $council_ids,
			'frequency'   => $frequency,
		] );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'civime_created' => '1' ] ) );
		exit;
	}

	/**
	 * Handle "Edit Subscriber" form submission.
	 */
	public function handle_update(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_update_subscriber' );

		$user_id     = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$frequency   = isset( $_POST['frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['frequency'] ) ) : 'immediate';
		$council_ids = isset( $_POST['council_ids'] ) && is_array( $_POST['council_ids'] )
			? array_map( 'absint', $_POST['council_ids'] )
			: [];

		if ( ! $user_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$data = [
			'email'       => $email,
			'channels'    => [ 'email' ],
			'frequency'   => $frequency,
			'council_ids' => $council_ids,
		];

		$result = civime_api()->update_admin_subscriber( $user_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'civime_updated' => '1' ] ) );
		exit;
	}

	/**
	 * Handle soft-deactivate subscriber form submission.
	 */
	public function handle_deactivate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_deactivate_subscriber' );

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$result = civime_api()->deactivate_admin_subscriber( $user_id );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'civime_deactivated' => '1' ] ) );
		exit;
	}

	/**
	 * Handle hard-delete subscriber form submission.
	 */
	public function handle_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_delete_subscriber' );

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$result = civime_api()->delete_admin_subscriber( $user_id );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'civime_deleted' => '1' ] ) );
		exit;
	}

	// =========================================================================
	// Page renderers
	// =========================================================================

	/**
	 * Output the subscribers list or form page based on the action param.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		if ( $action === 'add' || $action === 'edit' ) {
			require CIVIME_CORE_PATH . 'admin/subscriber-form.php';
		} else {
			require CIVIME_CORE_PATH . 'admin/subscribers-page.php';
		}
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Build an admin URL for the subscribers page with optional query args.
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
