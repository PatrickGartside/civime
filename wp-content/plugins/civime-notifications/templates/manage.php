<?php
/**
 * Template: Manage Notification Preferences
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$manage   = new CiviMe_Notifications_Manage();
$sub      = $manage->get_subscription();
$councils = $manage->get_councils();

get_header();
?>

<main id="main" class="site-main" role="main">

	<header class="page-header">
		<div class="container container--narrow">
			<h1 class="page-header__title"><?php esc_html_e( 'Manage Notifications', 'civime-notifications' ); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container container--narrow">

			<?php if ( $manage->has_error() ) : ?>

				<div class="notif-notice notif-notice--warning" role="alert">
					<p><strong><?php echo esc_html( $manage->get_error_message() ); ?></strong></p>
					<p>
						<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>">
							<?php esc_html_e( 'Browse meetings', 'civime-notifications' ); ?>
						</a>
					</p>
				</div>

			<?php else : ?>

				<?php if ( '' !== $manage->get_message() ) : ?>
					<div
						class="notif-notice notif-notice--<?php echo esc_attr( $manage->get_message_type() ); ?>"
						role="<?php echo 'error' === $manage->get_message_type() ? 'alert' : 'status'; ?>"
						<?php if ( 'error' === $manage->get_message_type() ) : ?>aria-live="assertive"<?php endif; ?>
					>
						<p><?php echo esc_html( $manage->get_message() ); ?></p>
					</div>
				<?php endif; ?>

				<form
					method="post"
					action="<?php echo esc_url( $manage->get_manage_url() ); ?>"
					class="manage-form"
					novalidate
				>
					<?php wp_nonce_field( 'civime_manage_subscription', '_civime_manage_nonce' ); ?>
					<input type="hidden" name="civime_action" value="update">

					<!-- Current subscription info -->
					<?php if ( ! empty( $sub['email'] ) || ! empty( $sub['phone'] ) ) : ?>
					<div class="manage-form__info">
						<h2 class="manage-form__section-title"><?php esc_html_e( 'Your Contact Info', 'civime-notifications' ); ?></h2>
						<dl class="manage-form__details">
							<?php if ( ! empty( $sub['email'] ) ) : ?>
							<div class="manage-form__detail">
								<dt><?php esc_html_e( 'Email', 'civime-notifications' ); ?></dt>
								<dd><?php echo esc_html( $sub['email'] ); ?></dd>
							</div>
							<?php endif; ?>
							<?php if ( ! empty( $sub['phone'] ) ) : ?>
							<div class="manage-form__detail">
								<dt><?php esc_html_e( 'Phone', 'civime-notifications' ); ?></dt>
								<dd><?php echo esc_html( $sub['phone'] ); ?></dd>
							</div>
							<?php endif; ?>
						</dl>
					</div>
					<?php endif; ?>

					<!-- Channels -->
					<fieldset class="manage-form__group">
						<legend class="manage-form__legend"><?php esc_html_e( 'Notification Channels', 'civime-notifications' ); ?></legend>
						<div class="manage-form__checks">
							<label class="manage-form__check">
								<input
									type="checkbox"
									name="channels[]"
									value="email"
									class="manage-form__checkbox"
									<?php checked( $manage->is_channel_active( 'email' ) ); ?>
								>
								<?php esc_html_e( 'Email', 'civime-notifications' ); ?>
							</label>
							<label class="manage-form__check">
								<input
									type="checkbox"
									name="channels[]"
									value="sms"
									class="manage-form__checkbox"
									<?php checked( $manage->is_channel_active( 'sms' ) ); ?>
								>
								<?php esc_html_e( 'Text Message', 'civime-notifications' ); ?>
							</label>
						</div>
					</fieldset>

					<!-- Frequency -->
					<fieldset class="manage-form__group">
						<legend class="manage-form__legend"><?php esc_html_e( 'Notification Frequency', 'civime-notifications' ); ?></legend>
						<div class="manage-form__radios">
							<label class="manage-form__radio">
								<input type="radio" name="frequency" value="immediate" <?php checked( $manage->get_frequency(), 'immediate' ); ?>>
								<?php esc_html_e( 'When new meetings are posted', 'civime-notifications' ); ?>
							</label>
							<label class="manage-form__radio">
								<input type="radio" name="frequency" value="daily" <?php checked( $manage->get_frequency(), 'daily' ); ?>>
								<?php esc_html_e( 'Daily digest', 'civime-notifications' ); ?>
							</label>
							<label class="manage-form__radio">
								<input type="radio" name="frequency" value="weekly" <?php checked( $manage->get_frequency(), 'weekly' ); ?>>
								<?php esc_html_e( 'Weekly digest', 'civime-notifications' ); ?>
							</label>
						</div>
					</fieldset>

					<!-- Councils -->
					<fieldset class="manage-form__group">
						<legend class="manage-form__legend"><?php esc_html_e( 'Councils You Follow', 'civime-notifications' ); ?></legend>

						<div class="council-picker">
							<label for="manage-council-search" class="screen-reader-text">
								<?php esc_html_e( 'Search councils', 'civime-notifications' ); ?>
							</label>
							<input
								type="search"
								id="manage-council-search"
								class="council-picker__search manage-form__input"
								placeholder="<?php esc_attr_e( 'Search councils…', 'civime-notifications' ); ?>"
								aria-controls="manage-council-list"
							>

							<div class="council-picker__list" id="manage-council-list" role="group" aria-label="<?php esc_attr_e( 'Councils', 'civime-notifications' ); ?>">
								<?php foreach ( $councils as $council ) : ?>
									<?php $council_id = absint( $council['id'] ); ?>
									<label class="council-picker__item" data-council-name="<?php echo esc_attr( strtolower( $council['name'] ) ); ?>">
										<input
											type="checkbox"
											name="council_ids[]"
											value="<?php echo esc_attr( (string) $council_id ); ?>"
											class="council-picker__checkbox"
											<?php checked( $manage->is_council_subscribed( $council_id ) ); ?>
										>
										<span class="council-picker__name"><?php echo esc_html( $council['name'] ); ?></span>
										<?php if ( ! empty( $council['county'] ) ) : ?>
											<span class="council-picker__county"><?php echo esc_html( ucfirst( $council['county'] ) ); ?></span>
										<?php endif; ?>
									</label>
								<?php endforeach; ?>
							</div>

							<p class="council-picker__count" aria-live="polite" aria-atomic="true"></p>
						</div>
					</fieldset>

					<div class="manage-form__submit">
						<button type="submit" class="btn btn--primary">
							<?php esc_html_e( 'Save Preferences', 'civime-notifications' ); ?>
						</button>
					</div>
				</form>

				<!-- Unsubscribe -->
				<div class="manage-form__unsubscribe">
					<h2 class="manage-form__section-title"><?php esc_html_e( 'Unsubscribe', 'civime-notifications' ); ?></h2>
					<p><?php esc_html_e( 'You can unsubscribe from all notifications at any time. This will permanently remove your subscription.', 'civime-notifications' ); ?></p>
					<form
						method="post"
						action="<?php echo esc_url( $manage->get_manage_url() ); ?>"
						class="manage-form__unsubscribe-form"
					>
						<?php wp_nonce_field( 'civime_manage_subscription', '_civime_manage_nonce' ); ?>
						<input type="hidden" name="civime_action" value="unsubscribe">
						<button
							type="submit"
							class="btn btn--ghost btn--danger"
							onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to unsubscribe? You will stop receiving all notifications.', 'civime-notifications' ) ); ?>');"
						>
							<?php esc_html_e( 'Unsubscribe from All Notifications', 'civime-notifications' ); ?>
						</button>
					</form>
				</div>

			<?php endif; ?>

		</div>
	</div>

</main>

<?php
get_footer();
