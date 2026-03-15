<?php
/**
 * CiviMe Councils List Page Template
 *
 * Rendered via CiviMe_Admin_Councils::render_page() when no council_id is set.
 * All output is escaped; $this is not available here — use helper functions.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read filter/pagination params from query string
$current_search       = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
$current_is_active    = isset( $_GET['is_active'] ) ? sanitize_text_field( wp_unslash( $_GET['is_active'] ) ) : '';
$current_jurisdiction = isset( $_GET['jurisdiction'] ) ? sanitize_text_field( wp_unslash( $_GET['jurisdiction'] ) ) : '';
$current_level        = isset( $_GET['level'] ) ? sanitize_text_field( wp_unslash( $_GET['level'] ) ) : '';
$current_page_num     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page             = 25;
$offset               = ( $current_page_num - 1 ) * $per_page;

// Fetch from API
$api_args = [
	'limit'  => $per_page,
	'offset' => $offset,
];
if ( $current_search !== '' ) {
	$api_args['q'] = $current_search;
}
if ( $current_is_active !== '' ) {
	$api_args['is_active'] = $current_is_active;
}
if ( $current_jurisdiction !== '' ) {
	$api_args['jurisdiction'] = $current_jurisdiction;
}
if ( $current_level !== '' ) {
	$api_args['level'] = $current_level;
}

$response = civime_api()->get_admin_councils( $api_args );
$is_error = is_wp_error( $response );
$councils = ! $is_error ? ( $response['data']['councils'] ?? [] ) : [];
$total    = ! $is_error ? ( $response['meta']['total'] ?? 0 ) : 0;

$had_error  = isset( $_GET['civime_error'] );
$error_type = $had_error ? sanitize_text_field( wp_unslash( $_GET['civime_error'] ) ) : '';

// Build base URL for pagination and links
$page_slug = 'civime-councils';
$base_url  = add_query_arg( [
	'page'         => $page_slug,
	'q'            => $current_search,
	'is_active'    => $current_is_active,
	'jurisdiction' => $current_jurisdiction,
	'level'        => $current_level,
], admin_url( 'admin.php' ) );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Councils', 'civime-core' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( $had_error ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'An error occurred. Please try again.', 'civime-core' ); ?></p>
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
		<input type="search" id="civime-search" name="q" value="<?php echo esc_attr( $current_search ); ?>" placeholder="<?php esc_attr_e( 'Search by name...', 'civime-core' ); ?>" style="width: 220px; vertical-align: middle;">

		<label for="civime-active" class="screen-reader-text"><?php esc_html_e( 'Status', 'civime-core' ); ?></label>
		<select id="civime-active" name="is_active" style="vertical-align: middle;">
			<option value="" <?php selected( $current_is_active, '' ); ?>><?php esc_html_e( 'All', 'civime-core' ); ?></option>
			<option value="true" <?php selected( $current_is_active, 'true' ); ?>><?php esc_html_e( 'Active', 'civime-core' ); ?></option>
			<option value="false" <?php selected( $current_is_active, 'false' ); ?>><?php esc_html_e( 'Inactive', 'civime-core' ); ?></option>
		</select>

		<label for="civime-level" class="screen-reader-text"><?php esc_html_e( 'Level', 'civime-core' ); ?></label>
		<select id="civime-level" name="level" style="vertical-align: middle;">
			<option value="" <?php selected( $current_level, '' ); ?>><?php esc_html_e( 'All Levels', 'civime-core' ); ?></option>
			<option value="state" <?php selected( $current_level, 'state' ); ?>><?php esc_html_e( 'State', 'civime-core' ); ?></option>
			<option value="county" <?php selected( $current_level, 'county' ); ?>><?php esc_html_e( 'County', 'civime-core' ); ?></option>
			<option value="neighborhood" <?php selected( $current_level, 'neighborhood' ); ?>><?php esc_html_e( 'Neighborhood', 'civime-core' ); ?></option>
		</select>

		<label for="civime-jurisdiction" class="screen-reader-text"><?php esc_html_e( 'Jurisdiction', 'civime-core' ); ?></label>
		<select id="civime-jurisdiction" name="jurisdiction" style="vertical-align: middle;">
			<option value="" <?php selected( $current_jurisdiction, '' ); ?>><?php esc_html_e( 'All Jurisdictions', 'civime-core' ); ?></option>
			<option value="state" <?php selected( $current_jurisdiction, 'state' ); ?>><?php esc_html_e( 'State', 'civime-core' ); ?></option>
			<option value="honolulu" <?php selected( $current_jurisdiction, 'honolulu' ); ?>><?php esc_html_e( 'Honolulu', 'civime-core' ); ?></option>
			<option value="maui" <?php selected( $current_jurisdiction, 'maui' ); ?>><?php esc_html_e( 'Maui', 'civime-core' ); ?></option>
			<option value="hawaii" <?php selected( $current_jurisdiction, 'hawaii' ); ?>><?php esc_html_e( 'Hawai&#699;i', 'civime-core' ); ?></option>
			<option value="kauai" <?php selected( $current_jurisdiction, 'kauai' ); ?>><?php esc_html_e( 'Kaua&#699;i', 'civime-core' ); ?></option>
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
				/* translators: %s = council count */
				esc_html( _n( '%s council', '%s councils', $total, 'civime-core' ) ),
				esc_html( number_format_i18n( $total ) )
			);
			?>
		</p>
	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Councils Table
	   ---------------------------------------------------------------- */ ?>
	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Name', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Level', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Jurisdiction', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Type', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Meetings', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Active', 'civime-core' ); ?></th>
				<th scope="col" style="width: 80px;"><?php esc_html_e( 'Actions', 'civime-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $councils ) ) : ?>
				<tr>
					<td colspan="7">
						<?php
						if ( $is_error ) {
							esc_html_e( 'Unable to load councils.', 'civime-core' );
						} else {
							esc_html_e( 'No councils found.', 'civime-core' );
						}
						?>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $councils as $council ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $council['name'] ); ?></strong>
						</td>
						<td><?php echo esc_html( ucfirst( $council['level'] ?? '—' ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $council['jurisdiction'] ?? '—' ) ); ?></td>
						<td><?php echo esc_html( $council['entity_type'] ? ucwords( str_replace( '_', ' ', $council['entity_type'] ) ) : '—' ); ?></td>
						<td><?php echo esc_html( $council['meeting_count'] ); ?></td>
						<td>
							<?php if ( $council['is_active'] ) : ?>
								<span style="color: #00a32a;">&#10003;</span>
							<?php else : ?>
								<span style="color: #d63638;">&#10007;</span>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( [ 'page' => $page_slug, 'council_id' => $council['id'] ], admin_url( 'admin.php' ) ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'civime-core' ); ?>
							</a>
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
