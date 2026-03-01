<?php
/**
 * CiviMe Subscriber Add/Edit Form Template
 *
 * Rendered via CiviMe_Admin_Subscribers::render_page() when action=add|edit.
 * All output is escaped; $this is not available here — use helper functions.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_slug = 'civime';
$action    = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'add';
$is_edit   = $action === 'edit';
$user_id   = $is_edit && isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

// Defaults for form fields
$form_email     = '';
$form_frequency = 'immediate';
$form_councils  = [];

// If editing, load existing subscriber data
if ( $is_edit && $user_id ) {
	$sub_response = civime_api()->get_admin_subscribers( [ 'q' => '', 'limit' => 100 ] );
	if ( ! is_wp_error( $sub_response ) ) {
		$all_subs = $sub_response['data']['subscribers'] ?? [];
		foreach ( $all_subs as $sub ) {
			if ( (int) $sub['user_id'] === $user_id ) {
				$form_email = $sub['email'] ?? '';
				$subs       = $sub['subscriptions'] ?? [];
				foreach ( $subs as $s ) {
					if ( $s['active'] ) {
						$form_councils[] = (int) $s['council_id'];
						if ( $form_frequency === 'immediate' && ! empty( $s['frequency'] ) ) {
							$form_frequency = $s['frequency'];
						}
					}
				}
				break;
			}
		}
	}
}

// Fetch all councils for checkboxes
$councils_response = civime_api()->get_councils( [ 'limit' => 200 ] );
$councils          = [];
if ( ! is_wp_error( $councils_response ) ) {
	$councils = $councils_response['data']['councils'] ?? $councils_response['data'] ?? [];
}

$page_title   = $is_edit ? __( 'Edit Subscriber', 'civime-core' ) : __( 'Add Subscriber', 'civime-core' );
$form_action  = $is_edit ? 'civime_update_subscriber' : 'civime_create_subscriber';
$nonce_action = $is_edit ? 'civime_update_subscriber' : 'civime_create_subscriber';
?>
<div class="wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( $form_action ); ?>">
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">
		<?php endif; ?>
		<?php wp_nonce_field( $nonce_action ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="civime-email"><?php esc_html_e( 'Email', 'civime-core' ); ?></label>
				</th>
				<td>
					<input type="email" id="civime-email" name="email" value="<?php echo esc_attr( $form_email ); ?>" class="regular-text" required>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="civime-frequency"><?php esc_html_e( 'Frequency', 'civime-core' ); ?></label>
				</th>
				<td>
					<select id="civime-frequency" name="frequency">
						<option value="immediate" <?php selected( $form_frequency, 'immediate' ); ?>><?php esc_html_e( 'Immediate', 'civime-core' ); ?></option>
						<option value="daily" <?php selected( $form_frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'civime-core' ); ?></option>
						<option value="weekly" <?php selected( $form_frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'civime-core' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Councils', 'civime-core' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Select councils', 'civime-core' ); ?></legend>
						<?php if ( empty( $councils ) ) : ?>
							<p><?php esc_html_e( 'Unable to load councils from the API.', 'civime-core' ); ?></p>
						<?php else : ?>
							<div style="max-height: 300px; overflow-y: auto; border: 1px solid #dcdcde; padding: 8px; background: #fff;">
								<?php foreach ( $councils as $council ) :
									$cid  = (int) ( $council['id'] ?? 0 );
									$name = $council['name'] ?? '';
									if ( ! $cid || ! $name ) {
										continue;
									}
								?>
									<label style="display: block; padding: 2px 0;">
										<input type="checkbox" name="council_ids[]" value="<?php echo esc_attr( $cid ); ?>"
											<?php checked( in_array( $cid, $form_councils, true ) ); ?>>
										<?php echo esc_html( $name ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</fieldset>
				</td>
			</tr>
		</table>

		<?php submit_button( $is_edit ? __( 'Update Subscriber', 'civime-core' ) : __( 'Add Subscriber', 'civime-core' ) ); ?>

		<p>
			<a href="<?php echo esc_url( add_query_arg( 'page', $page_slug, admin_url( 'admin.php' ) ) ); ?>">
				&larr; <?php esc_html_e( 'Back to Subscribers', 'civime-core' ); ?>
			</a>
		</p>
	</form>
</div>
