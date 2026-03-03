<?php
/**
 * Template: Notify Me Modal
 *
 * Accessible dialog with two options:
 * 1. Remind me about this meeting (AJAX form, one-time morning-of reminder)
 * 2. Subscribe to [Council Name] (link to existing subscribe page)
 *
 * Meeting context (council name, date, etc.) is populated by JavaScript
 * from data attributes on the trigger element.
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="notify-modal" id="notify-modal" role="dialog" aria-modal="true" aria-labelledby="notify-modal-title" hidden>
	<div class="notify-modal__backdrop"></div>

	<div class="notify-modal__dialog">
		<div class="notify-modal__header">
			<h2 class="notify-modal__title" id="notify-modal-title"><?php esc_html_e( 'Get Notified', 'civime-notifications' ); ?></h2>
			<button type="button" class="notify-modal__close" aria-label="<?php esc_attr_e( 'Close', 'civime-notifications' ); ?>">
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M15 5L5 15M5 5l10 10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</button>
		</div>

		<div class="notify-modal__body">

			<div class="notify-modal__meeting-info" aria-live="polite">
				<span class="notify-modal__council-name"></span>
				<span class="notify-modal__meeting-date"></span>
			</div>

			<!-- Reminder form -->
			<form class="notify-modal__form" id="notify-modal-form" novalidate>

				<div class="notify-modal__hp" aria-hidden="true">
					<label for="notify-modal-website"><?php esc_html_e( 'Website', 'civime-notifications' ); ?></label>
					<input type="text" name="website" id="notify-modal-website" tabindex="-1" autocomplete="off">
				</div>

				<input type="hidden" name="meeting_state_id" id="notify-modal-meeting-id" value="">

				<div class="notify-modal__field">
					<label for="notify-modal-email" class="notify-modal__label">
						<?php esc_html_e( 'Email address', 'civime-notifications' ); ?>
					</label>
					<input type="email" name="email" id="notify-modal-email" class="notify-modal__input"
						placeholder="you@example.com" required autocomplete="email">
					<p class="notify-modal__field-error" id="notify-modal-email-error" role="alert" hidden></p>
				</div>

				<button type="submit" class="btn btn--primary notify-modal__submit">
					<?php esc_html_e( 'Remind Me', 'civime-notifications' ); ?>
				</button>

				<p class="notify-modal__hint">
					<?php esc_html_e( "We'll send a confirmation email, then a reminder the morning of the meeting.", 'civime-notifications' ); ?>
				</p>
			</form>

			<!-- Success state (hidden by default, shown after AJAX success) -->
			<div class="notify-modal__success" id="notify-modal-success" hidden>
				<div class="notif-notice notif-notice--success">
					<p><strong><?php esc_html_e( 'Check your email!', 'civime-notifications' ); ?></strong></p>
					<p><?php esc_html_e( 'Click the confirmation link to activate your reminder.', 'civime-notifications' ); ?></p>
				</div>
			</div>

			<!-- Error state (hidden by default, shown after AJAX error) -->
			<div class="notify-modal__error" id="notify-modal-error" hidden>
				<div class="notif-notice notif-notice--error">
					<p id="notify-modal-error-message"></p>
				</div>
			</div>

			<!-- Divider -->
			<div class="notify-modal__divider">
				<span><?php esc_html_e( 'or', 'civime-notifications' ); ?></span>
			</div>

			<!-- Subscribe link -->
			<a href="#" class="btn btn--ghost notify-modal__subscribe" id="notify-modal-subscribe">
				<?php
				printf(
					/* translators: %s: council name placeholder, populated by JS */
					esc_html__( 'Subscribe to %s', 'civime-notifications' ),
					'<span class="notify-modal__subscribe-council"></span>'
				);
				?>
			</a>
			<p class="notify-modal__subscribe-hint">
				<?php esc_html_e( 'Get ongoing alerts for all future meetings from this council.', 'civime-notifications' ); ?>
			</p>

		</div>
	</div>
</div>
