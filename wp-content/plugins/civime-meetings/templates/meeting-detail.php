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

	$source_labels = [
		'ehawaii'          => __( 'State of Hawaii', 'civime-meetings' ),
		'nco'              => __( 'Honolulu Neighborhood Board', 'civime-meetings' ),
		'honolulu_boards'  => __( 'Honolulu County Committee', 'civime-meetings' ),
		'maui_legistar'    => __( 'Maui County Committee', 'civime-meetings' ),
	];
	$source_key   = $m['source'] ?? '';
	$source_label = $source_labels[ $source_key ] ?? '';

	$time_formatted = '';
	if ( ! empty( $m['time'] ) ) {
		// Times from the API are already in local Hawaii time — use UTC timezone
		// with wp_date() to prevent double-converting from the site's gmt_offset.
		$utc = new DateTimeZone( 'UTC' );
		$time_formatted = wp_date( 'g:i A', strtotime( $m['time'] ), $utc );

		if ( ! empty( $m['end_time'] ) ) {
			$time_formatted .= " \u{2013} " . wp_date( 'g:i A', strtotime( $m['end_time'] ), $utc );
		}
	}
	?>

	<nav class="meeting-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'civime-meetings' ); ?>">
		<div class="container">
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
			<div class="meeting-detail" translate="yes">

				<?php if ( class_exists( 'CiviMe_I18n_Locale' ) && 'en' !== apply_filters( 'civime_i18n_active_slug', 'en' ) ) : ?>
					<p class="i18n-content-notice" translate="no">
						<?php esc_html_e( 'Meeting information is displayed in English.', 'civime-meetings' ); ?>
					</p>
				<?php endif; ?>

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
									<?php esc_html_e( 'Join Online', 'civime-meetings' ); ?>
								</a>
							</dd>
						</div>
						<?php endif; ?>

					</dl>

					<?php if ( '' !== $source_label ) : ?>
					<div class="meeting-detail__source" style="margin-top: var(--space-4);">
						<span class="meeting-card__badge meeting-card__badge--source">
							<?php echo esc_html( $source_label ); ?>
						</span>
					</div>
					<?php endif; ?>

					<div class="meeting-detail__actions">

						<?php if ( ! empty( $m['notice_url'] ) ) : ?>
						<a href="<?php echo esc_url( $m['notice_url'] ); ?>" class="btn btn--small btn--ghost" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'View Official Notice', 'civime-meetings' ); ?>
						</a>
						<?php endif; ?>

						<?php if ( $detail->get_ics_url() ) : ?>
						<a href="<?php echo esc_url( $detail->get_ics_url() ); ?>" class="btn btn--small">
							<?php esc_html_e( 'Add to My Calendar', 'civime-meetings' ); ?>
						</a>
						<?php endif; ?>

						<a href="<?php echo esc_url( home_url( '/meetings/' . rawurlencode( $detail->get_state_id() ) . '/notify/' ) ); ?>"
							class="btn btn--small btn--primary">
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
							<?php echo esc_html( class_exists( 'CiviMe_I18n_Topic_Names' ) ? CiviMe_I18n_Topic_Names::get( $topic['slug'] ?? '' ) : $topic['name'] ); ?>
						</a>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $m['summary_text'] ) ) :
				$active_lang          = apply_filters( 'civime_i18n_active_slug', 'en' );
				$summary_display      = $m['summary_text'];
				$summary_is_translated = false;

				if ( 'en' !== $active_lang
					&& ! empty( $m['summary_translations'] )
					&& is_array( $m['summary_translations'] )
					&& ! empty( $m['summary_translations'][ $active_lang ] )
				) {
					$summary_display      = $m['summary_translations'][ $active_lang ];
					$summary_is_translated = true;
				}
			?>
				<section class="meeting-detail__summary" aria-labelledby="summary-heading"<?php echo $summary_is_translated ? '' : ' translate="yes"'; ?>>
					<h2 id="summary-heading"><?php esc_html_e( 'What This Meeting Is About', 'civime-meetings' ); ?></h2>
					<div class="meeting-detail__summary-content prose">
						<?php echo wp_kses_post( wpautop( $summary_display ) ); ?>
					</div>
					<p class="meeting-detail__summary-note">
						<small><?php esc_html_e( 'This summary was generated by AI to help you understand the agenda. Always refer to the official documents for complete information.', 'civime-meetings' ); ?></small>
					</p>
				</section>
				<?php endif; ?>


			</div>
		</div>
	</div>

	<div class="section">
		<div class="container">
			<div class="notify-page__grid">

				<!-- Remind me about this meeting -->
				<div class="notify-section">
					<h2 class="notify-section__heading"><span aria-hidden="true">&#x1F514;</span> <?php esc_html_e( 'Remind Me About This Meeting', 'civime-meetings' ); ?></h2>

					<form
						method="post"
						action="<?php echo esc_url( home_url( '/meetings/' . rawurlencode( $detail->get_state_id() ) . '/notify/' ) ); ?>"
						class="notify-page__form subscribe-form"
						novalidate
					>
						<?php wp_nonce_field( 'civime_reminder', '_civime_nonce' ); ?>
						<input type="hidden" name="form_action" value="remind">

						<!-- Honeypot -->
						<div class="notify-page__hp" aria-hidden="true" inert>
							<label for="detail-notify-website"><?php esc_html_e( 'Website', 'civime-meetings' ); ?></label>
							<input type="text" name="website" id="detail-notify-website" tabindex="-1" autocomplete="off">
						</div>

						<fieldset class="subscribe-form__group">
							<legend class="subscribe-form__legend"><?php esc_html_e( 'Email Address', 'civime-meetings' ); ?></legend>

							<div class="subscribe-form__field">
								<label for="detail-notify-email" class="subscribe-form__label">
									<?php esc_html_e( 'Email Address', 'civime-meetings' ); ?>
								</label>
								<input
									type="email"
									id="detail-notify-email"
									name="email"
									class="subscribe-form__input"
									autocomplete="email"
									placeholder="you@example.com"
									required
								>
								<p class="subscribe-form__hint">
									<?php esc_html_e( "We'll send a confirmation email, then a reminder the morning of the meeting.", 'civime-meetings' ); ?>
								</p>
							</div>
						</fieldset>

						<div class="subscribe-form__submit">
							<button type="submit" class="btn btn--primary btn--lg">
								<?php esc_html_e( 'Remind Me', 'civime-meetings' ); ?>
							</button>
						</div>

						<p class="subscribe-form__privacy">
							<?php
							printf(
								/* translators: %s: link to privacy policy */
								esc_html__( 'We never share your info. See our %s.', 'civime-meetings' ),
								'<a href="' . esc_url( home_url( '/privacy/' ) ) . '">' . esc_html__( 'privacy policy', 'civime-meetings' ) . '</a>'
							);
							?>
						</p>
					</form>
				</div>

				<!-- Subscribe to council -->
				<div class="notify-section">
					<h2 class="notify-section__heading"><span aria-hidden="true">&#x1F4E8;</span> <?php esc_html_e( 'Subscribe to This Council', 'civime-meetings' ); ?></h2>

					<div class="subscribe-form__council-badge">
						<?php if ( ! empty( $m['council_name'] ) ) : ?>
							<span class="subscribe-form__council-badge-name"><?php echo esc_html( $m['council_name'] ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $m['county'] ) ) : ?>
							<span class="council-picker__county"><?php echo esc_html( $m['county'] ); ?></span>
						<?php endif; ?>
					</div>

					<form
						method="post"
						action="<?php echo esc_url( home_url( '/meetings/' . rawurlencode( $detail->get_state_id() ) . '/notify/' ) ); ?>"
						class="notify-page__form subscribe-form"
						novalidate
					>
						<?php wp_nonce_field( 'civime_subscribe_inline', '_civime_subscribe_nonce' ); ?>
						<input type="hidden" name="form_action" value="subscribe">

						<!-- Honeypot -->
						<div class="notify-page__hp" aria-hidden="true" inert>
							<label for="detail-subscribe-hp-website"><?php esc_html_e( 'Website', 'civime-meetings' ); ?></label>
							<input type="text" name="subscribe_website" id="detail-subscribe-hp-website" tabindex="-1" autocomplete="off">
						</div>

						<fieldset class="subscribe-form__group">
							<legend class="subscribe-form__legend"><?php esc_html_e( 'Contact Information', 'civime-meetings' ); ?></legend>

							<div class="subscribe-form__field">
								<label for="detail-subscribe-email" class="subscribe-form__label">
									<?php esc_html_e( 'Email Address', 'civime-meetings' ); ?>
								</label>
								<input
									type="email"
									id="detail-subscribe-email"
									name="subscribe_email"
									class="subscribe-form__input"
									autocomplete="email"
									placeholder="you@example.com"
									required
								>
							</div>

							<div class="subscribe-form__field">
								<label for="detail-subscribe-phone" class="subscribe-form__label">
									<?php esc_html_e( 'Phone Number', 'civime-meetings' ); ?>
								</label>
								<input
									type="tel"
									id="detail-subscribe-phone"
									name="subscribe_phone"
									class="subscribe-form__input"
									autocomplete="tel"
									placeholder="(808) 555-1234"
								>
								<p class="subscribe-form__hint">
									<?php esc_html_e( 'Optional. US numbers only.', 'civime-meetings' ); ?>
								</p>
							</div>
						</fieldset>

						<fieldset class="subscribe-form__group">
							<legend class="subscribe-form__legend"><?php esc_html_e( 'Notification Frequency', 'civime-meetings' ); ?></legend>

							<div class="subscribe-form__radios">
								<label class="subscribe-form__radio">
									<input type="radio" name="frequency" value="immediate" checked>
									<?php esc_html_e( 'When new meetings are posted', 'civime-meetings' ); ?>
								</label>
								<label class="subscribe-form__radio">
									<input type="radio" name="frequency" value="daily">
									<?php esc_html_e( 'Daily digest', 'civime-meetings' ); ?>
								</label>
								<label class="subscribe-form__radio">
									<input type="radio" name="frequency" value="weekly">
									<?php esc_html_e( 'Weekly digest', 'civime-meetings' ); ?>
								</label>
							</div>
						</fieldset>

						<div class="subscribe-form__submit">
							<button type="submit" class="btn btn--primary btn--lg">
								<?php esc_html_e( 'Subscribe', 'civime-meetings' ); ?>
							</button>
						</div>

						<p class="subscribe-form__privacy">
							<?php
							printf(
								/* translators: %s: link to privacy policy */
								esc_html__( 'We never share your info. See our %s.', 'civime-meetings' ),
								'<a href="' . esc_url( home_url( '/privacy/' ) ) . '">' . esc_html__( 'privacy policy', 'civime-meetings' ) . '</a>'
							);
							?>
						</p>
					</form>
				</div>

			</div><!-- .notify-page__grid -->
		</div>
	</div>

<?php endif; ?>

</main>

<?php
get_footer();
