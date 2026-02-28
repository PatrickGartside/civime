<?php
/**
 * Template: Meeting Detail
 *
 * @package CiviMe_Meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$detail = new CiviMe_Meetings_Detail();

// The detail object must exist before the title filter runs because the
// title depends on data that is only available after the API call.
if ( ! $detail->has_error() ) {
	add_filter(
		'document_title_parts',
		function ( array $title ) use ( $detail ): array {
			$title['title'] = $detail->get_page_title();
			return $title;
		},
		20
	);
}

get_header();
?>

<main id="main" class="site-main" role="main">

<?php if ( $detail->is_not_found() ) : ?>

	<header class="page-header">
		<div class="container container--narrow">
			<h1 class="page-header__title"><?php esc_html_e( 'Meeting Not Found', 'civime-meetings' ); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container container--narrow">
			<div class="meetings-notice meetings-notice--warning" role="alert">
				<p><?php esc_html_e( "We couldn't find this meeting. It may have been cancelled or the link may be incorrect.", 'civime-meetings' ); ?></p>
				<p><a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--primary"><?php esc_html_e( 'Browse All Meetings', 'civime-meetings' ); ?></a></p>
			</div>
		</div>
	</div>

<?php elseif ( $detail->has_error() ) : ?>

	<header class="page-header">
		<div class="container container--narrow">
			<h1 class="page-header__title"><?php esc_html_e( 'Meeting Detail', 'civime-meetings' ); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container container--narrow">
			<div class="meetings-notice meetings-notice--warning" role="alert">
				<p><strong><?php esc_html_e( 'Meeting data is temporarily unavailable.', 'civime-meetings' ); ?></strong></p>
				<p><?php esc_html_e( "We're working on connecting to the meeting database. Check back soon.", 'civime-meetings' ); ?></p>
				<p><a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>"><?php esc_html_e( 'Back to meetings', 'civime-meetings' ); ?></a></p>
			</div>
		</div>
	</div>

<?php else : ?>

	<?php
	$m = $detail->get_meeting();

	// Format the date parts once so they are not repeated inline.
	$date_formatted = ! empty( $m['date'] ) ? wp_date( 'l, F j, Y', strtotime( $m['date'] ) ) : '';

	$time_formatted = '';
	if ( ! empty( $m['time'] ) ) {
		$time_formatted = wp_date( 'g:i A', strtotime( $m['time'] ) );

		if ( ! empty( $m['end_time'] ) ) {
			$time_formatted .= " \u{2013} " . wp_date( 'g:i A', strtotime( $m['end_time'] ) );
		}
	}
	?>

	<nav class="meeting-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'civime-meetings' ); ?>">
		<div class="container container--narrow">
			<ol class="meeting-breadcrumb__list">
				<li><a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>"><?php esc_html_e( 'Meetings', 'civime-meetings' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/meetings/?council_id=' . absint( $m['council_id'] ) ) ); ?>"><?php echo esc_html( $m['council_name'] ); ?></a></li>
				<li aria-current="page"><?php echo esc_html( $m['title'] ?: __( 'Meeting Detail', 'civime-meetings' ) ); ?></li>
			</ol>
		</div>
	</nav>

	<header class="page-header">
		<div class="container">
			<p class="meeting-detail__council"><?php echo esc_html( $m['council_name'] ); ?></p>
			<h1 class="page-header__title"><?php echo esc_html( $m['title'] ?: $m['council_name'] . ' ' . __( 'Meeting', 'civime-meetings' ) ); ?></h1>
		</div>
	</header>

	<div class="section">
		<div class="container">
			<div class="meeting-detail">

				<div class="meeting-detail__info-card">
					<dl class="meeting-detail__meta">

						<?php if ( $date_formatted ) : ?>
						<div class="meeting-detail__meta-item">
							<dt><?php esc_html_e( 'Date', 'civime-meetings' ); ?></dt>
							<dd><?php echo esc_html( $date_formatted ); ?></dd>
						</div>
						<?php endif; ?>

						<?php if ( $time_formatted ) : ?>
						<div class="meeting-detail__meta-item">
							<dt><?php esc_html_e( 'Time', 'civime-meetings' ); ?></dt>
							<dd><?php echo esc_html( $time_formatted ); ?></dd>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $m['location'] ) ) : ?>
						<div class="meeting-detail__meta-item">
							<dt><?php esc_html_e( 'Location', 'civime-meetings' ); ?></dt>
							<dd>
								<?php echo esc_html( $m['location'] ); ?>
								<?php if ( ! empty( $m['address'] ) ) : ?>
									<br><small><?php echo esc_html( $m['address'] ); ?></small>
								<?php endif; ?>
							</dd>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $m['zoom_url'] ) ) : ?>
						<div class="meeting-detail__meta-item">
							<dt><?php esc_html_e( 'Virtual', 'civime-meetings' ); ?></dt>
							<dd>
								<a href="<?php echo esc_url( $m['zoom_url'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Join via Zoom', 'civime-meetings' ); ?>
								</a>
							</dd>
						</div>
						<?php endif; ?>

					</dl>

					<div class="meeting-detail__actions">

						<?php if ( $detail->get_ics_url() ) : ?>
						<a href="<?php echo esc_url( $detail->get_ics_url() ); ?>" class="btn btn--small">
							<?php esc_html_e( 'Add to Calendar', 'civime-meetings' ); ?>
						</a>
						<?php endif; ?>

						<?php if ( ! empty( $m['notice_url'] ) ) : ?>
						<a href="<?php echo esc_url( $m['notice_url'] ); ?>" class="btn btn--small btn--ghost" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Official Notice', 'civime-meetings' ); ?>
						</a>
						<?php endif; ?>

						<a href="<?php echo esc_url( add_query_arg( 'council_id', absint( $m['council_id'] ), home_url( '/meetings/subscribe/' ) ) ); ?>" class="btn btn--small btn--primary">
							<?php esc_html_e( 'Get Notified', 'civime-meetings' ); ?>
						</a>

						<button type="button" class="btn btn--small btn--ghost js-share-meeting"
							data-title="<?php echo esc_attr( $m['title'] ?: $m['council_name'] . ' Meeting' ); ?>"
							data-text="<?php echo esc_attr( $m['council_name'] . ' — ' . $date_formatted ); ?>"
							data-url="<?php echo esc_url( home_url( '/meetings/' . rawurlencode( $detail->get_state_id() ) . '/' ) ); ?>">
							<?php esc_html_e( 'Share with a Friend', 'civime-meetings' ); ?>
						</button>

					</div>
				</div>

				<?php
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

				// Merge and deduplicate topic tags from direct and inherited sources.
				$all_topics = [];
				$seen_slugs = [];
				foreach ( ( $m['topics']['direct'] ?? [] ) as $t ) {
					if ( ! in_array( $t['slug'], $seen_slugs, true ) ) {
						$seen_slugs[]  = $t['slug'];
						$all_topics[] = $t;
					}
				}
				foreach ( ( $m['topics']['inherited'] ?? [] ) as $t ) {
					if ( ! in_array( $t['slug'], $seen_slugs, true ) ) {
						$seen_slugs[]  = $t['slug'];
						$all_topics[] = $t;
					}
				}
				?>
				<?php if ( ! empty( $all_topics ) ) : ?>
				<div class="meeting-detail__topics">
					<?php foreach ( $all_topics as $topic ) : ?>
						<a href="<?php echo esc_url( home_url( '/meetings/?topics=' . rawurlencode( $topic['slug'] ) ) ); ?>"
							class="meeting-detail__topic-tag">
							<?php
							$icon = $topic_icons[ $topic['slug'] ?? '' ] ?? '';
							if ( '' !== $icon ) : ?>
								<span class="meeting-detail__topic-icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
							<?php endif; ?>
							<?php echo esc_html( $topic['name'] ); ?>
						</a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $m['summary_text'] ) ) : ?>
				<section class="meeting-detail__summary" aria-labelledby="summary-heading">
					<h2 id="summary-heading"><?php esc_html_e( 'What This Meeting Is About', 'civime-meetings' ); ?></h2>
					<div class="meeting-detail__summary-content prose">
						<?php echo wp_kses_post( wpautop( $m['summary_text'] ) ); ?>
					</div>
					<p class="meeting-detail__summary-note">
						<small><?php esc_html_e( 'This summary was generated by AI to help you understand the agenda. Always refer to the official documents for complete information.', 'civime-meetings' ); ?></small>
					</p>
				</section>
				<?php endif; ?>

				<?php if ( ! empty( $m['agenda_text'] ) ) : ?>
				<section class="meeting-detail__agenda" aria-labelledby="agenda-heading">
					<h2 id="agenda-heading"><?php esc_html_e( 'Agenda', 'civime-meetings' ); ?></h2>
					<div class="meeting-detail__agenda-content prose">
						<?php
						// Escape the plain-text agenda before wpautop so any HTML characters
						// in scraped government text are rendered as visible characters, not tags.
						echo wp_kses_post( wpautop( esc_html( $m['agenda_text'] ) ) );
						?>
					</div>
					<?php if ( ! empty( $m['agenda_url'] ) ) : ?>
					<p>
						<a href="<?php echo esc_url( $m['agenda_url'] ); ?>" class="btn btn--small btn--ghost" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Download Full Agenda (PDF)', 'civime-meetings' ); ?>
						</a>
					</p>
					<?php endif; ?>
				</section>

				<?php elseif ( ! empty( $m['agenda_url'] ) ) : ?>
				<section class="meeting-detail__agenda" aria-labelledby="agenda-heading">
					<h2 id="agenda-heading"><?php esc_html_e( 'Agenda', 'civime-meetings' ); ?></h2>
					<p><?php esc_html_e( 'The agenda is available as a PDF document.', 'civime-meetings' ); ?></p>
					<p>
						<a href="<?php echo esc_url( $m['agenda_url'] ); ?>" class="btn btn--small btn--ghost" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Download Agenda (PDF)', 'civime-meetings' ); ?>
						</a>
					</p>
				</section>
				<?php endif; ?>

				<?php if ( ! empty( $m['attachments'] ) && is_array( $m['attachments'] ) ) : ?>
				<section class="meeting-detail__attachments" aria-labelledby="attachments-heading">
					<h2 id="attachments-heading"><?php esc_html_e( 'Documents', 'civime-meetings' ); ?></h2>
					<ul class="meeting-detail__attachment-list">
						<?php foreach ( $m['attachments'] as $attachment ) : ?>
						<li>
							<a href="<?php echo esc_url( $attachment['url'] ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $attachment['name'] ); ?>
							</a>
						</li>
						<?php endforeach; ?>
					</ul>
				</section>
				<?php endif; ?>

				<aside class="meeting-detail__cta">
					<h2><?php esc_html_e( 'Stay Informed', 'civime-meetings' ); ?></h2>
					<p>
						<?php
						printf(
							/* translators: %s: council name */
							esc_html__( 'Get notified when %s posts new meetings or agendas.', 'civime-meetings' ),
							esc_html( $m['council_name'] )
						);
						?>
					</p>
					<a href="<?php echo esc_url( add_query_arg( 'council_id', absint( $m['council_id'] ), home_url( '/meetings/subscribe/' ) ) ); ?>" class="btn btn--primary">
						<?php esc_html_e( 'Get Notified', 'civime-meetings' ); ?>
					</a>
				</aside>

			</div>
		</div>
	</div>

<?php endif; ?>

</main>

<?php
get_footer();
