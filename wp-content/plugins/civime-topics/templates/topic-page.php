<?php
/**
 * Template: Individual Topic Page
 *
 * Shows a topic's description, mapped councils, and their upcoming meetings.
 *
 * @package CiviMe_Topics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = get_query_var( 'civime_topic_slug' );
$api  = civime_api();

// Local emoji map — avoids mojibake from API response encoding issues.
$topic_icons = [
	'environment'     => "\xF0\x9F\x8C\xBF",
	'housing'         => "\xF0\x9F\x8F\xA0",
	'education'       => "\xF0\x9F\x93\x9A",
	'health'          => "\xF0\x9F\x8F\xA5",
	'transportation'  => "\xF0\x9F\x9A\x8C",
	'public-safety'   => "\xF0\x9F\x9B\xA1\xEF\xB8\x8F",
	'economy'         => "\xF0\x9F\x92\xBC",
	'culture'         => "\xF0\x9F\x8E\xAD",
	'agriculture'     => "\xF0\x9F\x8C\xBE",
	'energy'          => "\xE2\x9A\xA1",
	'water'           => "\xF0\x9F\x8C\x8A",
	'disability'      => "\xE2\x99\xBF",
	'veterans'        => "\xF0\x9F\x8E\x96\xEF\xB8\x8F",
	'technology'      => "\xF0\x9F\x92\xBB",
	'budget'          => "\xF0\x9F\x93\x8A",
	'governance'      => "\xE2\x9A\x96\xEF\xB8\x8F",
];

// Fetch topic detail (includes mapped councils)
$topic_result = $api->get_topic( $slug );
$topic        = null;
$councils     = [];
$error        = false;

if ( is_wp_error( $topic_result ) ) {
	$error = true;
} else {
	$topic    = $topic_result['data'] ?? null;
	$councils = $topic['councils'] ?? [];
}

// Fetch upcoming meetings for this topic
$meetings       = [];
$meetings_total = 0;
if ( $topic ) {
	$meetings_result = $api->get_topic_meetings( $slug, [ 'limit' => 20 ] );
	if ( ! is_wp_error( $meetings_result ) ) {
		$meetings       = $meetings_result['data'] ?? [];
		$meetings_total = $meetings_result['meta']['total'] ?? count( $meetings );
	}
}

get_header();
?>

<main id="main" class="site-main" role="main">

	<?php if ( $error || ! $topic ) : ?>

		<header class="page-header">
			<div class="container">
				<h1 class="page-header__title"><?php esc_html_e( 'Topic Not Found', 'civime-topics' ); ?></h1>
			</div>
		</header>

		<div class="section">
			<div class="container">
				<div class="meetings-notice meetings-notice--warning" role="alert">
					<p><?php esc_html_e( 'This topic could not be found or is temporarily unavailable.', 'civime-topics' ); ?></p>
					<p>
						<a href="<?php echo esc_url( home_url( '/topics/' ) ); ?>">
							<?php esc_html_e( 'Browse all topics', 'civime-topics' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>

	<?php else : ?>

		<header class="page-header">
			<div class="container">
				<nav class="meeting-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'civime-topics' ); ?>">
					<ol class="meeting-breadcrumb__list">
						<li><a href="<?php echo esc_url( home_url( '/topics/' ) ); ?>"><?php esc_html_e( 'Topics', 'civime-topics' ); ?></a></li>
						<li aria-current="page"><?php echo esc_html( $topic['name'] ); ?></li>
					</ol>
				</nav>

				<h1 class="page-header__title">
					<span aria-hidden="true"><?php echo esc_html( $topic_icons[ $slug ] ?? '' ); ?></span>
					<?php echo esc_html( $topic['name'] ); ?>
				</h1>
				<p class="page-header__description"><?php echo esc_html( $topic['description'] ?? '' ); ?></p>
			</div>
		</header>

		<div class="section">
			<div class="container">

				<?php if ( ! empty( $meetings ) ) : ?>

					<section aria-labelledby="topic-meetings-heading" class="topic-meetings-section">
						<h2 id="topic-meetings-heading" class="section__heading">
							<?php
							printf(
								/* translators: %d: number of meetings */
								esc_html__( 'Upcoming Meetings (%d)', 'civime-topics' ),
								$meetings_total
							);
							?>
						</h2>

						<?php foreach ( $meetings as $meeting ) : ?>
							<?php
							$state_id    = $meeting['state_id'] ?? '';
							$meeting_url = home_url( '/meetings/' . $state_id );
							$time_raw    = $meeting['meeting_time'] ?? '';
							$time_label  = '' !== $time_raw ? wp_date( 'g:i A', strtotime( $time_raw ) ) : '';
							$date_label  = ! empty( $meeting['meeting_date'] )
								? wp_date( 'D, M j', strtotime( $meeting['meeting_date'] ) )
								: '';
							?>
							<article class="meeting-card" aria-label="<?php echo esc_attr( $meeting['council']['name'] ?? '' ); ?>">
								<div class="meeting-card__body">
									<h3 class="meeting-card__title">
										<a href="<?php echo esc_url( $meeting_url ); ?>">
											<?php echo esc_html( $meeting['council']['name'] ?? '' ); ?>
										</a>
									</h3>
									<p class="meeting-card__subtitle">
										<?php echo esc_html( $meeting['title'] ?? '' ); ?>
									</p>
									<div class="meeting-card__meta">
										<?php if ( '' !== $date_label ) : ?>
											<span class="meeting-card__time">
												<?php echo esc_html( $date_label ); ?>
												<?php if ( '' !== $time_label ) : ?>
													&middot; <?php echo esc_html( $time_label ); ?>
												<?php endif; ?>
											</span>
										<?php endif; ?>
										<?php if ( ! empty( $meeting['location'] ) ) : ?>
											<span class="meeting-card__location"><?php echo esc_html( $meeting['location'] ); ?></span>
										<?php endif; ?>
									</div>
								</div>
								<div class="meeting-card__action">
									<a href="<?php echo esc_url( $meeting_url ); ?>" class="btn btn--small">
										<?php esc_html_e( 'Details', 'civime-topics' ); ?>
									</a>
								</div>
							</article>
						<?php endforeach; ?>

						<?php if ( $meetings_total > count( $meetings ) ) : ?>
							<p class="topic-meetings-section__more">
								<a href="<?php echo esc_url( home_url( '/meetings/?topics=' . rawurlencode( $slug ) ) ); ?>" class="btn btn--ghost">
									<?php esc_html_e( 'View all meetings for this topic', 'civime-topics' ); ?>
								</a>
							</p>
						<?php endif; ?>
					</section>

				<?php else : ?>

					<div class="meetings-notice meetings-notice--info" role="status">
						<p><?php esc_html_e( 'No upcoming meetings for this topic right now.', 'civime-topics' ); ?></p>
					</div>

				<?php endif; ?>

				<?php if ( ! empty( $councils ) ) : ?>

					<section aria-labelledby="topic-councils-heading">
						<h2 id="topic-councils-heading" class="section__heading">
							<?php
							printf(
								/* translators: %d: number of councils */
								esc_html( _n( '%d Related Council', '%d Related Councils', count( $councils ), 'civime-topics' ) ),
								count( $councils )
							);
							?>
						</h2>

						<div class="councils-grid">
							<?php foreach ( $councils as $council ) : ?>
								<article class="council-card" aria-label="<?php echo esc_attr( $council['name'] ); ?>">
									<h3 class="council-card__name">
										<a href="<?php echo esc_url( home_url( '/meetings/?council_id=' . (int) $council['id'] ) ); ?>">
											<?php echo esc_html( $council['name'] ); ?>
										</a>
									</h3>
									<div class="council-card__meta">
										<?php if ( 'primary' === $council['relevance'] ) : ?>
											<span class="meeting-card__badge">
												<?php esc_html_e( 'Primary', 'civime-topics' ); ?>
											</span>
										<?php endif; ?>
										<span class="council-card__meetings">
											<?php
											printf(
												/* translators: %d: number of upcoming meetings */
												esc_html( _n( '%d upcoming meeting', '%d upcoming meetings', (int) $council['upcoming_meeting_count'], 'civime-topics' ) ),
												(int) $council['upcoming_meeting_count']
											);
											?>
										</span>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</section>

				<?php endif; ?>

			</div>
		</div>

	<?php endif; ?>

</main>

<?php
get_footer();
