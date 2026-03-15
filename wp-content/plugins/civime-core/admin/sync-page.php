<?php
/**
 * CiviMe Meetings Sync Page Template
 *
 * Rendered via CiviMe_Admin_Sync::render_page().
 * All output is escaped; $this is not available here — use helper functions.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Dashboard stats
$health_response = civime_api()->get_health();
$stats_response  = civime_api()->get_stats();
$api_connected   = ! is_wp_error( $health_response ) && ( $health_response['data']['status'] ?? '' ) === 'ok';
$upcoming_count  = ! is_wp_error( $health_response ) ? (int) ( $health_response['data']['upcoming_meetings_count'] ?? 0 ) : 0;
$total_meetings  = ! is_wp_error( $health_response ) ? (int) ( $health_response['data']['meetings_count'] ?? 0 ) : 0;
$active_subs     = ! is_wp_error( $stats_response ) ? (int) ( $stats_response['data']['active_subscribers'] ?? 0 ) : 0;
$pending_remind  = ! is_wp_error( $stats_response ) ? (int) ( $stats_response['data']['pending_reminders'] ?? 0 ) : 0;
$by_source       = ! is_wp_error( $stats_response ) ? ( $stats_response['data']['meetings_by_source'] ?? [] ) : [];

// All API timestamps are UTC; display in HST
$hst = new DateTimeZone( 'Pacific/Honolulu' );

// Fetch scraper runs
$response = civime_api()->get_admin_scraper_runs( [ 'limit' => 50 ] );
$is_error = is_wp_error( $response );
$runs     = ! $is_error ? ( $response['data']['runs'] ?? [] ) : [];

// Flash params
$was_triggered            = isset( $_GET['civime_triggered'] ) && $_GET['civime_triggered'] === '1';
$nco_was_triggered        = isset( $_GET['civime_nco_triggered'] ) && $_GET['civime_nco_triggered'] === '1';
$hnl_boards_was_triggered = isset( $_GET['civime_hnl_boards_triggered'] ) && $_GET['civime_hnl_boards_triggered'] === '1';
$maui_was_triggered       = isset( $_GET['civime_maui_triggered'] ) && $_GET['civime_maui_triggered'] === '1';
$had_error                = isset( $_GET['civime_error'] );
$error_type               = $had_error ? sanitize_text_field( wp_unslash( $_GET['civime_error'] ) ) : '';

// ─── Helper: compute status card data for a scraper source ───────────
function civime_scraper_status( array $runs, string $source ): array {
	$last_success      = null;
	$last_success_time = null;
	foreach ( $runs as $run ) {
		if ( $run['source'] === $source && $run['status'] === 'success' ) {
			$last_success      = $run;
			$last_success_time = strtotime( $run['last_run'] );
			break;
		}
	}

	$seconds_ago  = $last_success_time ? ( time() - $last_success_time ) : null;
	$minutes_ago  = $seconds_ago !== null ? (int) floor( $seconds_ago / 60 ) : null;
	$border_color = '#d63638';
	if ( $seconds_ago !== null ) {
		if ( $seconds_ago < 3600 ) {
			$border_color = '#00a32a';
		} elseif ( $seconds_ago < 7200 ) {
			$border_color = '#dba617';
		}
	}

	$relative_time = '';
	if ( $minutes_ago !== null ) {
		if ( $minutes_ago < 1 ) {
			$relative_time = __( 'just now', 'civime-core' );
		} elseif ( $minutes_ago < 60 ) {
			$relative_time = sprintf( _n( '%d minute ago', '%d minutes ago', $minutes_ago, 'civime-core' ), $minutes_ago );
		} else {
			$hours_ago     = (int) floor( $minutes_ago / 60 );
			$relative_time = sprintf( _n( '%d hour ago', '%d hours ago', $hours_ago, 'civime-core' ), $hours_ago );
		}
	}

	return [
		'last_success'      => $last_success,
		'last_success_time' => $last_success_time,
		'border_color'      => $border_color,
		'relative_time'     => $relative_time,
	];
}

$ehawaii_status     = civime_scraper_status( $runs, 'rss_scraper' );
$nco_status         = civime_scraper_status( $runs, 'nco_scraper' );
$hnl_boards_status  = civime_scraper_status( $runs, 'honolulu_boards_scraper' );
$maui_status        = civime_scraper_status( $runs, 'maui_scraper' );

// Stale check (eHawaii)
$is_stale = $ehawaii_status['last_success_time'] !== null
	&& ( time() - $ehawaii_status['last_success_time'] ) > 5400;

// 24-hour summary
$twenty_four_hours_ago = time() - 86400;
$summary_runs          = 0;
$summary_new           = 0;
$summary_changed       = 0;
foreach ( $runs as $run ) {
	if ( strtotime( $run['last_run'] ) >= $twenty_four_hours_ago ) {
		$summary_runs++;
		$summary_new     += (int) $run['meetings_new'];
		$summary_changed += (int) $run['meetings_changed'];
	}
}

// Source label mapping
$source_labels = [
	'rss_scraper'              => __( 'eHawaii Scraper', 'civime-core' ),
	'nco_scraper'              => __( 'NCO Scraper', 'civime-core' ),
	'honolulu_boards_scraper'  => __( 'Honolulu Boards Scraper', 'civime-core' ),
	'maui_scraper'             => __( 'Maui Legistar Scraper', 'civime-core' ),
	'notify_cron'              => __( 'Notify Cron', 'civime-core' ),
];
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Meetings Sync', 'civime-core' ); ?></h1>
	<hr class="wp-header-end">

	<?php /* ----------------------------------------------------------------
	   Flash Notices
	   ---------------------------------------------------------------- */ ?>
	<?php if ( $was_triggered ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'eHawaii sync triggered. The scraper is running in the background — results will appear within a minute.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $nco_was_triggered ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'NCO neighborhood board sync triggered. The scraper is running in the background — results will appear within a minute.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $hnl_boards_was_triggered ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Honolulu boards & commissions sync triggered. The scraper is running in the background — results will appear within a minute.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $maui_was_triggered ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Maui County sync triggered. The scraper is running in the background — results will appear within a minute.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $had_error ) : ?>
		<div class="notice notice-error is-dismissible">
			<p>
			<?php
			if ( $error_type === 'too_recent' ) {
				esc_html_e( 'An eHawaii sync was triggered recently. Please wait a minute before trying again.', 'civime-core' );
			} elseif ( $error_type === 'nco_too_recent' ) {
				esc_html_e( 'An NCO sync was triggered recently. Please wait a minute before trying again.', 'civime-core' );
			} elseif ( $error_type === 'nco_api_error' ) {
				esc_html_e( 'An error occurred triggering the NCO scraper. Please try again.', 'civime-core' );
			} elseif ( $error_type === 'hnl_boards_too_recent' ) {
				esc_html_e( 'A Honolulu boards sync was triggered recently. Please wait a minute before trying again.', 'civime-core' );
			} elseif ( $error_type === 'hnl_boards_api_error' ) {
				esc_html_e( 'An error occurred triggering the Honolulu boards scraper. Please try again.', 'civime-core' );
			} elseif ( $error_type === 'maui_too_recent' ) {
				esc_html_e( 'A Maui sync was triggered recently. Please wait a minute before trying again.', 'civime-core' );
			} elseif ( $error_type === 'maui_api_error' ) {
				esc_html_e( 'An error occurred triggering the Maui scraper. Please try again.', 'civime-core' );
			} else {
				esc_html_e( 'An error occurred. Please try again.', 'civime-core' );
			}
			?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $is_error ) : ?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $response->get_error_message() ); ?></p>
		</div>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Dashboard Stats
	   ---------------------------------------------------------------- */ ?>
	<div style="display: flex; gap: 16px; margin: 16px 0;">
		<div style="flex: 1; padding: 12px 16px; border-left: 4px solid <?php echo $api_connected ? '#00a32a' : '#d63638'; ?>; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'API Status', 'civime-core' ); ?></span><br>
			<?php if ( $api_connected ) : ?>
				<strong style="color: #00a32a;">&#10003; <?php esc_html_e( 'Connected', 'civime-core' ); ?></strong>
			<?php else : ?>
				<strong style="color: #d63638;">&#10007; <?php esc_html_e( 'Disconnected', 'civime-core' ); ?></strong>
			<?php endif; ?>
		</div>
		<div style="flex: 1; padding: 12px 16px; border-left: 4px solid #2271b1; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Upcoming Meetings', 'civime-core' ); ?></span><br>
			<strong style="font-size: 20px;"><?php echo esc_html( $upcoming_count ); ?></strong>
		</div>
		<div style="flex: 1; padding: 12px 16px; border-left: 4px solid #2271b1; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Total Meetings', 'civime-core' ); ?></span><br>
			<strong style="font-size: 20px;"><?php echo esc_html( $total_meetings ); ?></strong>
		</div>
		<div style="flex: 1; padding: 12px 16px; border-left: 4px solid #2271b1; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Active Subscribers', 'civime-core' ); ?></span><br>
			<strong style="font-size: 20px;"><?php echo esc_html( $active_subs ); ?></strong>
		</div>
		<div style="flex: 1; padding: 12px 16px; border-left: 4px solid #2271b1; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Upcoming Reminders', 'civime-core' ); ?></span><br>
			<strong style="font-size: 20px;"><?php echo esc_html( $pending_remind ); ?></strong>
		</div>
	</div>

	<?php /* ----------------------------------------------------------------
	   Per-Source Meeting Counts
	   ---------------------------------------------------------------- */ ?>
	<?php if ( ! empty( $by_source ) ) :
		$source_display = [
			'ehawaii'          => __( 'State of Hawaii', 'civime-core' ),
			'nco'              => __( 'Honolulu Neighborhood Board', 'civime-core' ),
			'honolulu_boards'  => __( 'Honolulu County Committee', 'civime-core' ),
			'maui_legistar'    => __( 'Maui County Committee', 'civime-core' ),
		];
	?>
	<div style="display: flex; gap: 16px; margin: 0 0 16px; flex-wrap: wrap;">
		<?php foreach ( $source_display as $source_key => $source_label ) :
			$source_data = $by_source[ $source_key ] ?? [ 'total' => 0, 'upcoming' => 0 ];
		?>
		<div style="flex: 1; min-width: 180px; padding: 12px 16px; border-left: 4px solid #8c8f94; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php echo esc_html( $source_label ); ?></span><br>
			<strong style="font-size: 20px;"><?php echo esc_html( (string) $source_data['upcoming'] ); ?></strong>
			<span style="color: #646970; font-size: 12px;"><?php esc_html_e( 'upcoming', 'civime-core' ); ?></span><br>
			<span style="color: #646970; font-size: 13px;"><?php echo esc_html( (string) $source_data['total'] ); ?> <?php esc_html_e( 'total', 'civime-core' ); ?></span>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Stale Data Warning
	   ---------------------------------------------------------------- */ ?>
	<?php if ( $is_stale ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'The scraper has not completed a successful run in over 90 minutes.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Status Cards
	   ---------------------------------------------------------------- */ ?>
	<div style="display: flex; gap: 16px; margin: 16px 0; flex-wrap: wrap;">
		<div style="flex: 1; min-width: 200px; padding: 12px 16px; border-left: 4px solid <?php echo esc_attr( $ehawaii_status['border_color'] ); ?>; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'eHawaii (State)', 'civime-core' ); ?></span><br>
			<?php if ( $ehawaii_status['last_success'] ) : ?>
				<?php echo esc_html( wp_date( 'M j, g:i A', $ehawaii_status['last_success_time'], $hst ) ); ?> HST
				<span style="color: #646970;">(<?php echo esc_html( $ehawaii_status['relative_time'] ); ?>)</span>
			<?php else : ?>
				<strong style="color: #d63638;"><?php esc_html_e( 'No runs recorded', 'civime-core' ); ?></strong>
			<?php endif; ?>
		</div>
		<div style="flex: 1; min-width: 200px; padding: 12px 16px; border-left: 4px solid <?php echo esc_attr( $nco_status['border_color'] ); ?>; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'NCO (Neighborhood Boards)', 'civime-core' ); ?></span><br>
			<?php if ( $nco_status['last_success'] ) : ?>
				<?php echo esc_html( wp_date( 'M j, g:i A', $nco_status['last_success_time'], $hst ) ); ?> HST
				<span style="color: #646970;">(<?php echo esc_html( $nco_status['relative_time'] ); ?>)</span>
			<?php else : ?>
				<strong style="color: #d63638;"><?php esc_html_e( 'No runs recorded', 'civime-core' ); ?></strong>
			<?php endif; ?>
		</div>
		<div style="flex: 1; min-width: 200px; padding: 12px 16px; border-left: 4px solid <?php echo esc_attr( $hnl_boards_status['border_color'] ); ?>; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Honolulu Boards', 'civime-core' ); ?></span><br>
			<?php if ( $hnl_boards_status['last_success'] ) : ?>
				<?php echo esc_html( wp_date( 'M j, g:i A', $hnl_boards_status['last_success_time'], $hst ) ); ?> HST
				<span style="color: #646970;">(<?php echo esc_html( $hnl_boards_status['relative_time'] ); ?>)</span>
			<?php else : ?>
				<strong style="color: #d63638;"><?php esc_html_e( 'No runs recorded', 'civime-core' ); ?></strong>
			<?php endif; ?>
		</div>
		<div style="flex: 1; min-width: 200px; padding: 12px 16px; border-left: 4px solid <?php echo esc_attr( $maui_status['border_color'] ); ?>; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
			<span style="color: #646970; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Maui Legistar', 'civime-core' ); ?></span><br>
			<?php if ( $maui_status['last_success'] ) : ?>
				<?php echo esc_html( wp_date( 'M j, g:i A', $maui_status['last_success_time'], $hst ) ); ?> HST
				<span style="color: #646970;">(<?php echo esc_html( $maui_status['relative_time'] ); ?>)</span>
			<?php else : ?>
				<strong style="color: #d63638;"><?php esc_html_e( 'No runs recorded', 'civime-core' ); ?></strong>
			<?php endif; ?>
		</div>
	</div>

	<?php /* ----------------------------------------------------------------
	   24-Hour Summary
	   ---------------------------------------------------------------- */ ?>
	<?php if ( ! $is_error && ! empty( $runs ) ) : ?>
		<p style="margin: 8px 0; color: #646970;">
			<?php
			printf(
				/* translators: %1$d = run count, %2$d = new meetings, %3$d = updated meetings */
				esc_html__( 'Last 24 hours: %1$d runs, %2$d new meetings, %3$d updated meetings', 'civime-core' ),
				$summary_runs,
				$summary_new,
				$summary_changed
			);
			?>
		</p>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Trigger Forms
	   ---------------------------------------------------------------- */ ?>
	<div style="margin: 16px 0; display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
		<div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="civime_trigger_sync">
				<?php wp_nonce_field( 'civime_trigger_sync' ); ?>
				<?php submit_button( __( 'Sync eHawaii Meetings', 'civime-core' ), 'primary', 'civime_trigger', false ); ?>
			</form>
			<p class="description" style="margin-top: 4px;">
				<?php esc_html_e( 'State boards & commissions', 'civime-core' ); ?>
			</p>
		</div>
		<div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="civime_trigger_nco_sync">
				<?php wp_nonce_field( 'civime_trigger_nco_sync' ); ?>
				<?php submit_button( __( 'Sync Neighborhood Boards', 'civime-core' ), 'primary', 'civime_trigger_nco', false ); ?>
			</form>
			<p class="description" style="margin-top: 4px;">
				<?php esc_html_e( 'Honolulu neighborhood boards', 'civime-core' ); ?>
			</p>
		</div>
		<div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="civime_trigger_honolulu_boards_sync">
				<?php wp_nonce_field( 'civime_trigger_honolulu_boards_sync' ); ?>
				<?php submit_button( __( 'Sync Honolulu Boards', 'civime-core' ), 'primary', 'civime_trigger_hnl_boards', false ); ?>
			</form>
			<p class="description" style="margin-top: 4px;">
				<?php esc_html_e( 'County boards & commissions', 'civime-core' ); ?>
			</p>
		</div>
		<div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="civime_trigger_maui_sync">
				<?php wp_nonce_field( 'civime_trigger_maui_sync' ); ?>
				<?php submit_button( __( 'Sync Maui Council', 'civime-core' ), 'primary', 'civime_trigger_maui', false ); ?>
			</form>
			<p class="description" style="margin-top: 4px;">
				<?php esc_html_e( 'Maui County Council & committees', 'civime-core' ); ?>
			</p>
		</div>
	</div>

	<?php /* ----------------------------------------------------------------
	   Recent Runs Table
	   ---------------------------------------------------------------- */ ?>
	<h2><?php esc_html_e( 'Recent Runs', 'civime-core' ); ?></h2>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Time', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Source', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Found', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'New', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Changed', 'civime-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $runs ) ) : ?>
				<tr>
					<td colspan="6">
						<?php
						if ( $is_error ) {
							esc_html_e( 'Unable to load scraper runs.', 'civime-core' );
						} else {
							esc_html_e( 'No scraper runs recorded yet.', 'civime-core' );
						}
						?>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $runs as $run ) : ?>
					<tr<?php echo $run['status'] === 'error' ? ' style="background-color: #fcf0f1;"' : ''; ?>>
						<td>
							<?php
							if ( ! empty( $run['last_run'] ) ) {
								echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $run['last_run'] ), $hst ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php echo esc_html( $source_labels[ $run['source'] ] ?? $run['source'] ); ?></td>
						<td>
							<?php if ( $run['status'] === 'success' ) : ?>
								<span style="color: #00a32a;">&#10003;</span>
							<?php else : ?>
								<span style="color: #d63638;">&#10007;</span>
								<?php if ( ! empty( $run['error_message'] ) ) : ?>
									<span style="color: #646970; margin-left: 4px;"><?php echo esc_html( $run['error_message'] ); ?></span>
								<?php endif; ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $run['meetings_found'] ); ?></td>
						<td><?php echo $run['meetings_new'] > 0 ? '<strong>' . esc_html( $run['meetings_new'] ) . '</strong>' : esc_html( $run['meetings_new'] ); ?></td>
						<td><?php echo $run['meetings_changed'] > 0 ? '<strong>' . esc_html( $run['meetings_changed'] ) . '</strong>' : esc_html( $run['meetings_changed'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
