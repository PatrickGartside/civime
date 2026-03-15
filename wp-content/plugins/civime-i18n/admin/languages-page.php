<?php
/**
 * CiviMe I18n Languages Page Template
 *
 * Rendered via CiviMe_I18n_Admin_Languages::render_page().
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Translation stats.
$stats         = CiviMe_I18n_Pot_Parser::get_translation_stats();
$domains       = $stats['domains'];
$languages     = $stats['languages'];
$total_strings = $stats['total_strings'];
$active_count  = $stats['active_count'];
$translating   = $stats['translating_count'];

// Settings.
$switcher_enabled   = get_option( 'civime_i18n_switcher_enabled', true );
$switcher_locations = get_option( 'civime_i18n_switcher_locations', [ 'header', 'footer', 'mobile' ] );
$cookie_days        = (int) get_option( 'civime_i18n_cookie_days', 7 );

// Tool timestamps.
$last_pot_regen  = (int) get_option( 'civime_i18n_last_pot_regen', 0 );
$last_mo_compile = (int) get_option( 'civime_i18n_last_mo_compile', 0 );
$hst             = new DateTimeZone( 'Pacific/Honolulu' );

// Flash params.
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$pot_done       = isset( $_GET['civime_i18n_pot_done'] ) && '1' === $_GET['civime_i18n_pot_done'];
$mo_done        = isset( $_GET['civime_i18n_mo_done'] ) && '1' === $_GET['civime_i18n_mo_done'];
$settings_saved = isset( $_GET['civime_i18n_settings_saved'] ) && '1' === $_GET['civime_i18n_settings_saved'];
$had_error      = isset( $_GET['civime_i18n_error'] );
$error_type     = $had_error ? sanitize_text_field( wp_unslash( $_GET['civime_i18n_error'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$shell_exec_available = function_exists( 'shell_exec' );

// Domain display labels.
$domain_labels = [
	'civime'               => __( 'civime', 'civime-i18n' ),
	'civime-core'          => __( 'civime-core', 'civime-i18n' ),
	'civime-meetings'      => __( 'civime-meetings', 'civime-i18n' ),
	'civime-notifications' => __( 'civime-notifications', 'civime-i18n' ),
	'civime-guides'        => __( 'civime-guides', 'civime-i18n' ),
];
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Languages', 'civime-i18n' ); ?></h1>
	<hr class="wp-header-end">

	<?php /* ----------------------------------------------------------------
	   Flash Notices
	   ---------------------------------------------------------------- */ ?>
	<?php if ( $pot_done ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( '.pot files regenerated successfully.', 'civime-i18n' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $mo_done ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( '.mo files compiled successfully.', 'civime-i18n' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $settings_saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'civime-i18n' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $had_error && 'shell_exec' === $error_type ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'shell_exec is not available on this server. WP-CLI commands cannot be run.', 'civime-i18n' ); ?></p>
		</div>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Section 1 — Overview Cards
	   ---------------------------------------------------------------- */ ?>
	<div style="display: flex; gap: 16px; margin: 16px 0; flex-wrap: wrap;">
		<div style="flex: 1; min-width: 160px; padding: 12px 16px; border-left: 4px solid #2271b1; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Active Languages', 'civime-i18n' ); ?></span><br>
			<strong style="font-size: 20px;"><?php echo esc_html( $active_count ); ?></strong>
			<span style="color: #646970; font-size: 13px;"> / 15</span>
		</div>
		<div style="flex: 1; min-width: 160px; padding: 12px 16px; border-left: 4px solid #00a32a; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Total Strings', 'civime-i18n' ); ?></span><br>
			<strong style="font-size: 20px;"><?php echo esc_html( $total_strings ); ?></strong>
		</div>
		<div style="flex: 1; min-width: 160px; padding: 12px 16px; border-left: 4px solid #dba617; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Translation Progress', 'civime-i18n' ); ?></span><br>
			<strong style="font-size: 20px;"><?php echo esc_html( $translating ); ?></strong>
			<span style="color: #646970; font-size: 13px;"><?php esc_html_e( 'languages started', 'civime-i18n' ); ?></span>
		</div>
		<div style="flex: 1; min-width: 160px; padding: 12px 16px; border-left: 4px solid #8c8f94; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Text Domains', 'civime-i18n' ); ?></span><br>
			<strong style="font-size: 20px;">5</strong>
		</div>
	</div>

	<?php /* ----------------------------------------------------------------
	   Section 2 — Language Status Table
	   ---------------------------------------------------------------- */ ?>
	<h2><?php esc_html_e( 'Language Status', 'civime-i18n' ); ?></h2>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Language', 'civime-i18n' ); ?></th>
				<th scope="col"><?php esc_html_e( 'WP Pack', 'civime-i18n' ); ?></th>
				<?php foreach ( $domain_labels as $label ) : ?>
					<th scope="col"><?php echo esc_html( $label ); ?></th>
				<?php endforeach; ?>
				<th scope="col"><?php esc_html_e( 'Overall', 'civime-i18n' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'civime-i18n' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $languages as $slug => $lang ) : ?>
				<tr>
					<td>
						<strong><?php echo esc_html( $lang['native_name'] ); ?></strong>
						<br><span style="color: #646970; font-size: 12px;"><?php echo esc_html( $slug ); ?> (<?php echo esc_html( $lang['wp_locale'] ); ?>)</span>
					</td>
					<td>
						<?php if ( $lang['has_wp_pack'] ) : ?>
							<span style="color: #00a32a;">&#10003;</span>
						<?php else : ?>
							<span style="color: #646970;">&mdash;</span>
						<?php endif; ?>
					</td>
					<?php foreach ( array_keys( $domain_labels ) as $domain ) :
						$d = $lang['domains'][ $domain ];
					?>
						<td>
							<div style="background: #e0e0e0; border-radius: 3px; height: 8px; width: 60px; display: inline-block; vertical-align: middle;">
								<div style="background: #00a32a; border-radius: 3px; height: 8px; width: <?php echo esc_attr( min( 100, $d['percent'] ) ); ?>%;"></div>
							</div>
							<span style="font-size: 12px; color: #646970; margin-left: 4px;"><?php echo esc_html( $d['percent'] ); ?>%</span>
						</td>
					<?php endforeach; ?>
					<td>
						<div style="background: #e0e0e0; border-radius: 3px; height: 8px; width: 80px; display: inline-block; vertical-align: middle;">
							<div style="background: #00a32a; border-radius: 3px; height: 8px; width: <?php echo esc_attr( min( 100, $lang['overall_percent'] ) ); ?>%;"></div>
						</div>
						<strong style="font-size: 12px; margin-left: 4px;"><?php echo esc_html( $lang['overall_percent'] ); ?>%</strong>
					</td>
					<td>
						<?php
						$status_colors = [
							'complete'    => '#00a32a',
							'partial'     => '#dba617',
							'not_started' => '#646970',
						];
						$status_labels = [
							'complete'    => __( 'Complete', 'civime-i18n' ),
							'partial'     => __( 'Partial', 'civime-i18n' ),
							'not_started' => __( 'Not Started', 'civime-i18n' ),
						];
						$color = $status_colors[ $lang['status'] ] ?? '#646970';
						$label = $status_labels[ $lang['status'] ] ?? $lang['status'];
						?>
						<span style="display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; color: #fff; background: <?php echo esc_attr( $color ); ?>;">
							<?php echo esc_html( $label ); ?>
						</span>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php /* ----------------------------------------------------------------
	   Section 3 — Translation Tools
	   ---------------------------------------------------------------- */ ?>
	<h2 style="margin-top: 24px;"><?php esc_html_e( 'Translation Tools', 'civime-i18n' ); ?></h2>

	<?php if ( ! $shell_exec_available ) : ?>
		<div class="notice notice-warning" style="margin: 8px 0 16px;">
			<p><?php esc_html_e( 'shell_exec is not available on this server. The translation tools below require WP-CLI via shell_exec.', 'civime-i18n' ); ?></p>
		</div>
	<?php endif; ?>

	<div style="display: flex; gap: 24px; margin: 16px 0; flex-wrap: wrap;">
		<div style="flex: 1; min-width: 280px; padding: 16px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #c3c4c7;">
			<h3 style="margin-top: 0;"><?php esc_html_e( 'Regenerate .pot Files', 'civime-i18n' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Scans all 5 plugin/theme source directories and regenerates translation template files.', 'civime-i18n' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="civime_i18n_regenerate_pot">
				<?php wp_nonce_field( 'civime_i18n_regenerate_pot' ); ?>
				<?php submit_button( __( 'Regenerate .pot Files', 'civime-i18n' ), 'secondary', 'civime_i18n_regen', false, $shell_exec_available ? [] : [ 'disabled' => 'disabled' ] ); ?>
			</form>
			<?php if ( $last_pot_regen > 0 ) : ?>
				<p class="description" style="margin-top: 8px;">
					<?php
					printf(
						/* translators: %s: formatted date/time */
						esc_html__( 'Last run: %s HST', 'civime-i18n' ),
						esc_html( wp_date( 'M j, Y g:i A', $last_pot_regen, $hst ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<div style="flex: 1; min-width: 280px; padding: 16px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04); border: 1px solid #c3c4c7;">
			<h3 style="margin-top: 0;"><?php esc_html_e( 'Compile .po → .mo', 'civime-i18n' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Compiles all .po files into binary .mo files so WordPress can load translations.', 'civime-i18n' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="civime_i18n_compile_mo">
				<?php wp_nonce_field( 'civime_i18n_compile_mo' ); ?>
				<?php submit_button( __( 'Compile .mo Files', 'civime-i18n' ), 'secondary', 'civime_i18n_compile', false, $shell_exec_available ? [] : [ 'disabled' => 'disabled' ] ); ?>
			</form>
			<?php if ( $last_mo_compile > 0 ) : ?>
				<p class="description" style="margin-top: 8px;">
					<?php
					printf(
						/* translators: %s: formatted date/time */
						esc_html__( 'Last run: %s HST', 'civime-i18n' ),
						esc_html( wp_date( 'M j, Y g:i A', $last_mo_compile, $hst ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<?php /* ----------------------------------------------------------------
	   Section 4 — Settings
	   ---------------------------------------------------------------- */ ?>
	<h2 style="margin-top: 24px;"><?php esc_html_e( 'Language Switcher Settings', 'civime-i18n' ); ?></h2>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="civime_i18n_save_settings">
		<?php wp_nonce_field( 'civime_i18n_save_settings' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Switcher', 'civime-i18n' ); ?></th>
				<td>
					<label for="civime_i18n_switcher_enabled">
						<input type="checkbox" id="civime_i18n_switcher_enabled" name="civime_i18n_switcher_enabled" value="1" <?php checked( $switcher_enabled ); ?>>
						<?php esc_html_e( 'Show the language switcher on the frontend', 'civime-i18n' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Switcher Locations', 'civime-i18n' ); ?></th>
				<td>
					<fieldset>
						<label style="display: block; margin-bottom: 6px;">
							<input type="checkbox" name="civime_i18n_switcher_locations[]" value="header" <?php checked( in_array( 'header', $switcher_locations, true ) ); ?>>
							<?php esc_html_e( 'Header', 'civime-i18n' ); ?>
						</label>
						<label style="display: block; margin-bottom: 6px;">
							<input type="checkbox" name="civime_i18n_switcher_locations[]" value="footer" <?php checked( in_array( 'footer', $switcher_locations, true ) ); ?>>
							<?php esc_html_e( 'Footer', 'civime-i18n' ); ?>
						</label>
						<label style="display: block; margin-bottom: 6px;">
							<input type="checkbox" name="civime_i18n_switcher_locations[]" value="mobile" <?php checked( in_array( 'mobile', $switcher_locations, true ) ); ?>>
							<?php esc_html_e( 'Mobile Menu', 'civime-i18n' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="civime_i18n_cookie_days"><?php esc_html_e( 'Cookie Duration', 'civime-i18n' ); ?></label>
				</th>
				<td>
					<input type="number" id="civime_i18n_cookie_days" name="civime_i18n_cookie_days" value="<?php echo esc_attr( $cookie_days ); ?>" min="1" max="365" class="small-text">
					<?php esc_html_e( 'days', 'civime-i18n' ); ?>
					<p class="description">
						<?php esc_html_e( 'How long the language preference cookie persists in the browser.', 'civime-i18n' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'civime-i18n' ) ); ?>
	</form>
</div>
