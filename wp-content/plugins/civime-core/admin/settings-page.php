<?php
/**
 * CiviMe Settings Page Template
 *
 * Rendered via CiviMe_Settings::render_settings_page().
 * All output is escaped; $this is not available here — use helper functions.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Run a live health check so the connection status is always current.
$health_response  = civime_api()->get_health();
$health_is_ok     = ! is_wp_error( $health_response );
$cache_was_cleared = isset( $_GET['civime_cleared'] ) && $_GET['civime_cleared'] === '1';
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( $cache_was_cleared ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'CiviMe API cache cleared successfully.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php settings_errors( 'civime_options' ); ?>

	<?php /* ----------------------------------------------------------------
	   Connection Status Banner
	   ---------------------------------------------------------------- */ ?>
	<div class="civime-connection-status" style="margin: 16px 0; padding: 12px 16px; border-left: 4px solid <?php echo $health_is_ok ? '#00a32a' : '#d63638'; ?>; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
		<?php if ( $health_is_ok ) : ?>
			<strong style="color:#00a32a;">&#10003; <?php esc_html_e( 'Connected', 'civime-core' ); ?></strong>
			<?php if ( ! empty( $health_response['status'] ) ) : ?>
				&mdash; <?php echo esc_html( ucfirst( $health_response['status'] ) ); ?>
			<?php endif; ?>
			<?php if ( ! empty( $health_response['version'] ) ) : ?>
				<span style="color:#646970;">(API v<?php echo esc_html( $health_response['version'] ); ?>)</span>
			<?php endif; ?>
		<?php else : ?>
			<strong style="color:#d63638;">&#10007; <?php esc_html_e( 'Connection Failed', 'civime-core' ); ?></strong>
			&mdash; <?php echo esc_html( $health_response->get_error_message() ); ?>
		<?php endif; ?>
	</div>

	<?php /* ----------------------------------------------------------------
	   Main Settings Form
	   ---------------------------------------------------------------- */ ?>
	<form method="post" action="options.php">
		<?php
		settings_fields( 'civime_options' );
		do_settings_sections( 'civime-settings' );
		submit_button( __( 'Save Settings', 'civime-core' ) );
		?>
	</form>

	<hr>

	<?php /* ----------------------------------------------------------------
	   Cache Management
	   ---------------------------------------------------------------- */ ?>
	<h2><?php esc_html_e( 'Cache Management', 'civime-core' ); ?></h2>
	<p><?php esc_html_e( 'Clear all cached API responses stored as WordPress transients. Use this after updating data upstream or when debugging stale responses.', 'civime-core' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="civime_clear_cache">
		<?php wp_nonce_field( 'civime_clear_cache' ); ?>
		<?php submit_button( __( 'Clear Cache', 'civime-core' ), 'secondary', 'civime_clear_cache_btn', false ); ?>
	</form>

	<?php /* ----------------------------------------------------------------
	   Health Response Detail (visible only when connected)
	   ---------------------------------------------------------------- */ ?>
	<?php if ( $health_is_ok && count( $health_response ) > 0 ) : ?>
		<hr>
		<h2><?php esc_html_e( 'API Health Details', 'civime-core' ); ?></h2>
		<table class="widefat striped" style="max-width:600px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Field', 'civime-core' ); ?></th>
					<th><?php esc_html_e( 'Value', 'civime-core' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $health_response as $field => $value ) : ?>
					<tr>
						<td><code><?php echo esc_html( $field ); ?></code></td>
						<td><?php echo esc_html( is_array( $value ) ? wp_json_encode( $value ) : (string) $value ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Plugin metadata footer
	   ---------------------------------------------------------------- */ ?>
	<hr>
	<p style="color:#646970;">
		<?php printf(
			/* translators: %s = version number */
			esc_html__( 'CiviMe Core v%s', 'civime-core' ),
			esc_html( CIVIME_CORE_VERSION )
		); ?>
	</p>
</div>
