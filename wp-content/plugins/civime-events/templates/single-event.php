<?php
/**
 * Template: Single Event
 *
 * @package CiviMe_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$single       = new CiviMe_Events_Single();
$meta         = $single->get_meta();
$types        = $single->get_types();
$related      = $single->get_related();
$gcal_url     = $single->get_google_calendar_url();
$primary_type = ! empty( $types ) ? $types[0] : null;

$date_fmt = CiviMe_Events_Archive::format_event_date( $meta['date'] );
$time_fmt = CiviMe_Events_Archive::format_event_time( $meta['time'], $meta['end_time'] );

get_header();
?>

<main id="main" class="site-main" role="main">

	<nav class="events-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'civime-events' ); ?>">
		<div class="container container--narrow">
			<ol class="events-breadcrumb__list">
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'civime_event' ) ); ?>"><?php esc_html_e( 'Events', 'civime-events' ); ?></a></li>
				<?php if ( $primary_type ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $primary_type ) ); ?>"><?php echo esc_html( $primary_type->name ); ?></a></li>
				<?php endif; ?>
				<li aria-current="page"><?php the_title(); ?></li>
			</ol>
		</div>
	</nav>

	<header class="page-header">
		<div class="container container--narrow">
			<?php if ( $primary_type ) : ?>
				<p class="events-eyebrow"><?php echo esc_html( $primary_type->name ); ?></p>
			<?php endif; ?>
			<h1 class="page-header__title"><?php the_title(); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container container--narrow">
			<div class="event-detail">

				<div class="event-detail__info-card">
					<dl class="event-detail__meta">

						<?php if ( '' !== $date_fmt ) : ?>
						<div class="event-detail__meta-item">
							<dt><?php esc_html_e( 'Date', 'civime-events' ); ?></dt>
							<dd><?php echo esc_html( $date_fmt ); ?></dd>
						</div>
						<?php endif; ?>

						<?php if ( '' !== $time_fmt ) : ?>
						<div class="event-detail__meta-item">
							<dt><?php esc_html_e( 'Time', 'civime-events' ); ?></dt>
							<dd><?php echo esc_html( $time_fmt ); ?></dd>
						</div>
						<?php endif; ?>

						<?php if ( '' !== $meta['location'] ) : ?>
						<div class="event-detail__meta-item">
							<dt><?php esc_html_e( 'Location', 'civime-events' ); ?></dt>
							<dd><?php echo esc_html( $meta['location'] ); ?></dd>
						</div>
						<?php endif; ?>

						<?php if ( '' !== $meta['island'] ) : ?>
						<div class="event-detail__meta-item">
							<dt><?php esc_html_e( 'Island', 'civime-events' ); ?></dt>
							<dd><?php echo CiviMe_Events_Archive::get_island_label( $meta['island'] ); ?></dd>
						</div>
						<?php endif; ?>

						<?php if ( '1' === $meta['registration_required'] ) : ?>
						<div class="event-detail__meta-item">
							<dt><?php esc_html_e( 'Registration', 'civime-events' ); ?></dt>
							<dd><?php esc_html_e( 'Required', 'civime-events' ); ?></dd>
						</div>
						<?php endif; ?>

					</dl>

					<div class="event-detail__actions">

						<?php if ( '' !== $meta['url'] ) : ?>
						<a href="<?php echo esc_url( $meta['url'] ); ?>" class="btn btn--primary" target="_blank" rel="noopener noreferrer">
							<?php echo '1' === $meta['registration_required'] ? esc_html__( 'Register', 'civime-events' ) : esc_html__( 'Event Page', 'civime-events' ); ?>
						</a>
						<?php endif; ?>

						<?php if ( '' !== $gcal_url ) : ?>
						<a href="<?php echo esc_url( $gcal_url ); ?>" class="btn btn--small btn--ghost" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Add to Google Calendar', 'civime-events' ); ?>
						</a>
						<?php endif; ?>

					</div>
				</div>

				<article <?php post_class( 'event-detail__content' ); ?>>
					<div class="prose">
						<?php the_content(); ?>
					</div>
				</article>

				<?php if ( ! empty( $related ) ) : ?>

				<section class="event-detail__related" aria-labelledby="related-events-heading">
					<h2 id="related-events-heading"><?php esc_html_e( 'More Events', 'civime-events' ); ?></h2>

					<div class="card-grid card-grid--3">
						<?php foreach ( $related as $rel_post ) : ?>
							<?php
							$rel_date   = (string) get_post_meta( $rel_post->ID, '_civime_event_date', true );
							$rel_ts     = '' !== $rel_date ? strtotime( $rel_date ) : false;
							$rel_month  = $rel_ts ? wp_date( 'M', $rel_ts ) : '';
							$rel_day    = $rel_ts ? wp_date( 'j', $rel_ts ) : '';
							$rel_types  = wp_get_post_terms( $rel_post->ID, 'event_type' );
							$rel_type   = ! empty( $rel_types ) ? $rel_types[0] : null;
							?>
							<article class="card event-card">
								<div class="event-card__inner">
									<?php if ( $rel_ts ) : ?>
										<div class="event-card__date-badge" aria-hidden="true">
											<span class="event-card__date-month"><?php echo esc_html( $rel_month ); ?></span>
											<span class="event-card__date-day"><?php echo esc_html( $rel_day ); ?></span>
										</div>
									<?php endif; ?>
									<div class="event-card__content">
										<?php if ( $rel_type ) : ?>
											<span class="card__tag"><?php echo esc_html( $rel_type->name ); ?></span>
										<?php endif; ?>
										<h3 class="card__title">
											<a href="<?php echo esc_url( get_permalink( $rel_post ) ); ?>"><?php echo esc_html( $rel_post->post_title ); ?></a>
										</h3>
										<?php if ( $rel_ts ) : ?>
											<p class="event-card__meta-item">
												<?php echo esc_html( CiviMe_Events_Archive::format_event_date( $rel_date ) ); ?>
											</p>
										<?php endif; ?>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</section>

				<?php endif; ?>

				<aside class="event-detail__cta">
					<h2><?php esc_html_e( 'Host an Event', 'civime-events' ); ?></h2>
					<p><?php esc_html_e( 'Want to organize a civic engagement event in your community? We can help you get started.', 'civime-events' ); ?></p>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn--primary">
						<?php esc_html_e( 'Get in Touch', 'civime-events' ); ?>
					</a>
				</aside>

			</div>
		</div>
	</div>

</main>

<?php
get_footer();
