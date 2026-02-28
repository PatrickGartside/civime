<?php
/**
 * Template: Councils List
 *
 * @package CiviMe_Meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$list     = new CiviMe_Meetings_Councils_List();
$councils = $list->get_councils();

get_header();
?>

<main id="main" class="site-main" role="main">

	<header class="page-header">
		<div class="container">
			<h1 class="page-header__title"><?php esc_html_e( 'Councils & Boards', 'civime-meetings' ); ?></h1>
			<p class="page-header__description"><?php esc_html_e( 'Browse government councils, boards, and commissions across Hawaii. Select a council to see its upcoming meetings.', 'civime-meetings' ); ?></p>
		</div>
	</header>

	<div class="section">
		<div class="container">

			<form class="councils-filters" method="get" action="<?php echo esc_url( home_url( '/councils/' ) ); ?>" role="search" aria-label="<?php esc_attr_e( 'Filter councils', 'civime-meetings' ); ?>">
				<div class="councils-filters__row">
					<div class="councils-filters__field">
						<label for="councils-search" class="councils-filters__label">
							<?php esc_html_e( 'Search', 'civime-meetings' ); ?>
						</label>
						<input
							type="search"
							id="councils-search"
							name="q"
							value="<?php echo esc_attr( $list->get_search() ); ?>"
							placeholder="<?php esc_attr_e( 'Search councils…', 'civime-meetings' ); ?>"
							class="councils-filters__input"
						>
					</div>
					<div class="councils-filters__field">
						<label for="councils-county" class="councils-filters__label">
							<?php esc_html_e( 'County', 'civime-meetings' ); ?>
						</label>
						<select id="councils-county" name="county" class="councils-filters__select">
							<option value=""><?php esc_html_e( 'All', 'civime-meetings' ); ?></option>
							<option value="state" <?php selected( 'state', $list->get_county() ); ?>><?php esc_html_e( 'State', 'civime-meetings' ); ?></option>
							<option value="honolulu" <?php selected( 'honolulu', $list->get_county() ); ?>><?php esc_html_e( 'Honolulu', 'civime-meetings' ); ?></option>
							<option value="maui" <?php selected( 'maui', $list->get_county() ); ?>><?php esc_html_e( 'Maui', 'civime-meetings' ); ?></option>
							<option value="hawaii" <?php selected( 'hawaii', $list->get_county() ); ?>>Hawai&#x02BB;i</option>
							<option value="kauai" <?php selected( 'kauai', $list->get_county() ); ?>>Kaua&#x02BB;i</option>
						</select>
					</div>
					<div class="councils-filters__actions">
						<button type="submit" class="btn btn--primary">
							<?php esc_html_e( 'Search', 'civime-meetings' ); ?>
						</button>
						<?php if ( $list->has_filters() ) : ?>
							<a href="<?php echo esc_url( home_url( '/councils/' ) ); ?>" class="btn btn--ghost">
								<?php esc_html_e( 'Clear', 'civime-meetings' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</form>

			<?php if ( $list->has_error() ) : ?>

				<div class="meetings-notice meetings-notice--warning" role="alert">
					<p><strong><?php esc_html_e( 'Council data is temporarily unavailable.', 'civime-meetings' ); ?></strong></p>
					<p><?php esc_html_e( "We're working on connecting to the database. Check back soon.", 'civime-meetings' ); ?></p>
				</div>

			<?php elseif ( empty( $councils ) ) : ?>

				<div class="meetings-notice meetings-notice--info" role="status">
					<p><?php esc_html_e( 'No councils found matching your search.', 'civime-meetings' ); ?></p>
					<p>
						<a href="<?php echo esc_url( home_url( '/councils/' ) ); ?>">
							<?php esc_html_e( 'View all councils', 'civime-meetings' ); ?>
						</a>
					</p>
				</div>

			<?php else : ?>

				<p class="councils-results__count">
					<?php
					printf(
						/* translators: %d: number of councils */
						esc_html__( '%d councils found', 'civime-meetings' ),
						count( $councils )
					);
					?>
				</p>

				<div class="councils-grid">
					<?php foreach ( $councils as $council ) : ?>
						<?php $council_id = absint( $council['id'] ); ?>
						<article class="council-card">
							<h2 class="council-card__name">
								<a href="<?php echo esc_url( home_url( '/meetings/?council_id=' . $council_id ) ); ?>">
									<?php echo esc_html( $council['name'] ); ?>
								</a>
							</h2>

							<?php if ( ! empty( $council['parent_name'] ) ) : ?>
								<p class="council-card__parent"><?php echo esc_html( $council['parent_name'] ); ?></p>
							<?php endif; ?>

							<div class="council-card__meta">
								<?php if ( ! empty( $council['county'] ) ) : ?>
									<span class="council-card__county"><?php echo esc_html( ucfirst( $council['county'] ) ); ?></span>
								<?php endif; ?>

								<span class="council-card__meetings">
									<?php
									$meeting_count = (int) ( $council['meeting_count'] ?? 0 );
									printf(
										/* translators: %d: number of upcoming meetings */
										esc_html( _n( '%d upcoming meeting', '%d upcoming meetings', $meeting_count, 'civime-meetings' ) ),
										$meeting_count
									);
									?>
								</span>

								<?php if ( ! empty( $council['next_meeting_date'] ) ) : ?>
									<span class="council-card__next">
										<?php
										printf(
											/* translators: %s: formatted date */
											esc_html__( 'Next: %s', 'civime-meetings' ),
											esc_html( wp_date( 'M j, Y', strtotime( $council['next_meeting_date'] ) ) )
										);
										?>
									</span>
								<?php endif; ?>
							</div>

							<div class="council-card__actions">
								<a href="<?php echo esc_url( home_url( '/meetings/?council_id=' . $council_id ) ); ?>" class="btn btn--small">
									<?php esc_html_e( 'View Meetings', 'civime-meetings' ); ?>
								</a>
								<a href="<?php echo esc_url( add_query_arg( 'council_id', $council_id, home_url( '/meetings/subscribe/' ) ) ); ?>" class="btn btn--small btn--ghost">
									<?php esc_html_e( 'Get Notified', 'civime-meetings' ); ?>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

			<?php endif; ?>

		</div>
	</div>

</main>

<?php
get_footer();
