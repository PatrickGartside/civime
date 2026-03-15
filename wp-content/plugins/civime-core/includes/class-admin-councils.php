<?php
/**
 * CiviMe Admin Councils
 *
 * Registers the Councils submenu under the CiviMe admin menu and provides
 * CRUD handling for council profiles, members, vacancies, and legal authority
 * via the Access100 API admin endpoints.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Admin_Councils {

	private const MENU_SLUG   = 'civime-councils';
	private const PARENT_SLUG = 'civime';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_post_civime_update_council', [ $this, 'handle_update_council' ] );
		add_action( 'admin_post_civime_add_member', [ $this, 'handle_add_member' ] );
		add_action( 'admin_post_civime_delete_member', [ $this, 'handle_delete_member' ] );
		add_action( 'admin_post_civime_add_vacancy', [ $this, 'handle_add_vacancy' ] );
		add_action( 'admin_post_civime_delete_vacancy', [ $this, 'handle_delete_vacancy' ] );
		add_action( 'admin_post_civime_add_authority', [ $this, 'handle_add_authority' ] );
		add_action( 'admin_post_civime_delete_authority', [ $this, 'handle_delete_authority' ] );
	}

	/**
	 * Register the Councils submenu under the CiviMe parent menu.
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Councils', 'civime-core' ),
			__( 'Councils', 'civime-core' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	// =========================================================================
	// Form handlers
	// =========================================================================

	/**
	 * Handle council profile update.
	 */
	public function handle_update_council(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_update_council' );

		$council_id = isset( $_POST['council_id'] ) ? absint( $_POST['council_id'] ) : 0;
		if ( ! $council_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$data = [];

		// Council fields
		if ( isset( $_POST['name'] ) ) {
			$data['name'] = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		}
		if ( isset( $_POST['rss_url'] ) ) {
			$data['rss_url'] = esc_url_raw( wp_unslash( $_POST['rss_url'] ) );
		}
		$data['is_active'] = isset( $_POST['is_active'] );
		if ( isset( $_POST['parent_id'] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST['parent_id'] ) );
			$data['parent_id'] = $val !== '' ? absint( $val ) : null;
		}

		// Profile fields
		$text_fields = [
			'slug', 'entity_type', 'level', 'jurisdiction', 'meeting_schedule',
			'default_location', 'testimony_email', 'contact_email',
			'contact_phone', 'official_website', 'appointment_method',
			'term_length',
		];
		foreach ( $text_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		// Textarea fields (allow newlines)
		$textarea_fields = [
			'plain_description', 'why_care', 'decisions_examples',
			'vacancy_info', 'testimony_instructions', 'public_comment_info',
		];
		foreach ( $textarea_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$data[ $field ] = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		// Checkbox / numeric fields
		$data['virtual_option'] = isset( $_POST['virtual_option'] );
		if ( isset( $_POST['member_count'] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST['member_count'] ) );
			$data['member_count'] = $val !== '' ? absint( $val ) : null;
		}
		if ( isset( $_POST['vacancy_count'] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST['vacancy_count'] ) );
			$data['vacancy_count'] = $val !== '' ? absint( $val ) : null;
		}

		// Topics
		if ( isset( $_POST['topics'] ) && is_array( $_POST['topics'] ) ) {
			$data['topics'] = array_map( 'absint', $_POST['topics'] );
		} else {
			$data['topics'] = [];
		}

		$result = civime_api()->update_admin_council( $council_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_updated' => '1' ] ) );
		exit;
	}

	/**
	 * Handle add member form submission.
	 */
	public function handle_add_member(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_add_member' );

		$council_id = isset( $_POST['council_id'] ) ? absint( $_POST['council_id'] ) : 0;
		if ( ! $council_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$data = [
			'name'         => isset( $_POST['member_name'] ) ? sanitize_text_field( wp_unslash( $_POST['member_name'] ) ) : '',
			'title'        => isset( $_POST['member_title'] ) ? sanitize_text_field( wp_unslash( $_POST['member_title'] ) ) : '',
			'role'         => isset( $_POST['member_role'] ) ? sanitize_text_field( wp_unslash( $_POST['member_role'] ) ) : 'member',
			'appointed_by' => isset( $_POST['member_appointed_by'] ) ? sanitize_text_field( wp_unslash( $_POST['member_appointed_by'] ) ) : '',
			'term_start'   => isset( $_POST['member_term_start'] ) ? sanitize_text_field( wp_unslash( $_POST['member_term_start'] ) ) : '',
			'term_end'     => isset( $_POST['member_term_end'] ) ? sanitize_text_field( wp_unslash( $_POST['member_term_end'] ) ) : '',
			'status'       => isset( $_POST['member_status'] ) ? sanitize_text_field( wp_unslash( $_POST['member_status'] ) ) : 'active',
		];

		$result = civime_api()->create_admin_member( $council_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_member_added' => '1' ] ) );
		exit;
	}

	/**
	 * Handle delete member form submission.
	 */
	public function handle_delete_member(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_delete_member' );

		$council_id = isset( $_POST['council_id'] ) ? absint( $_POST['council_id'] ) : 0;
		$member_id  = isset( $_POST['member_id'] ) ? absint( $_POST['member_id'] ) : 0;

		if ( ! $council_id || ! $member_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$result = civime_api()->delete_admin_member( $council_id, $member_id );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_member_deleted' => '1' ] ) );
		exit;
	}

	/**
	 * Handle add vacancy form submission.
	 */
	public function handle_add_vacancy(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_add_vacancy' );

		$council_id = isset( $_POST['council_id'] ) ? absint( $_POST['council_id'] ) : 0;
		if ( ! $council_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$data = [
			'seat_description'     => isset( $_POST['vacancy_seat'] ) ? sanitize_text_field( wp_unslash( $_POST['vacancy_seat'] ) ) : '',
			'requirements'         => isset( $_POST['vacancy_requirements'] ) ? sanitize_textarea_field( wp_unslash( $_POST['vacancy_requirements'] ) ) : '',
			'application_url'      => isset( $_POST['vacancy_url'] ) ? esc_url_raw( wp_unslash( $_POST['vacancy_url'] ) ) : '',
			'application_deadline' => isset( $_POST['vacancy_deadline'] ) ? sanitize_text_field( wp_unslash( $_POST['vacancy_deadline'] ) ) : '',
			'appointing_authority' => isset( $_POST['vacancy_authority'] ) ? sanitize_text_field( wp_unslash( $_POST['vacancy_authority'] ) ) : '',
			'status'               => isset( $_POST['vacancy_status'] ) ? sanitize_text_field( wp_unslash( $_POST['vacancy_status'] ) ) : 'open',
		];

		$result = civime_api()->create_admin_vacancy( $council_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_vacancy_added' => '1' ] ) );
		exit;
	}

	/**
	 * Handle delete vacancy form submission.
	 */
	public function handle_delete_vacancy(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_delete_vacancy' );

		$council_id = isset( $_POST['council_id'] ) ? absint( $_POST['council_id'] ) : 0;
		$vacancy_id = isset( $_POST['vacancy_id'] ) ? absint( $_POST['vacancy_id'] ) : 0;

		if ( ! $council_id || ! $vacancy_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$result = civime_api()->delete_admin_vacancy( $council_id, $vacancy_id );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_vacancy_deleted' => '1' ] ) );
		exit;
	}

	/**
	 * Handle add authority form submission.
	 */
	public function handle_add_authority(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_add_authority' );

		$council_id = isset( $_POST['council_id'] ) ? absint( $_POST['council_id'] ) : 0;
		if ( ! $council_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$data = [
			'citation'    => isset( $_POST['authority_citation'] ) ? sanitize_text_field( wp_unslash( $_POST['authority_citation'] ) ) : '',
			'description' => isset( $_POST['authority_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['authority_description'] ) ) : '',
			'url'         => isset( $_POST['authority_url'] ) ? esc_url_raw( wp_unslash( $_POST['authority_url'] ) ) : '',
		];

		$result = civime_api()->create_admin_authority( $council_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_authority_added' => '1' ] ) );
		exit;
	}

	/**
	 * Handle delete authority form submission.
	 */
	public function handle_delete_authority(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-core' ) );
		}

		check_admin_referer( 'civime_delete_authority' );

		$council_id   = isset( $_POST['council_id'] ) ? absint( $_POST['council_id'] ) : 0;
		$authority_id = isset( $_POST['authority_id'] ) ? absint( $_POST['authority_id'] ) : 0;

		if ( ! $council_id || ! $authority_id ) {
			wp_redirect( $this->admin_url( [ 'civime_error' => 'invalid_id' ] ) );
			exit;
		}

		$result = civime_api()->delete_admin_authority( $council_id, $authority_id );

		if ( is_wp_error( $result ) ) {
			wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_error' => 'api_error' ] ) );
			exit;
		}

		wp_redirect( $this->admin_url( [ 'council_id' => $council_id, 'civime_authority_deleted' => '1' ] ) );
		exit;
	}

	// =========================================================================
	// Page renderers
	// =========================================================================

	/**
	 * Output the councils list or edit page based on the council_id param.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$council_id = isset( $_GET['council_id'] ) ? absint( $_GET['council_id'] ) : 0;

		if ( $council_id > 0 ) {
			require CIVIME_CORE_PATH . 'admin/council-edit-page.php';
		} else {
			require CIVIME_CORE_PATH . 'admin/councils-page.php';
		}
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Build an admin URL for the councils page with optional query args.
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
