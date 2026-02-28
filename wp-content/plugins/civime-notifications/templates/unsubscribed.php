<?php
/**
 * Template: Unsubscribed
 *
 * Landing page shown after a user unsubscribes from all notifications,
 * either via the manage page or a one-click unsubscribe link.
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
			<h1 class="page-header__title"><?php esc_html_e( 'Unsubscribed', 'civime-notifications' ); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container container--narrow">

			<div class="notif-status notif-status--neutral" role="status">
				<h2><?php esc_html_e( "You've been unsubscribed", 'civime-notifications' ); ?></h2>
				<p><?php esc_html_e( "You won't receive any more meeting notifications from us. If you change your mind, you can subscribe again at any time.", 'civime-notifications' ); ?></p>
				<div class="notif-status__actions">
					<a href="<?php echo esc_url( home_url( '/meetings/subscribe/' ) ); ?>" class="btn btn--primary">
						<?php esc_html_e( 'Subscribe Again', 'civime-notifications' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--ghost">
						<?php esc_html_e( 'Browse Meetings', 'civime-notifications' ); ?>
					</a>
				</div>
			</div>

		</div>
	</div>

</main>

<?php
get_footer();
