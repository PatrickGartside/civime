<?php
/**
 * Template: Event Archive
 *
 * @package CiviMe_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$archive       = new CiviMe_Events_Archive();
$types         = $archive->get_types();
$active_type   = $archive->get_active_type();
$active_island = $archive->get_active_island();
$showing_past  = $archive->is_showing_past();
$islands       = CiviMe_Events_Archive::get_valid_islands();
$archive_url   = get_post_type_archive_link( 'civime_event' );

get_header();
?>

<main id="main" class="site-main" role="main">

	<header class="page-header">
		<div class="container">
			<?php if ( $active_type ) : ?>
				<p class="events-eyebrow"><?php esc_html_e( 'Events', 'civime-events' ); ?></p>
				<h1 class="page-header__title"><?php echo esc_html( $active_type->name ); ?></h1>
			<?php else : ?>
				<h1 class="page-header__title">
					<?php echo $showing_past ? esc_html__( 'Past Events', 'civime-events' ) : esc_html__( 'Upcoming Events', 'civime-events' ); ?>
				</h1>
				<p class="page-header__description"><?php esc_html_e( 'Community events for civic engagement across Hawaii — letter writing parties, info sessions, town halls, and more.', 'civime-events' ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<div class="section">
		<div class="container">

			<div class="events-filters">

				<div class="events-filters__row">

					<?php // Upcoming / Past toggle. ?>
					<div class="events-filters__toggle">
						<a
							href="<?php echo esc_url( $archive_url ); ?>"
							class="events-filters__toggle-btn <?php echo ! $showing_past ? 'events-filters__toggle-btn--active' : ''; ?>"
							<?php echo ! $showing_past ? 'aria-current="page"' : ''; ?>
						>
							<?php esc_html_e( 'Upcoming', 'civime-events' ); ?>
						</a>
						<a
							href="<?php echo esc_url( add_query_arg( 'show', 'past', $archive_url ) ); ?>"
							class="events-filters__toggle-btn <?php echo $showing_past ? 'events-filters__toggle-btn--active' : ''; ?>"
							<?php echo $showing_past ? 'aria-current="page"' : ''; ?>
						>
							<?php esc_html_e( 'Past', 'civime-events' ); ?>
						</a>
					</div>

					<?php // Island filter. ?>
					<?php if ( ! empty( $islands ) ) : ?>
						<div class="events-filters__field">
							<label for="events-island-filter" class="events-filters__label"><?php esc_html_e( 'Island', 'civime-events' ); ?></label>
							<select
								id="events-island-filter"
								class="events-filters__select"
								onchange="if(this.value){location.href=this.value}"
							>
								<option value="<?php echo esc_url( remove_query_arg( 'island' ) ); ?>"><?php esc_html_e( 'All Islands', 'civime-events' ); ?></option>
								<?php foreach ( $islands as $slug => $label ) : ?>
									<option
										value="<?php echo esc_url( add_query_arg( 'island', $slug ) ); ?>"
										<?php selected( $active_island, $slug ); ?>
									>
										<?php echo $label; ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>

				</div>

				<?php // Type filter pills. ?>
				<?php if ( ! empty( $types ) ) : ?>
					<nav class="events-type-filter" aria-label="<?php esc_attr_e( 'Filter events by type', 'civime-events' ); ?>">
						<ul class="events-type-filter__list" role="list">
							<li>
								<a
									href="<?php echo esc_url( $showing_past ? add_query_arg( 'show', 'past', $archive_url ) : $archive_url ); ?>"
									class="guides-filter__pill <?php echo ! $active_type ? 'guides-filter__pill--active' : ''; ?>"
									<?php echo ! $active_type ? 'aria-current="page"' : ''; ?>
								>
									<?php esc_html_e( 'All', 'civime-events' ); ?>
								</a>
							</li>
							<?php foreach ( $types as $type ) : ?>
								<li>
									<a
										href="<?php echo esc_url( get_term_link( $type ) ); ?>"
										class="guides-filter__pill <?php echo $active_type && $active_type->term_id === $type->term_id ? 'guides-filter__pill--active' : ''; ?>"
										<?php echo $active_type && $active_type->term_id === $type->term_id ? 'aria-current="page"' : ''; ?>
									>
										<?php echo esc_html( $type->name ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>

			</div>

			<?php if ( have_posts() ) : ?>

				<div class="card-grid card-grid--2">

					<?php while ( have_posts() ) : the_post(); ?>

						<?php
						$event_date     = (string) get_post_meta( get_the_ID(), '_civime_event_date', true );
						$event_time     = (string) get_post_meta( get_the_ID(), '_civime_event_time', true );
						$event_end_time = (string) get_post_meta( get_the_ID(), '_civime_event_end_time', true );
						$event_location = (string) get_post_meta( get_the_ID(), '_civime_event_location', true );
						$event_island   = (string) get_post_meta( get_the_ID(), '_civime_event_island', true );
						$event_reg      = (string) get_post_meta( get_the_ID(), '_civime_event_registration_required', true );
						$event_types    = wp_get_post_terms( get_the_ID(), 'event_type' );
						$primary_type   = ! empty( $event_types ) ? $event_types[0] : null;

						$date_ts  = '' !== $event_date ? strtotime( $event_date ) : false;
						$month    = $date_ts ? wp_date( 'M', $date_ts ) : '';
						$day      = $date_ts ? wp_date( 'j', $date_ts ) : '';
						$time_fmt = CiviMe_Events_Archive::format_event_time( $event_time, $event_end_time );
						?>

						<article class="card event-card" aria-label="<?php echo esc_attr( get_the_title() ); ?>">

							<div class="event-card__inner">

								<?php if ( $date_ts ) : ?>
									<div class="event-card__date-badge" aria-hidden="true">
										<span class="event-card__date-month"><?php echo esc_html( $month ); ?></span>
										<span class="event-card__date-day"><?php echo esc_html( $day ); ?></span>
									</div>
								<?php endif; ?>

								<div class="event-card__content">

									<?php if ( $primary_type ) : ?>
										<span class="card__tag"><?php echo esc_html( $primary_type->name ); ?></span>
									<?php endif; ?>

									<h2 class="card__title">
										<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
									</h2>

									<div class="event-card__meta">

										<?php if ( $date_ts ) : ?>
											<span class="event-card__meta-item">
												<span class="screen-reader-text"><?php esc_html_e( 'Date:', 'civime-events' ); ?></span>
												<?php echo esc_html( CiviMe_Events_Archive::format_event_date( $event_date ) ); ?>
											</span>
										<?php endif; ?>

										<?php if ( '' !== $time_fmt ) : ?>
											<span class="event-card__meta-item">
												<span class="screen-reader-text"><?php esc_html_e( 'Time:', 'civime-events' ); ?></span>
												<?php echo esc_html( $time_fmt ); ?>
											</span>
										<?php endif; ?>

										<?php if ( '' !== $event_location ) : ?>
											<span class="event-card__meta-item">
												<span class="screen-reader-text"><?php esc_html_e( 'Location:', 'civime-events' ); ?></span>
												<?php echo esc_html( $event_location ); ?>
											</span>
										<?php endif; ?>

									</div>

									<div class="event-card__badges">
										<?php if ( '' !== $event_island ) : ?>
											<span class="event-card__island-pill"><?php echo CiviMe_Events_Archive::get_island_label( $event_island ); ?></span>
										<?php endif; ?>

										<?php if ( '1' === $event_reg ) : ?>
											<span class="event-card__registration-badge"><?php esc_html_e( 'Registration Required', 'civime-events' ); ?></span>
										<?php endif; ?>
									</div>

								</div>

							</div>

						</article>

					<?php endwhile; ?>

				</div>

				<?php
				the_posts_pagination( [
					'mid_size'  => 2,
					'prev_text' => '&laquo; ' . __( 'Previous', 'civime-events' ),
					'next_text' => __( 'Next', 'civime-events' ) . ' &raquo;',
				] );
				?>

			<?php else : ?>

				<div class="meetings-notice meetings-notice--info" role="status">
					<?php if ( $showing_past ) : ?>
						<p><?php esc_html_e( 'No past events found.', 'civime-events' ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'Community events are coming soon. We\'re building a network of ambassadors to host letter-writing parties and civic engagement events across the islands.', 'civime-events' ); ?></p>
						<p>
							<a href="<?php echo esc_url( home_url( '/meetings/subscribe/' ) ); ?>" class="btn btn--primary btn--sm">
								<?php esc_html_e( 'Get notified when events are posted', 'civime-events' ); ?>
							</a>
							<a href="<?php echo esc_url( home_url( '/get-involved/' ) ); ?>" class="btn btn--secondary btn--sm">
								<?php esc_html_e( 'Host an event', 'civime-events' ); ?>
							</a>
						</p>
					<?php endif; ?>
					<?php if ( $active_type || '' !== $active_island ) : ?>
						<p>
							<a href="<?php echo esc_url( $archive_url ); ?>">
								<?php esc_html_e( 'View all events', 'civime-events' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>

			<?php endif; ?>

		</div>
	</div>

</main>

<?php
get_footer();
