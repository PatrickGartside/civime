<?php
/**
 * Template: Subscribe Form
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$subscribe = new CiviMe_Notifications_Subscribe();
$form      = $subscribe->get_form_data();
$councils  = $subscribe->get_councils();

get_header();
?>

<main id="main" class="site-main" role="main">

	<header class="page-header">
		<div class="container container--narrow">
			<h1 class="page-header__title"><?php esc_html_e( 'Get Notified', 'civime-notifications' ); ?></h1>
			<p class="page-header__description"><?php esc_html_e( 'Get alerts when councils you follow post new meetings or agendas. Choose your channels, pick your councils, and we\'ll keep you in the loop.', 'civime-notifications' ); ?></p>
		</div>
	</header>

	<div class="section">
		<div class="container container--narrow">

			<?php if ( $subscribe->is_submitted() ) : ?>

				<div class="notif-status notif-status--success" role="status">
					<h2><?php esc_html_e( 'Check Your Inbox', 'civime-notifications' ); ?></h2>
					<p><?php esc_html_e( "We've sent a confirmation message to verify your contact info. Please click the link in your email (or reply YES to the text) to activate your subscription.", 'civime-notifications' ); ?></p>
					<p><?php esc_html_e( "If you don't see it within a few minutes, check your spam folder.", 'civime-notifications' ); ?></p>
					<p>
						<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--primary">
							<?php esc_html_e( 'Browse Meetings', 'civime-notifications' ); ?>
						</a>
					</p>
				</div>

			<?php else : ?>

				<?php if ( $subscribe->has_errors() ) : ?>
					<div class="notif-notice notif-notice--error" role="alert" aria-live="assertive" tabindex="-1">
						<p><strong><?php esc_html_e( 'Please fix the following:', 'civime-notifications' ); ?></strong></p>
						<ul>
							<?php foreach ( $subscribe->get_errors() as $error ) : ?>
								<li><?php echo esc_html( $error ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ( $subscribe->has_councils_error() ) : ?>
					<div class="notif-notice notif-notice--warning" role="alert">
						<p><strong><?php esc_html_e( 'Council data is temporarily unavailable.', 'civime-notifications' ); ?></strong></p>
						<p><?php esc_html_e( "We're having trouble loading the council list. Please try again in a moment.", 'civime-notifications' ); ?></p>
					</div>
				<?php else : ?>

					<form
						method="post"
						action="<?php echo esc_url( home_url( '/meetings/subscribe/' ) ); ?>"
						class="subscribe-form"
						novalidate
					>
						<?php wp_nonce_field( 'civime_subscribe', '_civime_nonce' ); ?>

						<!-- Honeypot -->
						<div class="subscribe-form__hp" aria-hidden="true" inert>
							<label for="subscribe-website"><?php esc_html_e( 'Website', 'civime-notifications' ); ?></label>
							<input type="text" name="website" id="subscribe-website" tabindex="-1" autocomplete="off">
						</div>

						<!-- Channels -->
						<fieldset class="subscribe-form__group">
							<legend class="subscribe-form__legend"><?php esc_html_e( 'How should we reach you?', 'civime-notifications' ); ?></legend>
							<div class="subscribe-form__checks">
								<label class="subscribe-form__check">
									<input
										type="checkbox"
										name="channels[]"
										value="email"
										class="subscribe-form__checkbox"
										data-toggle-target="subscribe-email-field"
										<?php checked( $subscribe->is_channel_selected( 'email' ) ); ?>
									>
									<?php esc_html_e( 'Email', 'civime-notifications' ); ?>
								</label>
								<label class="subscribe-form__check">
									<input
										type="checkbox"
										name="channels[]"
										value="sms"
										class="subscribe-form__checkbox"
										data-toggle-target="subscribe-phone-field"
										<?php checked( $subscribe->is_channel_selected( 'sms' ) ); ?>
									>
									<?php esc_html_e( 'Text Message', 'civime-notifications' ); ?>
								</label>
							</div>
						</fieldset>

						<!-- Email -->
						<div
							class="subscribe-form__field"
							id="subscribe-email-field"
							<?php if ( ! $subscribe->is_channel_selected( 'email' ) ) : ?>hidden<?php endif; ?>
						>
							<label for="subscribe-email" class="subscribe-form__label">
								<?php esc_html_e( 'Email Address', 'civime-notifications' ); ?>
							</label>
							<input
								type="email"
								id="subscribe-email"
								name="email"
								value="<?php echo esc_attr( $form['email'] ); ?>"
								class="subscribe-form__input"
								autocomplete="email"
								placeholder="you@example.com"
							>
						</div>

						<!-- Phone -->
						<div
							class="subscribe-form__field"
							id="subscribe-phone-field"
							<?php if ( ! $subscribe->is_channel_selected( 'sms' ) ) : ?>hidden<?php endif; ?>
						>
							<label for="subscribe-phone" class="subscribe-form__label">
								<?php esc_html_e( 'Phone Number', 'civime-notifications' ); ?>
							</label>
							<input
								type="tel"
								id="subscribe-phone"
								name="phone"
								value="<?php echo esc_attr( $form['phone'] ); ?>"
								class="subscribe-form__input"
								autocomplete="tel"
								placeholder="(808) 555-1234"
							>
							<p class="subscribe-form__hint">
								<?php esc_html_e( 'US numbers only. Standard messaging rates may apply.', 'civime-notifications' ); ?>
							</p>
						</div>

						<!-- Councils -->
						<fieldset class="subscribe-form__group">
							<legend class="subscribe-form__legend"><?php esc_html_e( 'What do you want to hear about?', 'civime-notifications' ); ?></legend>

							<div class="council-picker">
								<label for="council-picker-search" class="screen-reader-text">
									<?php esc_html_e( 'Search councils', 'civime-notifications' ); ?>
								</label>
								<input
									type="search"
									id="council-picker-search"
									class="council-picker__search subscribe-form__input"
									placeholder="<?php esc_attr_e( 'Search councils…', 'civime-notifications' ); ?>"
									aria-controls="council-picker-list"
								>

								<div class="council-picker__list" id="council-picker-list" role="group" aria-label="<?php esc_attr_e( 'Councils', 'civime-notifications' ); ?>">
									<?php if ( empty( $councils ) ) : ?>
										<p class="council-picker__empty"><?php esc_html_e( 'No councils available.', 'civime-notifications' ); ?></p>
									<?php else : ?>
										<?php foreach ( $councils as $council ) : ?>
											<?php $council_id = absint( $council['id'] ); ?>
											<label class="council-picker__item" data-council-name="<?php echo esc_attr( strtolower( $council['name'] ) ); ?>">
												<input
													type="checkbox"
													name="council_ids[]"
													value="<?php echo esc_attr( (string) $council_id ); ?>"
													class="council-picker__checkbox"
													<?php checked( $subscribe->is_council_selected( $council_id ) ); ?>
												>
												<span class="council-picker__name"><?php echo esc_html( $council['name'] ); ?></span>
												<?php if ( ! empty( $council['county'] ) ) : ?>
													<span class="council-picker__county"><?php echo esc_html( ucfirst( $council['county'] ) ); ?></span>
												<?php endif; ?>
											</label>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>

								<p class="council-picker__count" aria-live="polite" aria-atomic="true"></p>
							</div>
						</fieldset>

						<!-- Frequency -->
						<fieldset class="subscribe-form__group">
							<legend class="subscribe-form__legend"><?php esc_html_e( 'How often?', 'civime-notifications' ); ?></legend>
							<div class="subscribe-form__radios">
								<label class="subscribe-form__radio">
									<input
										type="radio"
										name="frequency"
										value="immediate"
										<?php checked( ( $form['frequency'] ?? '' ), 'immediate' ); ?>
									>
									<?php esc_html_e( 'When new meetings are posted', 'civime-notifications' ); ?>
								</label>
								<label class="subscribe-form__radio">
									<input
										type="radio"
										name="frequency"
										value="daily"
										<?php checked( ( $form['frequency'] ?? '' ), 'daily' ); ?>
									>
									<?php esc_html_e( 'Daily digest', 'civime-notifications' ); ?>
								</label>
								<label class="subscribe-form__radio">
									<input
										type="radio"
										name="frequency"
										value="weekly"
										<?php checked( ( $form['frequency'] ?? '' ), 'weekly' ); ?>
									>
									<?php esc_html_e( 'Weekly digest', 'civime-notifications' ); ?>
								</label>
							</div>
						</fieldset>

						<div class="subscribe-form__submit">
							<button type="submit" class="btn btn--primary btn--lg">
								<?php esc_html_e( 'Subscribe', 'civime-notifications' ); ?>
							</button>
						</div>

						<p class="subscribe-form__privacy">
							<?php
							printf(
								/* translators: %s: link to privacy policy */
								esc_html__( 'We never share your info. Unsubscribe anytime. See our %s.', 'civime-notifications' ),
								'<a href="' . esc_url( home_url( '/privacy/' ) ) . '">' . esc_html__( 'privacy policy', 'civime-notifications' ) . '</a>'
							);
							?>
						</p>
					</form>

				<?php endif; ?>

			<?php endif; ?>

		</div>
	</div>

</main>

<?php
get_footer();
