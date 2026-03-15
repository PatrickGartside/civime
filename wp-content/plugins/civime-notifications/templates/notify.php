<?php
/**
 * Template: Meeting Notify Page
 *
 * Two options:
 * 1. Remind me about this meeting (one-time morning-of email)
 * 2. Subscribe to this council (link to existing subscribe page)
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$notify = new CiviMe_Notifications_Notify();

if ( ! $notify->has_error() ) {
	add_filter(
		'document_title_parts',
		function ( array $title ) use ( $notify ): array {
			$title['title'] = $notify->get_page_title();
			return $title;
		},
		20
	);
}

$m = $notify->get_meeting();

get_header();
?>

<main id="main" class="site-main" role="main">

<?php if ( $notify->is_not_found() ) : ?>

	<header class="page-header">
		<div class="container">
			<h1 class="page-header__title"><?php esc_html_e( 'Meeting Not Found', 'civime-notifications' ); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container">
			<div class="notif-notice notif-notice--warning" role="alert">
				<p><?php esc_html_e( "We couldn't find this meeting. It may have been cancelled or the link may be incorrect.", 'civime-notifications' ); ?></p>
				<p><a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--primary"><?php esc_html_e( 'Browse All Meetings', 'civime-notifications' ); ?></a></p>
			</div>
		</div>
	</div>

<?php elseif ( $notify->has_error() ) : ?>

	<header class="page-header">
		<div class="container">
			<h1 class="page-header__title"><?php esc_html_e( 'Get Notified', 'civime-notifications' ); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container">
			<div class="notif-notice notif-notice--warning" role="alert">
				<p><strong><?php esc_html_e( 'Meeting data is temporarily unavailable.', 'civime-notifications' ); ?></strong></p>
				<p><?php esc_html_e( "We're having trouble loading meeting details. Please try again in a moment.", 'civime-notifications' ); ?></p>
				<p><a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>"><?php esc_html_e( 'Back to meetings', 'civime-notifications' ); ?></a></p>
			</div>
		</div>
	</div>

<?php else : ?>

	<nav class="meeting-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'civime-notifications' ); ?>">
		<div class="container">
			<ol class="meeting-breadcrumb__list">
				<li><a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>"><?php esc_html_e( 'Meetings', 'civime-notifications' ); ?></a></li>
				<?php if ( ! empty( $m['council_name'] ) ) : ?>
					<li><a href="<?php echo esc_url( home_url( '/meetings/' . rawurlencode( $notify->get_state_id() ) . '/' ) ); ?>"><?php echo esc_html( $m['council_name'] ); ?></a></li>
				<?php endif; ?>
				<li aria-current="page"><?php esc_html_e( 'Get Notified', 'civime-notifications' ); ?></li>
			</ol>
		</div>
	</nav>

	<header class="page-header">
		<div class="container">
			<h1 class="page-header__title"><?php esc_html_e( 'Get Notified', 'civime-notifications' ); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container">
			<div class="notify-page">

				<?php if ( $notify->is_submitted() ) : ?>

					<div class="notif-status notif-status--success" role="status">
						<h2><?php esc_html_e( 'Check Your Inbox', 'civime-notifications' ); ?></h2>
						<p><?php esc_html_e( "We've sent a confirmation email. Click the link to activate your reminder — we'll email you the morning of the meeting.", 'civime-notifications' ); ?></p>
						<div class="notif-status__actions">
							<a href="<?php echo esc_url( home_url( '/meetings/' . rawurlencode( $notify->get_state_id() ) . '/' ) ); ?>" class="btn btn--primary">
								<?php esc_html_e( 'Back to Meeting', 'civime-notifications' ); ?>
							</a>
							<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--ghost">
								<?php esc_html_e( 'Browse Meetings', 'civime-notifications' ); ?>
							</a>
						</div>
					</div>

				<?php elseif ( $notify->is_subscribe_submitted() ) : ?>

					<div class="notif-status notif-status--success" role="status">
						<h2><?php esc_html_e( 'Check Your Inbox', 'civime-notifications' ); ?></h2>
						<p><?php esc_html_e( "We've sent a confirmation email. Click the link to activate your subscription and start receiving meeting alerts.", 'civime-notifications' ); ?></p>
						<div class="notif-status__actions">
							<a href="<?php echo esc_url( home_url( '/meetings/' . rawurlencode( $notify->get_state_id() ) . '/' ) ); ?>" class="btn btn--primary">
								<?php esc_html_e( 'Back to Meeting', 'civime-notifications' ); ?>
							</a>
							<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--ghost">
								<?php esc_html_e( 'Browse Meetings', 'civime-notifications' ); ?>
							</a>
						</div>
					</div>

				<?php else : ?>

					<div class="notify-page__grid">

					<!-- Section 1: Remind me about this meeting -->
					<div class="notify-section">
						<h2 class="notify-section__heading"><span aria-hidden="true">&#x1F514;</span> <?php esc_html_e( 'Remind Me About This Meeting', 'civime-notifications' ); ?></h2>

						<?php if ( ! empty( $m['council_name'] ) || $notify->get_formatted_date() ) : ?>
						<div class="notify-page__meeting-info">
							<?php if ( ! empty( $m['council_name'] ) ) : ?>
								<span class="notify-page__council-name"><?php echo esc_html( $m['council_name'] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $m['title'] ) ) : ?>
								<span class="notify-page__meeting-title"><?php echo esc_html( $m['title'] ); ?></span>
							<?php endif; ?>
							<?php if ( $notify->get_formatted_date() ) : ?>
								<span class="notify-page__meeting-date"><?php echo esc_html( $notify->get_formatted_date() ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $m['location'] ) ) : ?>
								<span class="notify-page__meeting-location"><?php echo esc_html( $m['location'] ); ?></span>
							<?php endif; ?>
						</div>
						<?php endif; ?>

						<?php if ( $notify->has_errors() ) : ?>
							<div class="notif-notice notif-notice--error" role="alert" aria-live="assertive" tabindex="-1">
								<p><strong><?php esc_html_e( 'Please fix the following:', 'civime-notifications' ); ?></strong></p>
								<ul>
									<?php foreach ( $notify->get_errors() as $error ) : ?>
										<li><?php echo esc_html( $error ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<form
							method="post"
							action="<?php echo esc_url( home_url( '/meetings/' . rawurlencode( $notify->get_state_id() ) . '/notify/' ) ); ?>"
							class="notify-page__form subscribe-form"
							novalidate
						>
							<?php wp_nonce_field( 'civime_reminder', '_civime_nonce' ); ?>
							<input type="hidden" name="form_action" value="remind">

							<!-- Honeypot -->
							<div class="notify-page__hp" aria-hidden="true" inert>
								<label for="notify-website"><?php esc_html_e( 'Website', 'civime-notifications' ); ?></label>
								<input type="text" name="website" id="notify-website" tabindex="-1" autocomplete="off">
							</div>

							<fieldset class="subscribe-form__group">
								<legend class="subscribe-form__legend"><?php esc_html_e( 'Email Address', 'civime-notifications' ); ?></legend>

								<div class="subscribe-form__field">
									<label for="notify-email" class="subscribe-form__label">
										<?php esc_html_e( 'Email Address', 'civime-notifications' ); ?>
									</label>
									<input
										type="email"
										id="notify-email"
										name="email"
										value="<?php echo esc_attr( $notify->get_form_email() ); ?>"
										class="subscribe-form__input"
										autocomplete="email"
										placeholder="you@example.com"
										required
									>
									<p class="subscribe-form__hint">
										<?php esc_html_e( "We'll send a confirmation email, then a reminder the morning of the meeting.", 'civime-notifications' ); ?>
									</p>
								</div>
							</fieldset>

							<div class="subscribe-form__submit">
								<button type="submit" class="btn btn--primary btn--lg">
									<?php esc_html_e( 'Remind Me', 'civime-notifications' ); ?>
								</button>
							</div>

							<p class="subscribe-form__privacy">
								<?php
								printf(
									/* translators: %s: link to privacy policy */
									esc_html__( 'We never share your info. See our %s.', 'civime-notifications' ),
									'<a href="' . esc_url( home_url( '/privacy/' ) ) . '">' . esc_html__( 'privacy policy', 'civime-notifications' ) . '</a>'
								);
								?>
							</p>
						</form>
					</div>

					<!-- Section 2: Subscribe to council -->
					<?php $sf = $notify->get_subscribe_form(); ?>
					<div class="notify-section">
						<h2 class="notify-section__heading"><span aria-hidden="true">&#x1F4E8;</span> <?php esc_html_e( 'Subscribe to This Council', 'civime-notifications' ); ?></h2>

						<div class="subscribe-form__council-badge">
							<?php if ( ! empty( $m['council_name'] ) ) : ?>
								<span class="subscribe-form__council-badge-name"><?php echo esc_html( $m['council_name'] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $m['county'] ) ) : ?>
								<span class="council-picker__county"><?php echo esc_html( $m['county'] ); ?></span>
							<?php endif; ?>
						</div>

						<?php if ( $notify->has_subscribe_errors() ) : ?>
							<div class="notif-notice notif-notice--error" role="alert" aria-live="assertive" tabindex="-1">
								<p><strong><?php esc_html_e( 'Please fix the following:', 'civime-notifications' ); ?></strong></p>
								<ul>
									<?php foreach ( $notify->get_subscribe_errors() as $error ) : ?>
										<li><?php echo esc_html( $error ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<form
							method="post"
							action="<?php echo esc_url( home_url( '/meetings/' . rawurlencode( $notify->get_state_id() ) . '/notify/' ) ); ?>"
							class="notify-page__form subscribe-form"
							novalidate
						>
							<?php wp_nonce_field( 'civime_subscribe_inline', '_civime_subscribe_nonce' ); ?>
							<input type="hidden" name="form_action" value="subscribe">

							<!-- Honeypot -->
							<div class="notify-page__hp" aria-hidden="true" inert>
								<label for="subscribe-hp-website"><?php esc_html_e( 'Website', 'civime-notifications' ); ?></label>
								<input type="text" name="subscribe_website" id="subscribe-hp-website" tabindex="-1" autocomplete="off">
							</div>

							<fieldset class="subscribe-form__group">
								<legend class="subscribe-form__legend"><?php esc_html_e( 'Contact Information', 'civime-notifications' ); ?></legend>

								<div class="subscribe-form__field">
									<label for="subscribe-email" class="subscribe-form__label">
										<?php esc_html_e( 'Email Address', 'civime-notifications' ); ?>
									</label>
									<input
										type="email"
										id="subscribe-email"
										name="subscribe_email"
										value="<?php echo esc_attr( $sf['email'] ); ?>"
										class="subscribe-form__input"
										autocomplete="email"
										placeholder="you@example.com"
										required
									>
								</div>

								<div class="subscribe-form__field">
									<label for="subscribe-phone" class="subscribe-form__label">
										<?php esc_html_e( 'Phone Number', 'civime-notifications' ); ?>
									</label>
									<input
										type="tel"
										id="subscribe-phone"
										name="subscribe_phone"
										value="<?php echo esc_attr( $sf['phone'] ); ?>"
										class="subscribe-form__input"
										autocomplete="tel"
										placeholder="(808) 555-1234"
									>
									<p class="subscribe-form__hint">
										<?php esc_html_e( 'Optional. US numbers only.', 'civime-notifications' ); ?>
									</p>
								</div>
							</fieldset>

							<fieldset class="subscribe-form__group">
								<legend class="subscribe-form__legend"><?php esc_html_e( 'Notification Frequency', 'civime-notifications' ); ?></legend>

								<div class="subscribe-form__radios">
									<label class="subscribe-form__radio">
										<input type="radio" name="frequency" value="immediate" <?php checked( $sf['frequency'], 'immediate' ); ?>>
										<?php esc_html_e( 'When new meetings are posted', 'civime-notifications' ); ?>
									</label>
									<label class="subscribe-form__radio">
										<input type="radio" name="frequency" value="daily" <?php checked( $sf['frequency'], 'daily' ); ?>>
										<?php esc_html_e( 'Daily digest', 'civime-notifications' ); ?>
									</label>
									<label class="subscribe-form__radio">
										<input type="radio" name="frequency" value="weekly" <?php checked( $sf['frequency'], 'weekly' ); ?>>
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
									esc_html__( 'We never share your info. See our %s.', 'civime-notifications' ),
									'<a href="' . esc_url( home_url( '/privacy/' ) ) . '">' . esc_html__( 'privacy policy', 'civime-notifications' ) . '</a>'
								);
								?>
							</p>
						</form>
					</div>

				</div><!-- .notify-page__grid -->

				<?php endif; ?>

			</div>
		</div>
	</div>

<?php endif; ?>

</main>

<?php
get_footer();
