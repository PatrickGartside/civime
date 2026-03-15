<?php
/**
 * CiviMe Council Edit Page Template
 *
 * Rendered via CiviMe_Admin_Councils::render_page() when council_id is set.
 * All output is escaped; $this is not available here — use helper functions.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$council_id = absint( $_GET['council_id'] );
$page_slug  = 'civime-councils';
$list_url   = add_query_arg( [ 'page' => $page_slug ], admin_url( 'admin.php' ) );
$edit_url   = add_query_arg( [ 'page' => $page_slug, 'council_id' => $council_id ], admin_url( 'admin.php' ) );

// Fetch council data
$response = civime_api()->get_admin_council( $council_id );
$is_error = is_wp_error( $response );

if ( $is_error ) : ?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Edit Council', 'civime-core' ); ?></h1>
		<hr class="wp-header-end">
		<div class="notice notice-error">
			<p><?php echo esc_html( $response->get_error_message() ); ?></p>
		</div>
		<p><a href="<?php echo esc_url( $list_url ); ?>">&larr; <?php esc_html_e( 'Back to Councils', 'civime-core' ); ?></a></p>
	</div>
<?php return; endif;

$council   = $response['data'] ?? $response;
$profile   = $council['profile'] ?? [];
$members   = $council['members'] ?? [];
$vacancies = $council['vacancies'] ?? [];
$authority = $council['authority'] ?? [];
$topics    = $council['topics'] ?? [];

// Map current topic IDs for pre-checking
$current_topic_ids = array_map( fn( $t ) => (int) $t['id'], $topics );

// Fetch all topics for the checkbox list
$all_topics_response = civime_api()->get_topics();
$all_topics          = ! is_wp_error( $all_topics_response ) ? ( $all_topics_response['data'] ?? $all_topics_response ) : [];

// Flash params
$was_updated = isset( $_GET['civime_updated'] ) && $_GET['civime_updated'] === '1';
$had_error   = isset( $_GET['civime_error'] );

$member_added     = isset( $_GET['civime_member_added'] ) && $_GET['civime_member_added'] === '1';
$member_deleted   = isset( $_GET['civime_member_deleted'] ) && $_GET['civime_member_deleted'] === '1';
$vacancy_added    = isset( $_GET['civime_vacancy_added'] ) && $_GET['civime_vacancy_added'] === '1';
$vacancy_deleted  = isset( $_GET['civime_vacancy_deleted'] ) && $_GET['civime_vacancy_deleted'] === '1';
$authority_added   = isset( $_GET['civime_authority_added'] ) && $_GET['civime_authority_added'] === '1';
$authority_deleted = isset( $_GET['civime_authority_deleted'] ) && $_GET['civime_authority_deleted'] === '1';

$has_success = $was_updated || $member_added || $member_deleted || $vacancy_added || $vacancy_deleted || $authority_added || $authority_deleted;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $council['name'] ); ?></h1>
	<a href="<?php echo esc_url( $list_url ); ?>" class="page-title-action">&larr; <?php esc_html_e( 'All Councils', 'civime-core' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( $has_success ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
			<?php
			if ( $was_updated ) {
				esc_html_e( 'Council updated successfully.', 'civime-core' );
			} elseif ( $member_added ) {
				esc_html_e( 'Member added.', 'civime-core' );
			} elseif ( $member_deleted ) {
				esc_html_e( 'Member deleted.', 'civime-core' );
			} elseif ( $vacancy_added ) {
				esc_html_e( 'Vacancy added.', 'civime-core' );
			} elseif ( $vacancy_deleted ) {
				esc_html_e( 'Vacancy deleted.', 'civime-core' );
			} elseif ( $authority_added ) {
				esc_html_e( 'Legal authority added.', 'civime-core' );
			} elseif ( $authority_deleted ) {
				esc_html_e( 'Legal authority deleted.', 'civime-core' );
			}
			?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $had_error ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'An error occurred. Please try again.', 'civime-core' ); ?></p>
		</div>
	<?php endif; ?>

	<?php /* ================================================================
	   Main form: Config + Profile + Contact + Details + Topics
	   ================================================================ */ ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="civime_update_council">
		<input type="hidden" name="council_id" value="<?php echo esc_attr( $council_id ); ?>">
		<?php wp_nonce_field( 'civime_update_council' ); ?>

		<?php /* ----------------------------------------------------------------
		   Section 1: Config
		   ---------------------------------------------------------------- */ ?>
		<h2><?php esc_html_e( 'Config', 'civime-core' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="name"><?php esc_html_e( 'Name', 'civime-core' ); ?></label></th>
				<td><input type="text" id="name" name="name" value="<?php echo esc_attr( $council['name'] ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="rss_url"><?php esc_html_e( 'RSS URL', 'civime-core' ); ?></label></th>
				<td><input type="url" id="rss_url" name="rss_url" value="<?php echo esc_attr( $council['rss_url'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Active', 'civime-core' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="is_active" value="1" <?php checked( $council['is_active'] ?? false ); ?>>
						<?php esc_html_e( 'Council is actively scraped', 'civime-core' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="parent_id"><?php esc_html_e( 'Parent ID', 'civime-core' ); ?></label></th>
				<td>
					<input type="number" id="parent_id" name="parent_id" value="<?php echo esc_attr( $council['parent_id'] ?? '' ); ?>" class="small-text" min="0">
					<?php if ( ! empty( $council['parent_name'] ) ) : ?>
						<span class="description"><?php echo esc_html( $council['parent_name'] ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php /* ----------------------------------------------------------------
		   Section 2: Profile
		   ---------------------------------------------------------------- */ ?>
		<h2><?php esc_html_e( 'Profile', 'civime-core' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="slug"><?php esc_html_e( 'Slug', 'civime-core' ); ?></label></th>
				<td><input type="text" id="slug" name="slug" value="<?php echo esc_attr( $profile['slug'] ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="plain_description"><?php esc_html_e( 'Description', 'civime-core' ); ?></label></th>
				<td><textarea id="plain_description" name="plain_description" rows="4" class="large-text"><?php echo esc_textarea( $profile['plain_description'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="entity_type"><?php esc_html_e( 'Entity Type', 'civime-core' ); ?></label></th>
				<td>
					<select id="entity_type" name="entity_type">
						<option value=""><?php esc_html_e( '— Select —', 'civime-core' ); ?></option>
						<?php
						$entity_types = [ 'board', 'commission', 'council', 'committee', 'authority', 'department', 'office', 'task_force', 'neighborhood_board' ];
						foreach ( $entity_types as $type ) :
						?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $profile['entity_type'] ?? '', $type ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $type ) ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="level"><?php esc_html_e( 'Level', 'civime-core' ); ?></label></th>
				<td>
					<select id="level" name="level">
						<option value=""><?php esc_html_e( '— Select —', 'civime-core' ); ?></option>
						<?php
						$levels = [ 'state' => 'State', 'county' => 'County', 'neighborhood' => 'Neighborhood' ];
						foreach ( $levels as $key => $label ) :
						?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $profile['level'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="jurisdiction"><?php esc_html_e( 'Jurisdiction', 'civime-core' ); ?></label></th>
				<td>
					<select id="jurisdiction" name="jurisdiction">
						<option value=""><?php esc_html_e( '— Select —', 'civime-core' ); ?></option>
						<?php
						$jurisdictions = [ 'state' => 'State', 'honolulu' => 'Honolulu', 'maui' => 'Maui', 'hawaii' => "Hawai\u{02BB}i", 'kauai' => "Kaua\u{02BB}i" ];
						foreach ( $jurisdictions as $key => $label ) :
						?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $profile['jurisdiction'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="meeting_schedule"><?php esc_html_e( 'Meeting Schedule', 'civime-core' ); ?></label></th>
				<td><input type="text" id="meeting_schedule" name="meeting_schedule" value="<?php echo esc_attr( $profile['meeting_schedule'] ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="default_location"><?php esc_html_e( 'Default Location', 'civime-core' ); ?></label></th>
				<td><input type="text" id="default_location" name="default_location" value="<?php echo esc_attr( $profile['default_location'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Virtual Option', 'civime-core' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="virtual_option" value="1" <?php checked( $profile['virtual_option'] ?? false ); ?>>
						<?php esc_html_e( 'Meetings available online', 'civime-core' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php /* ----------------------------------------------------------------
		   Section 3: Contact
		   ---------------------------------------------------------------- */ ?>
		<h2><?php esc_html_e( 'Contact', 'civime-core' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="testimony_email"><?php esc_html_e( 'Testimony Email', 'civime-core' ); ?></label></th>
				<td><input type="email" id="testimony_email" name="testimony_email" value="<?php echo esc_attr( $profile['testimony_email'] ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="contact_email"><?php esc_html_e( 'Contact Email', 'civime-core' ); ?></label></th>
				<td><input type="email" id="contact_email" name="contact_email" value="<?php echo esc_attr( $profile['contact_email'] ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="contact_phone"><?php esc_html_e( 'Contact Phone', 'civime-core' ); ?></label></th>
				<td><input type="text" id="contact_phone" name="contact_phone" value="<?php echo esc_attr( $profile['contact_phone'] ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="official_website"><?php esc_html_e( 'Official Website', 'civime-core' ); ?></label></th>
				<td><input type="url" id="official_website" name="official_website" value="<?php echo esc_attr( $profile['official_website'] ?? '' ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="testimony_instructions"><?php esc_html_e( 'Testimony Instructions', 'civime-core' ); ?></label></th>
				<td><textarea id="testimony_instructions" name="testimony_instructions" rows="3" class="large-text"><?php echo esc_textarea( $profile['testimony_instructions'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="public_comment_info"><?php esc_html_e( 'Public Comment Info', 'civime-core' ); ?></label></th>
				<td><textarea id="public_comment_info" name="public_comment_info" rows="3" class="large-text"><?php echo esc_textarea( $profile['public_comment_info'] ?? '' ); ?></textarea></td>
			</tr>
		</table>

		<?php /* ----------------------------------------------------------------
		   Section 4: Details
		   ---------------------------------------------------------------- */ ?>
		<h2><?php esc_html_e( 'Details', 'civime-core' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="appointment_method"><?php esc_html_e( 'Appointment Method', 'civime-core' ); ?></label></th>
				<td><input type="text" id="appointment_method" name="appointment_method" value="<?php echo esc_attr( $profile['appointment_method'] ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="term_length"><?php esc_html_e( 'Term Length', 'civime-core' ); ?></label></th>
				<td><input type="text" id="term_length" name="term_length" value="<?php echo esc_attr( $profile['term_length'] ?? '' ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><label for="member_count"><?php esc_html_e( 'Member Count', 'civime-core' ); ?></label></th>
				<td><input type="number" id="member_count" name="member_count" value="<?php echo esc_attr( $profile['member_count'] ?? '' ); ?>" class="small-text" min="0"></td>
			</tr>
			<tr>
				<th scope="row"><label for="vacancy_count"><?php esc_html_e( 'Vacancy Count', 'civime-core' ); ?></label></th>
				<td><input type="number" id="vacancy_count" name="vacancy_count" value="<?php echo esc_attr( $profile['vacancy_count'] ?? '' ); ?>" class="small-text" min="0"></td>
			</tr>
			<tr>
				<th scope="row"><label for="vacancy_info"><?php esc_html_e( 'Vacancy Info', 'civime-core' ); ?></label></th>
				<td><textarea id="vacancy_info" name="vacancy_info" rows="3" class="large-text"><?php echo esc_textarea( $profile['vacancy_info'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="why_care"><?php esc_html_e( 'Why Care', 'civime-core' ); ?></label></th>
				<td><textarea id="why_care" name="why_care" rows="4" class="large-text"><?php echo esc_textarea( $profile['why_care'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="decisions_examples"><?php esc_html_e( 'Decisions Examples', 'civime-core' ); ?></label></th>
				<td><textarea id="decisions_examples" name="decisions_examples" rows="4" class="large-text"><?php echo esc_textarea( $profile['decisions_examples'] ?? '' ); ?></textarea></td>
			</tr>
		</table>

		<?php /* ----------------------------------------------------------------
		   Section 5: Topics
		   ---------------------------------------------------------------- */ ?>
		<h2><?php esc_html_e( 'Topics', 'civime-core' ); ?></h2>
		<?php if ( ! empty( $all_topics ) ) : ?>
			<fieldset style="margin: 8px 0 16px; padding: 12px 16px; background: #fff; border: 1px solid #c3c4c7;">
				<?php foreach ( $all_topics as $topic ) :
					$topic_id = (int) ( $topic['id'] ?? 0 );
					if ( ! $topic_id ) continue;
				?>
					<label style="display: inline-block; width: 220px; margin: 4px 0;">
						<input type="checkbox" name="topics[]" value="<?php echo esc_attr( $topic_id ); ?>" <?php checked( in_array( $topic_id, $current_topic_ids, true ) ); ?>>
						<?php echo esc_html( $topic['name'] ?? $topic['slug'] ?? '' ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'No topics available.', 'civime-core' ); ?></p>
		<?php endif; ?>

		<?php submit_button( __( 'Save Council', 'civime-core' ) ); ?>
	</form>

	<?php /* ================================================================
	   Section 6: Members
	   ================================================================ */ ?>
	<h2><?php esc_html_e( 'Members', 'civime-core' ); ?></h2>

	<table class="wp-list-table widefat striped" style="margin-bottom: 16px;">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Name', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Title', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Role', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Term', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'civime-core' ); ?></th>
				<th scope="col" style="width: 80px;"><?php esc_html_e( 'Actions', 'civime-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $members ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'No members.', 'civime-core' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $members as $member ) : ?>
					<tr>
						<td><?php echo esc_html( $member['name'] ); ?></td>
						<td><?php echo esc_html( $member['title'] ?: '—' ); ?></td>
						<td><?php echo esc_html( ucfirst( $member['role'] ?? 'member' ) ); ?></td>
						<td>
							<?php
							$start = $member['term_start'] ?? '';
							$end   = $member['term_end'] ?? '';
							if ( $start || $end ) {
								echo esc_html( ( $start ?: '?' ) . ' – ' . ( $end ?: '?' ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php echo esc_html( ucfirst( $member['status'] ?? '—' ) ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this member?', 'civime-core' ); ?>');">
								<input type="hidden" name="action" value="civime_delete_member">
								<input type="hidden" name="council_id" value="<?php echo esc_attr( $council_id ); ?>">
								<input type="hidden" name="member_id" value="<?php echo esc_attr( $member['id'] ); ?>">
								<?php wp_nonce_field( 'civime_delete_member' ); ?>
								<button type="submit" class="button button-small" style="color: #d63638;"><?php esc_html_e( 'Delete', 'civime-core' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<details style="margin-bottom: 24px;">
		<summary style="cursor: pointer; font-weight: 600;"><?php esc_html_e( 'Add Member', 'civime-core' ); ?></summary>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 12px; padding: 12px 16px; background: #fff; border: 1px solid #c3c4c7;">
			<input type="hidden" name="action" value="civime_add_member">
			<input type="hidden" name="council_id" value="<?php echo esc_attr( $council_id ); ?>">
			<?php wp_nonce_field( 'civime_add_member' ); ?>

			<p>
				<label for="member_name"><?php esc_html_e( 'Name', 'civime-core' ); ?></label><br>
				<input type="text" id="member_name" name="member_name" class="regular-text" required>
			</p>
			<p>
				<label for="member_title"><?php esc_html_e( 'Title', 'civime-core' ); ?></label><br>
				<input type="text" id="member_title" name="member_title" class="regular-text">
			</p>
			<p>
				<label for="member_role"><?php esc_html_e( 'Role', 'civime-core' ); ?></label><br>
				<select id="member_role" name="member_role">
					<option value="member"><?php esc_html_e( 'Member', 'civime-core' ); ?></option>
					<option value="chair"><?php esc_html_e( 'Chair', 'civime-core' ); ?></option>
					<option value="vice-chair"><?php esc_html_e( 'Vice Chair', 'civime-core' ); ?></option>
					<option value="ex-officio"><?php esc_html_e( 'Ex-Officio', 'civime-core' ); ?></option>
				</select>
			</p>
			<p>
				<label for="member_appointed_by"><?php esc_html_e( 'Appointed By', 'civime-core' ); ?></label><br>
				<input type="text" id="member_appointed_by" name="member_appointed_by" class="regular-text">
			</p>
			<p>
				<label for="member_term_start"><?php esc_html_e( 'Term Start', 'civime-core' ); ?></label><br>
				<input type="date" id="member_term_start" name="member_term_start">
			</p>
			<p>
				<label for="member_term_end"><?php esc_html_e( 'Term End', 'civime-core' ); ?></label><br>
				<input type="date" id="member_term_end" name="member_term_end">
			</p>
			<p>
				<label for="member_status"><?php esc_html_e( 'Status', 'civime-core' ); ?></label><br>
				<select id="member_status" name="member_status">
					<option value="active"><?php esc_html_e( 'Active', 'civime-core' ); ?></option>
					<option value="inactive"><?php esc_html_e( 'Inactive', 'civime-core' ); ?></option>
					<option value="vacant"><?php esc_html_e( 'Vacant', 'civime-core' ); ?></option>
				</select>
			</p>

			<?php submit_button( __( 'Add Member', 'civime-core' ), 'secondary', 'civime_add', false ); ?>
		</form>
	</details>

	<?php /* ================================================================
	   Section 7: Vacancies
	   ================================================================ */ ?>
	<h2><?php esc_html_e( 'Vacancies', 'civime-core' ); ?></h2>

	<table class="wp-list-table widefat striped" style="margin-bottom: 16px;">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Seat', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Requirements', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Deadline', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'civime-core' ); ?></th>
				<th scope="col" style="width: 80px;"><?php esc_html_e( 'Actions', 'civime-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $vacancies ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No vacancies.', 'civime-core' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $vacancies as $vacancy ) : ?>
					<tr>
						<td><?php echo esc_html( $vacancy['seat_description'] ?: '—' ); ?></td>
						<td><?php echo esc_html( $vacancy['requirements'] ? wp_trim_words( $vacancy['requirements'], 12 ) : '—' ); ?></td>
						<td>
							<?php
							if ( ! empty( $vacancy['application_deadline'] ) ) {
								echo esc_html( wp_date( 'M j, Y', strtotime( $vacancy['application_deadline'] ) ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php echo esc_html( ucfirst( $vacancy['status'] ?? '—' ) ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this vacancy?', 'civime-core' ); ?>');">
								<input type="hidden" name="action" value="civime_delete_vacancy">
								<input type="hidden" name="council_id" value="<?php echo esc_attr( $council_id ); ?>">
								<input type="hidden" name="vacancy_id" value="<?php echo esc_attr( $vacancy['id'] ); ?>">
								<?php wp_nonce_field( 'civime_delete_vacancy' ); ?>
								<button type="submit" class="button button-small" style="color: #d63638;"><?php esc_html_e( 'Delete', 'civime-core' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<details style="margin-bottom: 24px;">
		<summary style="cursor: pointer; font-weight: 600;"><?php esc_html_e( 'Add Vacancy', 'civime-core' ); ?></summary>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 12px; padding: 12px 16px; background: #fff; border: 1px solid #c3c4c7;">
			<input type="hidden" name="action" value="civime_add_vacancy">
			<input type="hidden" name="council_id" value="<?php echo esc_attr( $council_id ); ?>">
			<?php wp_nonce_field( 'civime_add_vacancy' ); ?>

			<p>
				<label for="vacancy_seat"><?php esc_html_e( 'Seat Description', 'civime-core' ); ?></label><br>
				<input type="text" id="vacancy_seat" name="vacancy_seat" class="regular-text">
			</p>
			<p>
				<label for="vacancy_requirements"><?php esc_html_e( 'Requirements', 'civime-core' ); ?></label><br>
				<textarea id="vacancy_requirements" name="vacancy_requirements" rows="3" class="large-text"></textarea>
			</p>
			<p>
				<label for="vacancy_url"><?php esc_html_e( 'Application URL', 'civime-core' ); ?></label><br>
				<input type="url" id="vacancy_url" name="vacancy_url" class="large-text">
			</p>
			<p>
				<label for="vacancy_deadline"><?php esc_html_e( 'Application Deadline', 'civime-core' ); ?></label><br>
				<input type="date" id="vacancy_deadline" name="vacancy_deadline">
			</p>
			<p>
				<label for="vacancy_authority"><?php esc_html_e( 'Appointing Authority', 'civime-core' ); ?></label><br>
				<input type="text" id="vacancy_authority" name="vacancy_authority" class="regular-text">
			</p>
			<p>
				<label for="vacancy_status"><?php esc_html_e( 'Status', 'civime-core' ); ?></label><br>
				<select id="vacancy_status" name="vacancy_status">
					<option value="open"><?php esc_html_e( 'Open', 'civime-core' ); ?></option>
					<option value="closed"><?php esc_html_e( 'Closed', 'civime-core' ); ?></option>
					<option value="filled"><?php esc_html_e( 'Filled', 'civime-core' ); ?></option>
				</select>
			</p>

			<?php submit_button( __( 'Add Vacancy', 'civime-core' ), 'secondary', 'civime_add', false ); ?>
		</form>
	</details>

	<?php /* ================================================================
	   Section 8: Legal Authority
	   ================================================================ */ ?>
	<h2><?php esc_html_e( 'Legal Authority', 'civime-core' ); ?></h2>

	<table class="wp-list-table widefat striped" style="margin-bottom: 16px;">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Citation', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Description', 'civime-core' ); ?></th>
				<th scope="col"><?php esc_html_e( 'URL', 'civime-core' ); ?></th>
				<th scope="col" style="width: 80px;"><?php esc_html_e( 'Actions', 'civime-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $authority ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No legal authority records.', 'civime-core' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $authority as $auth ) : ?>
					<tr>
						<td><?php echo esc_html( $auth['citation'] ); ?></td>
						<td><?php echo esc_html( $auth['description'] ? wp_trim_words( $auth['description'], 15 ) : '—' ); ?></td>
						<td>
							<?php if ( ! empty( $auth['url'] ) ) : ?>
								<a href="<?php echo esc_url( $auth['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Link', 'civime-core' ); ?></a>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this authority record?', 'civime-core' ); ?>');">
								<input type="hidden" name="action" value="civime_delete_authority">
								<input type="hidden" name="council_id" value="<?php echo esc_attr( $council_id ); ?>">
								<input type="hidden" name="authority_id" value="<?php echo esc_attr( $auth['id'] ); ?>">
								<?php wp_nonce_field( 'civime_delete_authority' ); ?>
								<button type="submit" class="button button-small" style="color: #d63638;"><?php esc_html_e( 'Delete', 'civime-core' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<details style="margin-bottom: 24px;">
		<summary style="cursor: pointer; font-weight: 600;"><?php esc_html_e( 'Add Authority', 'civime-core' ); ?></summary>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 12px; padding: 12px 16px; background: #fff; border: 1px solid #c3c4c7;">
			<input type="hidden" name="action" value="civime_add_authority">
			<input type="hidden" name="council_id" value="<?php echo esc_attr( $council_id ); ?>">
			<?php wp_nonce_field( 'civime_add_authority' ); ?>

			<p>
				<label for="authority_citation"><?php esc_html_e( 'Citation', 'civime-core' ); ?></label><br>
				<input type="text" id="authority_citation" name="authority_citation" class="regular-text" required>
			</p>
			<p>
				<label for="authority_description"><?php esc_html_e( 'Description', 'civime-core' ); ?></label><br>
				<textarea id="authority_description" name="authority_description" rows="3" class="large-text"></textarea>
			</p>
			<p>
				<label for="authority_url"><?php esc_html_e( 'URL', 'civime-core' ); ?></label><br>
				<input type="url" id="authority_url" name="authority_url" class="large-text">
			</p>

			<?php submit_button( __( 'Add Authority', 'civime-core' ), 'secondary', 'civime_add', false ); ?>
		</form>
	</details>
</div>
