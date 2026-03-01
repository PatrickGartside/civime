<?php
/**
 * CiviMe Subscribers Page Template
 *
 * Rendered via CiviMe_Admin_Subscribers::render_page().
 * All output is escaped; $this is not available here — use helper functions.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read filter/pagination params from query string
$current_search    = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$current_status    = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';
$current_confirmed = isset( $_GET['confirmed'] ) ? sanitize_text_field( wp_unslash( $_GET['confirmed'] ) ) : '';
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
if ( $current_status !== 'all' ) {
	$api_args['status'] = $current_status;
}
if ( $current_confirmed !== '' ) {
	$api_args['confirmed'] = $current_confirmed;
}

$response    = civime_api()->get_admin_subscribers( $api_args );
$is_error    = is_wp_error( $response );
$subscribers = ! $is_error ? ( $response['data']['subscribers'] ?? [] ) : [];
$total       = ! $is_error ? ( $response['meta']['total'] ?? 0 ) : 0;

$was_deactivated = isset( $_GET['civime_deactivated'] ) && $_GET['civime_deactivated'] === '1';
$was_created     = isset( $_GET['civime_created'] ) && $_GET['civime_created'] === '1';
$was_updated     = isset( $_GET['civime_updated'] ) && $_GET['civime_updated'] === '1';
$was_deleted     = isset( $_GET['civime_deleted'] ) && $_GET['civime_deleted'] === '1';
$had_error       = isset( $_GET['civime_error'] );
$error_type      = $had_error ? sanitize_text_field( wp_unslash( $_GET['civime_error'] ) ) : '';

// Build base URL for pagination and links
$page_slug = 'civime';
$base_url  = add_query_arg( [
	'page'      => $page_slug,
	'q'         => $current_search,
	'status'    => $current_status,
	'confirmed' => $current_confirmed,
], admin_url( 'admin.php' ) );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Subscribers', 'civime-core' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( [ 'page' => $page_slug, 'action' => 'add' ], admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add Subscriber', 'civime-core' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $was_created ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Subscriber created successfully.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $was_updated ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Subscriber updated successfully.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $was_deleted ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Subscriber permanently deleted.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $was_deactivated ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Subscriber deactivated successfully.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $had_error ) : ?>
		<div class="notice notice-error is-dismissible">
			<p>
			<?php
			if ( $error_type === 'invalid_id' ) {
				esc_html_e( 'Invalid subscriber ID.', 'civime-core' );
			} elseif ( $error_type === 'missing_fields' ) {
				esc_html_e( 'Please fill in all required fields.', 'civime-core' );
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
		<input type="search" id="civime-search" name="q" value="<?php echo esc_attr( $current_search ); ?>" placeholder="<?php esc_attr_e( 'Search email or phone...', 'civime-core' ); ?>" style="width: 220px; vertical-align: middle;">

		<label for="civime-status" class="screen-reader-text"><?php esc_html_e( 'Status', 'civime-core' ); ?></label>
		<select id="civime-status" name="status" style="vertical-align: middle;">
			<option value="all" <?php selected( $current_status, 'all' ); ?>><?php esc_html_e( 'All statuses', 'civime-core' ); ?></option>
			<option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Active', 'civime-core' ); ?></option>
			<option value="inactive" <?php selected( $current_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'civime-core' ); ?></option>
		</select>

		<label for="civime-confirmed" class="screen-reader-text"><?php esc_html_e( 'Confirmed', 'civime-core' ); ?></label>
		<select id="civime-confirmed" name="confirmed" style="vertical-align: middle;">
			<option value="" <?php selected( $current_confirmed, '' ); ?>><?php esc_html_e( 'All confirmation', 'civime-core' ); ?></option>
			<option value="true" <?php selected( $current_confirmed, 'true' ); ?>><?php esc_html_e( 'Confirmed', 'civime-core' ); ?></option>
			<option value="false" <?php selected( $current_confirmed, 'false' ); ?>><?php esc_html_e( 'Unconfirmed', 'civime-core' ); ?></option>
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
				/* translators: %s = subscriber count */
				esc_html( _n( '%s subscriber', '%s subscribers', $total, 'civime-core' ) ),
				esc_html( number_format_i18n( $total ) )
			);
			?>
		</p>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Subscribers Table
	   ---------------------------------------------------------------- */ ?>
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col" style="width: 50px;"><?php esc_html_e( 'ID', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Email', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Confirmed', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Councils', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Channels', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Frequency', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Signed Up', 'civime-core' ); ?></th>
				<th scope="col" style="width: 200px;"><?php esc_html_e( 'Actions', 'civime-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $subscribers ) ) : ?>
				<tr>
					<td colspan="8">
						<?php
						if ( $is_error ) {
							esc_html_e( 'Unable to load subscribers.', 'civime-core' );
						} else {
							esc_html_e( 'No subscribers found.', 'civime-core' );
						}
						?>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $subscribers as $sub ) :
					$subs         = $sub['subscriptions'] ?? [];
					$council_names = array_column( $subs, 'council_name' );
					$has_active   = false;
					$channels_set = [];
					$frequency    = '';
					foreach ( $subs as $s ) {
						if ( $s['active'] ) {
							$has_active = true;
						}
						foreach ( $s['channels'] as $ch ) {
							$channels_set[ $ch ] = true;
						}
						if ( $frequency === '' ) {
							$frequency = $s['frequency'] ?? '';
						}
					}
					$channels_display = implode( ', ', array_keys( $channels_set ) );
					$confirmed = $sub['confirmed_email'] || $sub['confirmed_phone'];
				?>
					<tr>
						<td><?php echo esc_html( $sub['user_id'] ); ?></td>
						<td><?php echo esc_html( $sub['email'] ?: '—' ); ?></td>
						<td>
							<?php if ( $confirmed ) : ?>
								<span style="color: #00a32a;">&#10003;</span>
							<?php else : ?>
								<span style="color: #d63638;">&#10007;</span>
							<?php endif; ?>
						</td>
						<td>
							<?php
							if ( ! empty( $council_names ) ) {
								$council_parts = [];
								foreach ( $subs as $s ) {
									$name = esc_html( $s['council_name'] );
									if ( ! $s['active'] ) {
										$name = '<s style="color:#646970;">' . $name . '</s>';
									}
									$council_parts[] = $name;
								}
								echo implode( ', ', $council_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php echo esc_html( $channels_display ?: '—' ); ?></td>
						<td><?php echo esc_html( ucfirst( $frequency ) ?: '—' ); ?></td>
						<td>
							<?php
							if ( ! empty( $sub['created_at'] ) ) {
								echo esc_html( wp_date( 'M j, Y', strtotime( $sub['created_at'] ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( [
								'page'    => $page_slug,
								'action'  => 'edit',
								'user_id' => $sub['user_id'],
							], admin_url( 'admin.php' ) ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'civime-core' ); ?>
							</a>

							<?php if ( $has_active ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Deactivate all subscriptions for this user?', 'civime-core' ); ?>');">
									<input type="hidden" name="action" value="civime_deactivate_subscriber">
									<input type="hidden" name="user_id" value="<?php echo esc_attr( $sub['user_id'] ); ?>">
									<?php wp_nonce_field( 'civime_deactivate_subscriber' ); ?>
									<button type="submit" class="button button-small">
										<?php esc_html_e( 'Deactivate', 'civime-core' ); ?>
									</button>
								</form>
							<?php endif; ?>

							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Permanently delete this subscriber and all their data? This cannot be undone.', 'civime-core' ); ?>');">
								<input type="hidden" name="action" value="civime_delete_subscriber">
								<input type="hidden" name="user_id" value="<?php echo esc_attr( $sub['user_id'] ); ?>">
								<?php wp_nonce_field( 'civime_delete_subscriber' ); ?>
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
