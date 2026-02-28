<?php
/**
 * Template: Subscription Confirmed
 *
 * Landing page shown after a user clicks the confirmation link in their
 * email/SMS. The Access100 API redirects here after confirming.
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="main" class="site-main" role="main">

	<header class="page-header">
		<div class="container container--narrow">
			<h1 class="page-header__title"><?php esc_html_e( 'Subscription Confirmed', 'civime-notifications' ); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container container--narrow">

			<div class="notif-status notif-status--success" role="status">
				<h2><?php esc_html_e( "You're all set!", 'civime-notifications' ); ?></h2>
				<p><?php esc_html_e( "Your subscription is confirmed. You'll receive notifications when new meetings are posted for the councils you follow.", 'civime-notifications' ); ?></p>
				<p><?php esc_html_e( 'You can manage your preferences or unsubscribe at any time using the link in any notification email.', 'civime-notifications' ); ?></p>
				<div class="notif-status__actions">
					<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--primary">
						<?php esc_html_e( 'Browse Meetings', 'civime-notifications' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/councils/' ) ); ?>" class="btn btn--ghost">
						<?php esc_html_e( 'Explore Councils', 'civime-notifications' ); ?>
					</a>
				</div>
			</div>

		</div>
	</div>

</main>

<?php
get_footer();
