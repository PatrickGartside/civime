<?php
/**
 * CiviMe Reminders Page Template
 *
 * Rendered via CiviMe_Admin_Reminders::render_page().
 * All output is escaped; $this is not available here — use helper functions.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read filter/pagination params from query string
$current_search    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$current_confirmed = isset( $_GET['confirmed'] ) ? sanitize_text_field( wp_unslash( $_GET['confirmed'] ) ) : '';
$current_sent      = isset( $_GET['sent'] ) ? sanitize_text_field( wp_unslash( $_GET['sent'] ) ) : '';
$current_page_num  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page          = 25;
$offset            = ( $current_page_num - 1 ) * $per_page;

// Fetch from API
$api_args = [
	'limit'  => $per_page,
	'offset' => $offset,
];
if ( $current_search !== '' ) {
	$api_args['q'] = $current_search;
}
if ( $current_confirmed !== '' ) {
	$api_args['confirmed'] = $current_confirmed;
}
if ( $current_sent !== '' ) {
	$api_args['sent'] = $current_sent;
}

$response  = civime_api()->get_admin_reminders( $api_args );
$is_error  = is_wp_error( $response );
$reminders = ! $is_error ? ( $response['data']['reminders'] ?? [] ) : [];
$total     = ! $is_error ? ( $response['meta']['total'] ?? 0 ) : 0;

$was_deleted = isset( $_GET['civime_deleted'] ) && $_GET['civime_deleted'] === '1';
$had_error   = isset( $_GET['civime_error'] );
$error_type  = $had_error ? sanitize_text_field( wp_unslash( $_GET['civime_error'] ) ) : '';

// Build base URL for pagination and links
$page_slug = 'civime-reminders';
$base_url  = add_query_arg( [
	'page'      => $page_slug,
	'q'         => $current_search,
	'confirmed' => $current_confirmed,
	'sent'      => $current_sent,
], admin_url( 'admin.php' ) );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Reminders', 'civime-core' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $was_deleted ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Reminder permanently deleted.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $had_error ) : ?>
		<div class="notice notice-error is-dismissible">
			<p>
			<?php
			if ( $error_type === 'invalid_id' ) {
				esc_html_e( 'Invalid reminder ID.', 'civime-core' );
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
	   Filters
	   ---------------------------------------------------------------- */ ?>
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin: 16px 0;">
		<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">

		<label for="civime-search" class="screen-reader-text"><?php esc_html_e( 'Search', 'civime-core' ); ?></label>
		<input type="search" id="civime-search" name="q" value="<?php echo esc_attr( $current_search ); ?>" placeholder="<?php esc_attr_e( 'Search by email...', 'civime-core' ); ?>" style="width: 220px; vertical-align: middle;">

		<label for="civime-confirmed" class="screen-reader-text"><?php esc_html_e( 'Confirmed', 'civime-core' ); ?></label>
		<select id="civime-confirmed" name="confirmed" style="vertical-align: middle;">
			<option value="" <?php selected( $current_confirmed, '' ); ?>><?php esc_html_e( 'All', 'civime-core' ); ?></option>
			<option value="true" <?php selected( $current_confirmed, 'true' ); ?>><?php esc_html_e( 'Confirmed', 'civime-core' ); ?></option>
			<option value="false" <?php selected( $current_confirmed, 'false' ); ?>><?php esc_html_e( 'Unconfirmed', 'civime-core' ); ?></option>
		</select>

		<label for="civime-sent" class="screen-reader-text"><?php esc_html_e( 'Sent', 'civime-core' ); ?></label>
		<select id="civime-sent" name="sent" style="vertical-align: middle;">
			<option value="" <?php selected( $current_sent, '' ); ?>><?php esc_html_e( 'All', 'civime-core' ); ?></option>
			<option value="true" <?php selected( $current_sent, 'true' ); ?>><?php esc_html_e( 'Sent', 'civime-core' ); ?></option>
			<option value="false" <?php selected( $current_sent, 'false' ); ?>><?php esc_html_e( 'Unsent', 'civime-core' ); ?></option>
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
				/* translators: %s = reminder count */
				esc_html( _n( '%s reminder', '%s reminders', $total, 'civime-core' ) ),
				esc_html( number_format_i18n( $total ) )
			);
			?>
		</p>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Reminders Table
	   ---------------------------------------------------------------- */ ?>
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col" style="width: 50px;"><?php esc_html_e( 'ID', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Email', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Meeting', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Council', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Date', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Confirmed', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Sent', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Source', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Signed Up', 'civime-core' ); ?></th>
				<th scope="col" style="width: 100px;"><?php esc_html_e( 'Actions', 'civime-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $reminders ) ) : ?>
				<tr>
					<td colspan="10">
						<?php
						if ( $is_error ) {
							esc_html_e( 'Unable to load reminders.', 'civime-core' );
						} else {
							esc_html_e( 'No reminders found.', 'civime-core' );
						}
						?>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $reminders as $reminder ) : ?>
					<tr>
						<td><?php echo esc_html( $reminder['id'] ); ?></td>
						<td><?php echo esc_html( $reminder['email'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $reminder['meeting_title'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $reminder['council_name'] ?: '—' ); ?></td>
						<td>
							<?php
							if ( ! empty( $reminder['meeting_date'] ) ) {
								echo esc_html( wp_date( 'M j, Y', strtotime( $reminder['meeting_date'] ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td>
							<?php if ( $reminder['confirmed'] ) : ?>
								<span style="color: #00a32a;">&#10003;</span>
							<?php else : ?>
								<span style="color: #d63638;">&#10007;</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $reminder['sent'] ) : ?>
								<span style="color: #00a32a;">&#10003;</span>
								<?php if ( ! empty( $reminder['sent_at'] ) ) : ?>
									<span style="color: #646970; margin-left: 4px;"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $reminder['sent_at'] ) ) ); ?></span>
								<?php endif; ?>
							<?php else : ?>
								<span style="color: #d63638;">&#10007;</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $reminder['source'] ?: '—' ); ?></td>
						<td>
							<?php
							if ( ! empty( $reminder['created_at'] ) ) {
								echo esc_html( wp_date( 'M j, Y', strtotime( $reminder['created_at'] ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Permanently delete this reminder? This cannot be undone.', 'civime-core' ); ?>');">
								<input type="hidden" name="action" value="civime_delete_reminder">
								<input type="hidden" name="reminder_id" value="<?php echo esc_attr( $reminder['id'] ); ?>">
								<?php wp_nonce_field( 'civime_delete_reminder' ); ?>
								<button type="submit" class="button button-small" style="color: #d63638;">
									<?php esc_html_e( 'Delete', 'civime-core' ); ?>
								</button>
							</form>
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
