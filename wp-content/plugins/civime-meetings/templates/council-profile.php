<?php
/**
 * Template: Council Profile
 *
 * Renders a full council profile page with description, legal authority,
 * board members, upcoming meetings, and how-to-participate guidance.
 *
 * @package CiviMe_Meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$council_slug = get_query_var( 'civime_council_slug' );
$api          = civime_api();

// Lookup council by slug
$council_result = $api->get_council_by_slug( $council_slug );
$council        = null;
$council_id     = 0;
$error          = false;

if ( is_wp_error( $council_result ) ) {
	$error = true;
} else {
	$council    = $council_result['data'] ?? null;
	$council_id = (int) ( $council['id'] ?? 0 );
}

// Fetch profile, authority, members, vacancies, and upcoming meetings
$profile    = null;
$authority  = [];
$members    = [];
$vacancies  = [];
$meetings   = [];

if ( $council_id > 0 ) {
	$profile_result = $api->get_council_profile( $council_id );
	if ( ! is_wp_error( $profile_result ) ) {
		$profile = $profile_result['data'] ?? null;
	}

	$authority_result = $api->get_council_authority( $council_id );
	if ( ! is_wp_error( $authority_result ) ) {
		$authority = $authority_result['data'] ?? [];
	}

	$members_result = $api->get_council_members( $council_id );
	if ( ! is_wp_error( $members_result ) ) {
		$members = $members_result['data'] ?? [];
	}

	$vacancies_result = $api->get_council_vacancies( $council_id );
	if ( ! is_wp_error( $vacancies_result ) ) {
		$vacancies = $vacancies_result['data'] ?? [];
	}

	$meetings_result = $api->get_council_meetings( $council_id, [ 'limit' => 5 ] );
	if ( ! is_wp_error( $meetings_result ) ) {
		$meetings = $meetings_result['data'] ?? [];
	}
}

get_header();
?>

<main id="main" class="site-main" role="main">

	<?php if ( $error || ! $council ) : ?>

		<header class="page-header">
			<div class="container">
				<h1 class="page-header__title"><?php esc_html_e( 'Council Not Found', 'civime-meetings' ); ?></h1>
			</div>
		</header>
		<div class="section">
			<div class="container">
				<div class="meetings-notice meetings-notice--warning" role="alert">
					<p><?php esc_html_e( 'This council could not be found.', 'civime-meetings' ); ?></p>
					<p>
						<a href="<?php echo esc_url( home_url( '/councils/' ) ); ?>">
							<?php esc_html_e( 'Browse all councils', 'civime-meetings' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>

	<?php else : ?>

		<header class="page-header">
			<div class="container">
				<nav class="meeting-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'civime-meetings' ); ?>">
					<ol class="meeting-breadcrumb__list">
						<li><a href="<?php echo esc_url( home_url( '/councils/' ) ); ?>"><?php esc_html_e( 'Councils', 'civime-meetings' ); ?></a></li>
						<li aria-current="page"><?php echo esc_html( $council['name'] ); ?></li>
					</ol>
				</nav>

				<?php if ( $profile && $profile['entity_type'] ) : ?>
					<p class="meeting-detail__council">
						<?php echo esc_html( ucfirst( $profile['entity_type'] ) ); ?>
						<?php if ( $profile['jurisdiction'] ) : ?>
							&middot; <?php echo esc_html( ucfirst( $profile['jurisdiction'] ) ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<h1 class="page-header__title"><?php echo esc_html( $council['name'] ); ?></h1>

				<?php if ( $profile && $profile['plain_description'] ) : ?>
					<p class="page-header__description"><?php echo esc_html( $profile['plain_description'] ); ?></p>
				<?php endif; ?>
			</div>
		</header>

		<div class="section">
			<div class="container">
				<div class="council-profile">

					<!-- Main Content Column -->
					<div class="council-profile__main">

						<?php if ( $profile && $profile['decisions_examples'] ) : ?>
							<section class="council-profile__section" aria-labelledby="cp-decisions">
								<h2 id="cp-decisions" class="council-profile__section-heading">
									<?php esc_html_e( 'What This Board Does', 'civime-meetings' ); ?>
								</h2>
								<div class="council-profile__section-body prose">
									<?php echo wp_kses_post( wpautop( $profile['decisions_examples'] ) ); ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ( $profile && $profile['why_care'] ) : ?>
							<section class="council-profile__section" aria-labelledby="cp-why-care">
								<h2 id="cp-why-care" class="council-profile__section-heading">
									<?php esc_html_e( 'Why You Should Care', 'civime-meetings' ); ?>
								</h2>
								<div class="council-profile__section-body prose">
									<?php echo wp_kses_post( wpautop( $profile['why_care'] ) ); ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ( ! empty( $authority ) ) : ?>
							<section class="council-profile__section" aria-labelledby="cp-authority">
								<h2 id="cp-authority" class="council-profile__section-heading">
									<?php esc_html_e( 'Legal Authority', 'civime-meetings' ); ?>
								</h2>
								<ul class="council-profile__authority-list">
									<?php foreach ( $authority as $ref ) : ?>
										<li class="council-profile__authority-item">
											<?php if ( ! empty( $ref['url'] ) ) : ?>
												<a href="<?php echo esc_url( $ref['url'] ); ?>" target="_blank" rel="noopener noreferrer">
													<?php echo esc_html( $ref['citation'] ); ?>
												</a>
											<?php else : ?>
												<strong><?php echo esc_html( $ref['citation'] ); ?></strong>
											<?php endif; ?>
											<?php if ( ! empty( $ref['description'] ) ) : ?>
												<span class="council-profile__authority-desc">
													— <?php echo esc_html( $ref['description'] ); ?>
												</span>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</section>
						<?php endif; ?>

						<?php if ( ! empty( $members ) ) : ?>
							<section class="council-profile__section" aria-labelledby="cp-members">
								<h2 id="cp-members" class="council-profile__section-heading">
									<?php
									printf(
										/* translators: %d: number of board members */
										esc_html__( 'Board Members (%d)', 'civime-meetings' ),
										count( $members )
									);
									?>
								</h2>
								<div class="council-profile__members-grid">
									<?php foreach ( $members as $member ) : ?>
										<div class="council-profile__member-card">
											<p class="council-profile__member-name"><?php echo esc_html( $member['name'] ); ?></p>
											<?php if ( ! empty( $member['title'] ) ) : ?>
												<p class="council-profile__member-title"><?php echo esc_html( $member['title'] ); ?></p>
											<?php endif; ?>
											<?php if ( $member['role'] !== 'member' ) : ?>
												<span class="meeting-card__badge">
													<?php echo esc_html( ucfirst( str_replace( '-', ' ', $member['role'] ) ) ); ?>
												</span>
											<?php endif; ?>
											<?php if ( ! empty( $member['term_end'] ) ) : ?>
												<p class="council-profile__member-term">
													<?php
													printf(
														/* translators: %s: term end date */
														esc_html__( 'Term ends %s', 'civime-meetings' ),
														esc_html( wp_date( 'M j, Y', strtotime( $member['term_end'] ) ) )
													);
													?>
												</p>
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ( ! empty( $meetings ) ) : ?>
							<section class="council-profile__section" aria-labelledby="cp-meetings">
								<h2 id="cp-meetings" class="council-profile__section-heading">
									<?php esc_html_e( 'Upcoming Meetings', 'civime-meetings' ); ?>
								</h2>
								<?php foreach ( $meetings as $meeting ) : ?>
									<?php
									$state_id    = $meeting['state_id'] ?? '';
									$meeting_url = home_url( '/meetings/' . $state_id );
									$time_raw    = $meeting['meeting_time'] ?? '';
									$time_label  = '' !== $time_raw ? wp_date( 'g:i A', strtotime( $time_raw ) ) : '';
									$date_label  = ! empty( $meeting['meeting_date'] )
										? wp_date( 'l, F j, Y', strtotime( $meeting['meeting_date'] ) )
										: '';
									?>
									<article class="meeting-card">
										<div class="meeting-card__body">
											<h3 class="meeting-card__title">
												<a href="<?php echo esc_url( $meeting_url ); ?>">
													<?php echo esc_html( $meeting['title'] ?? $council['name'] ); ?>
												</a>
											</h3>
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
												<?php esc_html_e( 'Details', 'civime-meetings' ); ?>
											</a>
										</div>
									</article>
								<?php endforeach; ?>

								<p style="margin-top: var(--space-3);">
									<a href="<?php echo esc_url( home_url( '/meetings/?council_id=' . $council_id ) ); ?>">
										<?php esc_html_e( 'View all meetings for this council', 'civime-meetings' ); ?>
									</a>
								</p>
							</section>
						<?php endif; ?>

						<?php if ( $profile && ( $profile['testimony_instructions'] || $profile['public_comment_info'] || $profile['testimony_email'] ) ) : ?>
							<section class="council-profile__section" aria-labelledby="cp-participate">
								<h2 id="cp-participate" class="council-profile__section-heading">
									<?php esc_html_e( 'How to Participate', 'civime-meetings' ); ?>
								</h2>
								<div class="council-profile__section-body prose">
									<?php if ( $profile['testimony_instructions'] ) : ?>
										<?php echo wp_kses_post( wpautop( $profile['testimony_instructions'] ) ); ?>
									<?php endif; ?>

									<?php if ( $profile['testimony_email'] ) : ?>
										<p>
											<strong><?php esc_html_e( 'Submit Written Testimony:', 'civime-meetings' ); ?></strong>
											<a href="mailto:<?php echo esc_attr( $profile['testimony_email'] ); ?>">
												<?php echo esc_html( $profile['testimony_email'] ); ?>
											</a>
										</p>
									<?php endif; ?>

									<?php if ( $profile['public_comment_info'] ) : ?>
										<?php echo wp_kses_post( wpautop( $profile['public_comment_info'] ) ); ?>
									<?php endif; ?>
								</div>
							</section>
						<?php endif; ?>

					</div>

					<!-- Sidebar -->
					<aside class="council-profile__sidebar">

						<div class="council-profile__at-a-glance">
							<h2 class="council-profile__sidebar-heading">
								<?php esc_html_e( 'At a Glance', 'civime-meetings' ); ?>
							</h2>
							<dl class="council-profile__facts">
								<?php if ( $profile && $profile['entity_type'] ) : ?>
									<dt><?php esc_html_e( 'Type', 'civime-meetings' ); ?></dt>
									<dd><?php echo esc_html( ucfirst( $profile['entity_type'] ) ); ?></dd>
								<?php endif; ?>

								<?php if ( $profile && $profile['jurisdiction'] ) : ?>
									<dt><?php esc_html_e( 'Jurisdiction', 'civime-meetings' ); ?></dt>
									<dd><?php echo esc_html( ucfirst( $profile['jurisdiction'] ) ); ?></dd>
								<?php endif; ?>

								<?php if ( $profile && $profile['member_count'] ) : ?>
									<dt><?php esc_html_e( 'Members', 'civime-meetings' ); ?></dt>
									<dd><?php echo esc_html( (string) $profile['member_count'] ); ?></dd>
								<?php endif; ?>

								<?php if ( $profile && $profile['appointment_method'] ) : ?>
									<dt><?php esc_html_e( 'Appointed By', 'civime-meetings' ); ?></dt>
									<dd><?php echo esc_html( $profile['appointment_method'] ); ?></dd>
								<?php endif; ?>

								<?php if ( $profile && $profile['term_length'] ) : ?>
									<dt><?php esc_html_e( 'Term Length', 'civime-meetings' ); ?></dt>
									<dd><?php echo esc_html( $profile['term_length'] ); ?></dd>
								<?php endif; ?>

								<?php if ( $profile && $profile['meeting_schedule'] ) : ?>
									<dt><?php esc_html_e( 'Meets', 'civime-meetings' ); ?></dt>
									<dd><?php echo esc_html( $profile['meeting_schedule'] ); ?></dd>
								<?php endif; ?>

								<?php if ( $profile && $profile['default_location'] ) : ?>
									<dt><?php esc_html_e( 'Location', 'civime-meetings' ); ?></dt>
									<dd><?php echo esc_html( $profile['default_location'] ); ?></dd>
								<?php endif; ?>

								<?php if ( $profile && $profile['virtual_option'] ) : ?>
									<dt><?php esc_html_e( 'Virtual', 'civime-meetings' ); ?></dt>
									<dd><?php esc_html_e( 'Yes, virtual attendance available', 'civime-meetings' ); ?></dd>
								<?php endif; ?>
							</dl>

							<?php if ( $profile && $profile['official_website'] ) : ?>
								<p class="council-profile__website">
									<a href="<?php echo esc_url( $profile['official_website'] ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'Official Website', 'civime-meetings' ); ?>
									</a>
								</p>
							<?php endif; ?>

							<?php if ( $profile && ( $profile['contact_email'] || $profile['contact_phone'] ) ) : ?>
								<div class="council-profile__contact">
									<h3 class="council-profile__sidebar-subheading"><?php esc_html_e( 'Contact', 'civime-meetings' ); ?></h3>
									<?php if ( $profile['contact_email'] ) : ?>
										<p>
											<a href="mailto:<?php echo esc_attr( $profile['contact_email'] ); ?>">
												<?php echo esc_html( $profile['contact_email'] ); ?>
											</a>
										</p>
									<?php endif; ?>
									<?php if ( $profile['contact_phone'] ) : ?>
										<p>
											<a href="tel:<?php echo esc_attr( $profile['contact_phone'] ); ?>">
												<?php echo esc_html( $profile['contact_phone'] ); ?>
											</a>
										</p>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>

						<?php if ( ! empty( $vacancies ) ) : ?>
							<div class="council-profile__vacancies">
								<h2 class="council-profile__sidebar-heading">
									<?php
									printf(
										/* translators: %d: number of open seats */
										esc_html( _n( '%d Open Seat', '%d Open Seats', count( $vacancies ), 'civime-meetings' ) ),
										count( $vacancies )
									);
									?>
								</h2>
								<?php foreach ( $vacancies as $vacancy ) : ?>
									<div class="council-profile__vacancy-item">
										<?php if ( ! empty( $vacancy['seat_description'] ) ) : ?>
											<p class="council-profile__vacancy-desc"><strong><?php echo esc_html( $vacancy['seat_description'] ); ?></strong></p>
										<?php endif; ?>
										<?php if ( ! empty( $vacancy['application_deadline'] ) ) : ?>
											<p class="council-profile__vacancy-deadline">
												<?php
												printf(
													/* translators: %s: application deadline date */
													esc_html__( 'Deadline: %s', 'civime-meetings' ),
													esc_html( wp_date( 'M j, Y', strtotime( $vacancy['application_deadline'] ) ) )
												);
												?>
											</p>
										<?php endif; ?>
										<?php if ( ! empty( $vacancy['application_url'] ) ) : ?>
											<p>
												<a href="<?php echo esc_url( $vacancy['application_url'] ); ?>" class="btn btn--small" target="_blank" rel="noopener noreferrer">
													<?php esc_html_e( 'Apply', 'civime-meetings' ); ?>
												</a>
											</p>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<!-- Get Notified CTA -->
						<div class="meeting-detail__cta">
							<h2><?php esc_html_e( 'Get Notified', 'civime-meetings' ); ?></h2>
							<p><?php esc_html_e( 'Sign up to receive alerts when this council schedules new meetings.', 'civime-meetings' ); ?></p>
							<a href="<?php echo esc_url( home_url( '/notifications/?council_id=' . $council_id ) ); ?>" class="btn btn--primary">
								<?php esc_html_e( 'Set Up Alerts', 'civime-meetings' ); ?>
							</a>
						</div>

					</aside>

				</div>
			</div>
		</div>

	<?php endif; ?>

</main>

<?php
get_footer();
