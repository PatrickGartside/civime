<?php
/**
 * CiviMe I18n Admin Languages
 *
 * Registers the Languages submenu under the CiviMe admin menu and provides
 * tools for managing translations: .pot regeneration, .mo compilation, and
 * language switcher settings.
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_I18n_Admin_Languages {

	private const MENU_SLUG = 'civime-languages';

	public function __construct() {
		// Priority 20 ensures civime-core has already registered the parent 'civime' menu.
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ], 20 );
		add_action( 'admin_post_civime_i18n_regenerate_pot', [ $this, 'handle_regenerate_pot' ] );
		add_action( 'admin_post_civime_i18n_compile_mo', [ $this, 'handle_compile_mo' ] );
		add_action( 'admin_post_civime_i18n_save_settings', [ $this, 'handle_save_settings' ] );
	}

	/**
	 * Register Languages submenu under CiviMe.
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			'civime',
			__( 'Languages', 'civime-i18n' ),
			__( 'Languages', 'civime-i18n' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Regenerate .pot files for all 5 text domains.
	 */
	public function handle_regenerate_pot(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-i18n' ) );
		}

		check_admin_referer( 'civime_i18n_regenerate_pot' );

		if ( ! function_exists( 'shell_exec' ) ) {
			wp_redirect( $this->admin_url( [ 'civime_i18n_error' => 'shell_exec' ] ) );
			exit;
		}

		$wp_path   = ABSPATH;
		$lang_base = CIVIME_I18N_PATH . 'languages/';

		$commands = [
			'civime'               => [
				'source' => WP_CONTENT_DIR . '/themes/civime',
				'output' => $lang_base . 'civime/civime.pot',
				'domain' => 'civime',
			],
			'civime-core'          => [
				'source' => WP_PLUGIN_DIR . '/civime-core',
				'output' => $lang_base . 'civime-core/civime-core.pot',
				'domain' => 'civime-core',
			],
			'civime-meetings'      => [
				'source' => WP_PLUGIN_DIR . '/civime-meetings',
				'output' => $lang_base . 'civime-meetings/civime-meetings.pot',
				'domain' => 'civime-meetings',
			],
			'civime-notifications' => [
				'source' => WP_PLUGIN_DIR . '/civime-notifications',
				'output' => $lang_base . 'civime-notifications/civime-notifications.pot',
				'domain' => 'civime-notifications',
			],
			'civime-guides'        => [
				'source' => WP_PLUGIN_DIR . '/civime-guides',
				'output' => $lang_base . 'civime-guides/civime-guides.pot',
				'domain' => 'civime-guides',
			],
		];

		$wp_path = escapeshellarg( $wp_path );

		foreach ( $commands as $cmd ) {
			$source = escapeshellarg( $cmd['source'] );
			$output = escapeshellarg( $cmd['output'] );
			$domain = escapeshellarg( $cmd['domain'] );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
			shell_exec( "wp i18n make-pot {$source} {$output} --domain={$domain} --path={$wp_path} 2>&1" );
		}

		update_option( 'civime_i18n_last_pot_regen', time() );

		wp_redirect( $this->admin_url( [ 'civime_i18n_pot_done' => '1' ] ) );
		exit;
	}

	/**
	 * Compile .po files to .mo for all domain directories.
	 */
	public function handle_compile_mo(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-i18n' ) );
		}

		check_admin_referer( 'civime_i18n_compile_mo' );

		if ( ! function_exists( 'shell_exec' ) ) {
			wp_redirect( $this->admin_url( [ 'civime_i18n_error' => 'shell_exec' ] ) );
			exit;
		}

		$lang_base = CIVIME_I18N_PATH . 'languages/';
		$dirs      = [ 'civime', 'civime-core', 'civime-meetings', 'civime-notifications', 'civime-guides' ];

		foreach ( $dirs as $dir ) {
			$path = escapeshellarg( $lang_base . $dir );
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
			shell_exec( "wp i18n make-mo {$path} 2>&1" );
		}

		update_option( 'civime_i18n_last_mo_compile', time() );

		wp_redirect( $this->admin_url( [ 'civime_i18n_mo_done' => '1' ] ) );
		exit;
	}

	/**
	 * Save language switcher settings.
	 */
	public function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'civime-i18n' ) );
		}

		check_admin_referer( 'civime_i18n_save_settings' );

		// Switcher enabled (checkbox — absent means unchecked).
		$enabled = isset( $_POST['civime_i18n_switcher_enabled'] ) ? true : false;
		update_option( 'civime_i18n_switcher_enabled', $enabled );

		// Switcher locations (checkboxes).
		$valid_locations = [ 'header', 'footer', 'mobile' ];
		$locations       = [];
		if ( isset( $_POST['civime_i18n_switcher_locations'] ) && is_array( $_POST['civime_i18n_switcher_locations'] ) ) {
			foreach ( $_POST['civime_i18n_switcher_locations'] as $loc ) {
				$loc = sanitize_text_field( wp_unslash( $loc ) );
				if ( in_array( $loc, $valid_locations, true ) ) {
					$locations[] = $loc;
				}
			}
		}
		update_option( 'civime_i18n_switcher_locations', $locations );

		// Cookie duration.
		$cookie_days = isset( $_POST['civime_i18n_cookie_days'] )
			? (int) sanitize_text_field( wp_unslash( $_POST['civime_i18n_cookie_days'] ) )
			: 7;
		$cookie_days = max( 1, min( 365, $cookie_days ) );
		update_option( 'civime_i18n_cookie_days', $cookie_days );

		wp_redirect( $this->admin_url( [ 'civime_i18n_settings_saved' => '1' ] ) );
		exit;
	}

	/**
	 * Output the languages page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require CIVIME_I18N_PATH . 'admin/languages-page.php';
	}

	/**
	 * Build an admin URL for the languages page with optional query args.
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
