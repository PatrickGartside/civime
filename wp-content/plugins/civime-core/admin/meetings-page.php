<?php
/**
 * CiviMe Meetings Page Template
 *
 * Rendered via CiviMe_Admin_Meetings::render_page().
 * All output is escaped; $this is not available here — use helper functions.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read filter/pagination params from query string
$current_search     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$current_council    = isset( $_GET['council_id'] ) ? absint( $_GET['council_id'] ) : 0;
$current_date_from  = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : wp_date( 'Y-m-d' );
$current_date_to    = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
$current_status     = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$current_order      = isset( $_GET['order'] ) && strtolower( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) === 'desc' ? 'desc' : 'asc';
$current_page_num   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page           = 25;
$offset             = ( $current_page_num - 1 ) * $per_page;

// Allow clearing the date_from filter explicitly
if ( isset( $_GET['date_from'] ) && $_GET['date_from'] === '' ) {
	$current_date_from = '';
}

// Fetch meetings from API
$api_args = [
	'limit'  => $per_page,
	'offset' => $offset,
];
if ( $current_search !== '' ) {
	$api_args['q'] = $current_search;
}
if ( $current_council > 0 ) {
	$api_args['council_id'] = $current_council;
}
if ( $current_date_from !== '' ) {
	$api_args['date_from'] = $current_date_from;
}
if ( $current_date_to !== '' ) {
	$api_args['date_to'] = $current_date_to;
}
if ( $current_status !== '' ) {
	$api_args['status'] = $current_status;
}
if ( $current_order === 'desc' ) {
	$api_args['order'] = 'desc';
}

$response = civime_api()->get_admin_meetings( $api_args );
$is_error = is_wp_error( $response );
$meetings = ! $is_error ? ( $response['data']['meetings'] ?? [] ) : [];
$total    = ! $is_error ? ( $response['meta']['total'] ?? 0 ) : 0;

// Fetch councils for the dropdown (cached via public endpoint)
$councils_response = civime_api()->get_councils( [ 'limit' => 200 ] );
$councils_list     = ! is_wp_error( $councils_response ) ? ( $councils_response['data']['councils'] ?? [] ) : [];

// Build base URL for pagination
$page_slug = 'civime-meetings';
$base_url  = add_query_arg( [
	'page'       => $page_slug,
	'q'          => $current_search,
	'council_id' => $current_council ?: '',
	'date_from'  => $current_date_from,
	'date_to'    => $current_date_to,
	'status'     => $current_status,
	'order'      => $current_order !== 'asc' ? $current_order : '',
], admin_url( 'admin.php' ) );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Meetings', 'civime-core' ); ?></h1>
	<?php
	$upcoming_url = admin_url( 'admin.php?page=civime-meetings' );
	$yesterday    = wp_date( 'Y-m-d', strtotime( '-1 day' ) );
	$archive_url  = admin_url( 'admin.php?page=civime-meetings&date_from=&date_to=' . $yesterday . '&order=desc' );

	// Determine which button is active: archive if date_to is set and date_from is empty
	$is_archive = ( $current_date_from === '' && $current_date_to !== '' );
	?>
	<a href="<?php echo esc_url( $upcoming_url ); ?>" class="page-title-action<?php echo ! $is_archive ? ' current' : ''; ?>">
		<?php esc_html_e( 'Upcoming Meetings', 'civime-core' ); ?>
	</a>
	<a href="<?php echo esc_url( $archive_url ); ?>" class="page-title-action<?php echo $is_archive ? ' current' : ''; ?>">
		<?php esc_html_e( 'Meeting Archive', 'civime-core' ); ?>
	</a>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin: 0; padding: 0;">
		<input type="hidden" name="action" value="civime_check_meeting_links">
		<?php wp_nonce_field( 'civime_check_meeting_links' ); ?>
		<button type="submit" class="page-title-action"><?php esc_html_e( 'Check Links', 'civime-core' ); ?></button>
	</form>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['civime_updated'] ) && $_GET['civime_updated'] === '1' ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Meeting updated successfully.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['civime_checked'] ) && $_GET['civime_checked'] === '1' && ! isset( $_GET['civime_error'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Link check complete. Results are shown below.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['civime_error'] ) ) :
		$error_code = sanitize_text_field( wp_unslash( $_GET['civime_error'] ) );
		$error_messages = [
			'check_failed'  => __( 'Link check failed. The API may be unavailable — try again later.', 'civime-core' ),
			'update_failed' => __( 'Meeting update failed. Please try again.', 'civime-core' ),
			'invalid_input' => __( 'Invalid input. Please provide a meeting ID and new state ID.', 'civime-core' ),
		];
		$error_message = $error_messages[ $error_code ] ?? __( 'An unknown error occurred.', 'civime-core' );
	?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error_message ); ?></p>
		</div>
	<?php endif; ?>

	<?php
	/* ----------------------------------------------------------------
	   Broken Links Results
	   ---------------------------------------------------------------- */
	$broken_links_data = get_transient( 'civime_broken_links' );
	if ( $broken_links_data !== false ) :
		$broken  = $broken_links_data['data']['broken'] ?? $broken_links_data['broken'] ?? [];
		$checked = $broken_links_data['data']['checked'] ?? $broken_links_data['checked'] ?? 0;
	?>
		<?php if ( empty( $broken ) ) : ?>
			<div class="notice notice-success" style="margin: 16px 0;">
				<p>
					<?php
					printf(
						/* translators: %s = number of links checked */
						esc_html__( 'All %s links are working.', 'civime-core' ),
						esc_html( number_format_i18n( $checked ) )
					);
					?>
				</p>
			</div>
		<?php else : ?>
			<div class="notice notice-error" style="margin: 16px 0;">
				<p>
					<?php
					printf(
						/* translators: %1$s = broken count, %2$s = total checked */
						esc_html__( '%1$s broken out of %2$s links checked.', 'civime-core' ),
						esc_html( number_format_i18n( count( $broken ) ) ),
						esc_html( number_format_i18n( $checked ) )
					);
					?>
				</p>
			</div>
			<table class="wp-list-table widefat striped" style="margin-bottom: 24px;">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'civime-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Title', 'civime-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Council', 'civime-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'HTTP Status', 'civime-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Current State ID', 'civime-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Fix', 'civime-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $broken as $link ) : ?>
						<tr>
							<td>
								<?php
								if ( ! empty( $link['meeting_date'] ) ) {
									echo esc_html( wp_date( 'M j, Y', strtotime( $link['meeting_date'] ) ) );
								} else {
									echo '—';
								}
								?>
							</td>
							<td>
								<?php if ( ! empty( $link['state_id'] ) ) : ?>
									<a href="<?php echo esc_url( home_url( '/meetings/' . $link['state_id'] . '/' ) ); ?>">
										<?php echo esc_html( $link['title'] ?: '—' ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $link['title'] ?: '—' ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $link['council_name'] ?: '—' ); ?></td>
							<td>
								<?php
								if ( ! empty( $link['error'] ) ) {
									echo esc_html( $link['error'] );
								} else {
									echo esc_html( $link['http_status'] );
								}
								?>
							</td>
							<td><code><?php echo esc_html( $link['state_id'] ); ?></code></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: flex; gap: 4px; align-items: center;">
									<input type="hidden" name="action" value="civime_update_meeting">
									<input type="hidden" name="meeting_id" value="<?php echo esc_attr( $link['id'] ); ?>">
									<?php wp_nonce_field( 'civime_update_meeting' ); ?>
									<label for="new_state_id_<?php echo esc_attr( $link['id'] ); ?>" class="screen-reader-text">
										<?php esc_html_e( 'New State ID', 'civime-core' ); ?>
									</label>
									<input type="text" id="new_state_id_<?php echo esc_attr( $link['id'] ); ?>" name="new_state_id" placeholder="<?php esc_attr_e( 'New state ID', 'civime-core' ); ?>" style="width: 110px;" required>
									<?php submit_button( __( 'Update', 'civime-core' ), 'secondary small', '', false ); ?>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $is_error ) : ?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $response->get_error_message() ); ?></p>
		</div>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Filters
	   ---------------------------------------------------------------- */ ?>
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin: 16px 0;">
		<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">

		<label for="civime-search" class="screen-reader-text"><?php esc_html_e( 'Search', 'civime-core' ); ?></label>
		<input type="search" id="civime-search" name="q" value="<?php echo esc_attr( $current_search ); ?>" placeholder="<?php esc_attr_e( 'Search by title...', 'civime-core' ); ?>" style="width: 220px; vertical-align: middle;">

		<label for="civime-council" class="screen-reader-text"><?php esc_html_e( 'Council', 'civime-core' ); ?></label>
		<select id="civime-council" name="council_id" style="vertical-align: middle;">
			<option value="" <?php selected( $current_council, 0 ); ?>><?php esc_html_e( 'All Councils', 'civime-core' ); ?></option>
			<?php foreach ( $councils_list as $council ) : ?>
				<option value="<?php echo esc_attr( $council['id'] ); ?>" <?php selected( $current_council, (int) $council['id'] ); ?>>
					<?php echo esc_html( $council['name'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label for="civime-date-from" class="screen-reader-text"><?php esc_html_e( 'Date from', 'civime-core' ); ?></label>
		<input type="date" id="civime-date-from" name="date_from" value="<?php echo esc_attr( $current_date_from ); ?>" style="vertical-align: middle;">

		<label for="civime-date-to" class="screen-reader-text"><?php esc_html_e( 'Date to', 'civime-core' ); ?></label>
		<input type="date" id="civime-date-to" name="date_to" value="<?php echo esc_attr( $current_date_to ); ?>" style="vertical-align: middle;">

		<label for="civime-status" class="screen-reader-text"><?php esc_html_e( 'Status', 'civime-core' ); ?></label>
		<select id="civime-status" name="status" style="vertical-align: middle;">
			<option value="" <?php selected( $current_status, '' ); ?>><?php esc_html_e( 'All Statuses', 'civime-core' ); ?></option>
			<option value="scheduled" <?php selected( $current_status, 'scheduled' ); ?>><?php esc_html_e( 'Scheduled', 'civime-core' ); ?></option>
			<option value="cancelled" <?php selected( $current_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'civime-core' ); ?></option>
			<option value="completed" <?php selected( $current_status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'civime-core' ); ?></option>
		</select>

		<?php submit_button( __( 'Filter', 'civime-core' ), 'secondary', 'civime_filter', false ); ?>
	</form>

	<?php /* ----------------------------------------------------------------
	   Results Count
	   ---------------------------------------------------------------- */ ?>
	<?php if ( ! $is_error ) : ?>
		<p class="displaying-num" style="margin: 8px 0;">
			<?php
			printf(
				/* translators: %s = meeting count */
				esc_html( _n( '%s meeting', '%s meetings', $total, 'civime-core' ) ),
				esc_html( number_format_i18n( $total ) )
			);
			?>
		</p>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Meetings Table
	   ---------------------------------------------------------------- */ ?>
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Date', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Time', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Title', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Council', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Location', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Added', 'civime-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $meetings ) ) : ?>
				<tr>
					<td colspan="7">
						<?php
						if ( $is_error ) {
							esc_html_e( 'Unable to load meetings.', 'civime-core' );
						} else {
							esc_html_e( 'No meetings found.', 'civime-core' );
						}
						?>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $meetings as $meeting ) : ?>
					<tr>
						<td>
							<?php
							if ( ! empty( $meeting['meeting_date'] ) ) {
								echo esc_html( wp_date( 'M j, Y', strtotime( $meeting['meeting_date'] ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td>
							<?php
							if ( ! empty( $meeting['meeting_time'] ) ) {
								echo esc_html( wp_date( 'g:i A', strtotime( $meeting['meeting_time'] ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td>
							<?php if ( ! empty( $meeting['state_id'] ) ) : ?>
								<a href="<?php echo esc_url( home_url( '/meetings/' . $meeting['state_id'] . '/' ) ); ?>">
									<?php echo esc_html( $meeting['title'] ?: '—' ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $meeting['title'] ?: '—' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $meeting['council_name'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $meeting['location'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $meeting['status'] ?: '—' ); ?></td>
						<td>
							<?php
							if ( ! empty( $meeting['first_seen_at'] ) ) {
								$first_seen = strtotime( $meeting['first_seen_at'] );
								$diff       = time() - $first_seen;
								if ( $diff < DAY_IN_SECONDS ) {
									echo esc_html( human_time_diff( $first_seen ) . ' ago' );
								} else {
									echo esc_html( wp_date( 'M j, Y', $first_seen ) );
								}
							} else {
								echo '—';
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php /* ----------------------------------------------------------------
	   Pagination
	   ---------------------------------------------------------------- */ ?>
	<?php if ( $total > $per_page ) :
		$total_pages = (int) ceil( $total / $per_page );
		$pagination  = paginate_links( [
			'base'      => $base_url . '%_%',
			'format'    => '&paged=%#%',
			'current'   => $current_page_num,
			'total'     => $total_pages,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
		] );
	?>
		<div class="tablenav bottom" style="margin-top: 12px;">
			<div class="tablenav-pages">
				<?php echo $pagination; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links output ?>
			</div>
		</div>
	<?php endif; ?>
</div>
