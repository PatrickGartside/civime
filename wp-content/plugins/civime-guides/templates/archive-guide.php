<?php
/**
 * Template: Guide Archive
 *
 * @package CiviMe_Guides
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$archive    = new CiviMe_Guides_Archive();
$categories = $archive->get_categories();
$active     = $archive->get_active_category();

get_header();
?>

<main id="main" class="site-main" role="main">

	<header class="page-header">
		<div class="container">
			<?php if ( $active ) : ?>
				<p class="guides-eyebrow"><?php esc_html_e( 'Guides', 'civime-guides' ); ?></p>
				<h1 class="page-header__title"><?php echo esc_html( $active->name ); ?></h1>
				<?php if ( $active->description ) : ?>
					<p class="page-header__description"><?php echo esc_html( $active->description ); ?></p>
				<?php endif; ?>
			<?php else : ?>
				<h1 class="page-header__title"><?php esc_html_e( 'Civic Action Guides', 'civime-guides' ); ?></h1>
				<p class="page-header__description"><?php esc_html_e( 'Step-by-step guides to help you engage with government in Hawaii — from testifying to writing letters to elected officials.', 'civime-guides' ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<div class="section">
		<div class="container">

			<?php if ( ! empty( $categories ) ) : ?>
				<nav class="guides-filter" aria-label="<?php esc_attr_e( 'Filter guides by category', 'civime-guides' ); ?>">
					<ul class="guides-filter__list" role="list">
						<li>
							<a
								href="<?php echo esc_url( get_post_type_archive_link( 'civime_guide' ) ); ?>"
								class="guides-filter__pill <?php echo ! $active ? 'guides-filter__pill--active' : ''; ?>"
								<?php echo ! $active ? 'aria-current="page"' : ''; ?>
							>
								<?php esc_html_e( 'All', 'civime-guides' ); ?>
							</a>
						</li>
						<?php foreach ( $categories as $cat ) : ?>
							<li>
								<a
									href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
									class="guides-filter__pill <?php echo $active && $active->term_id === $cat->term_id ? 'guides-filter__pill--active' : ''; ?>"
									<?php echo $active && $active->term_id === $cat->term_id ? 'aria-current="page"' : ''; ?>
								>
									<?php echo esc_html( $cat->name ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>

				<div class="card-grid card-grid--3">

					<?php while ( have_posts() ) : the_post(); ?>

						<?php
						$guide_cats   = wp_get_post_terms( get_the_ID(), 'guide_category' );
						$primary_cat  = ! empty( $guide_cats ) ? $guide_cats[0] : null;
						$read_time    = CiviMe_Guides_Post_Type::reading_time( get_the_content() );
						?>

						<article class="card guide-card" aria-label="<?php echo esc_attr( get_the_title() ); ?>">

							<?php if ( has_post_thumbnail() ) : ?>
								<div class="guide-card__image">
									<a href="<?php the_permalink(); ?>" tabindex="-1">
										<?php the_post_thumbnail( 'medium_large', [ 'loading' => 'lazy' ] ); ?>
									</a>
								</div>
							<?php endif; ?>

							<div class="guide-card__content">

								<?php if ( $primary_cat ) : ?>
									<span class="card__tag"><?php echo esc_html( $primary_cat->name ); ?></span>
								<?php endif; ?>

								<h2 class="card__title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h2>

								<?php if ( has_excerpt() ) : ?>
									<p class="card__body"><?php echo esc_html( get_the_excerpt() ); ?></p>
								<?php endif; ?>

								<div class="card__footer">
									<span class="guide-card__read-time">
										<?php
										printf(
											/* translators: %d: number of minutes */
											esc_html( _n( '%d min read', '%d min read', $read_time, 'civime-guides' ) ),
											$read_time
										);
										?>
									</span>
									<a href="<?php the_permalink(); ?>" class="guide-card__link">
										<?php esc_html_e( 'Read Guide', 'civime-guides' ); ?>
										<span aria-hidden="true">&rarr;</span>
									</a>
								</div>

							</div>

						</article>

					<?php endwhile; ?>

				</div>

				<?php
				the_posts_pagination( [
					'mid_size'  => 2,
					'prev_text' => '&laquo; ' . __( 'Previous', 'civime-guides' ),
					'next_text' => __( 'Next', 'civime-guides' ) . ' &raquo;',
				] );
				?>

			<?php else : ?>

				<div class="meetings-notice meetings-notice--info" role="status">
					<p><?php esc_html_e( 'No guides found. Check back soon — we\'re adding new civic action guides regularly.', 'civime-guides' ); ?></p>
					<?php if ( $active ) : ?>
						<p>
							<a href="<?php echo esc_url( get_post_type_archive_link( 'civime_guide' ) ); ?>">
								<?php esc_html_e( 'View all guides', 'civime-guides' ); ?>
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
