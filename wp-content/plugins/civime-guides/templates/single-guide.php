<?php
/**
 * Template: Single Guide
 *
 * @package CiviMe_Guides
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$single      = new CiviMe_Guides_Single();
$categories  = $single->get_categories();
$read_time   = $single->get_reading_time();
$related     = $single->get_related();
$primary_cat = ! empty( $categories ) ? $categories[0] : null;

get_header();
?>

<main id="main" class="site-main" role="main">

	<nav class="guides-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'civime-guides' ); ?>">
		<div class="container container--narrow">
			<ol class="guides-breadcrumb__list">
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'civime_guide' ) ); ?>"><?php esc_html_e( 'Guides', 'civime-guides' ); ?></a></li>
				<?php if ( $primary_cat ) : ?>
					<li><a href="<?php echo esc_url( get_term_link( $primary_cat ) ); ?>"><?php echo esc_html( $primary_cat->name ); ?></a></li>
				<?php endif; ?>
				<li aria-current="page"><?php the_title(); ?></li>
			</ol>
		</div>
	</nav>

	<header class="page-header">
		<div class="container container--narrow">
			<?php if ( $primary_cat ) : ?>
				<p class="guides-eyebrow"><?php echo esc_html( $primary_cat->name ); ?></p>
			<?php endif; ?>
			<h1 class="page-header__title"><?php the_title(); ?></h1>
			<div class="guides-meta">
				<span class="guide-card__read-time">
					<?php
					printf(
						/* translators: %d: number of minutes */
						esc_html( _n( '%d min read', '%d min read', $read_time, 'civime-guides' ) ),
						$read_time
					);
					?>
				</span>
			</div>
		</div>
	</header>

	<div class="section">
		<div class="container container--narrow">

			<article <?php post_class( 'guide-detail' ); ?>>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="guide-detail__featured-image">
						<?php the_post_thumbnail( 'large' ); ?>
					</figure>
				<?php endif; ?>

				<div class="prose">
					<?php the_content(); ?>
				</div>

			</article>

			<?php if ( ! empty( $related ) ) : ?>

				<section class="guide-detail__related" aria-labelledby="related-heading">
					<h2 id="related-heading"><?php esc_html_e( 'Related Guides', 'civime-guides' ); ?></h2>

					<div class="card-grid card-grid--3">
						<?php foreach ( $related as $rel_post ) : ?>
							<?php
							$rel_cats    = wp_get_post_terms( $rel_post->ID, 'guide_category' );
							$rel_cat     = ! empty( $rel_cats ) ? $rel_cats[0] : null;
							$rel_time    = CiviMe_Guides_Post_Type::reading_time( $rel_post->post_content );
							?>
							<article class="card guide-card">
								<?php if ( $rel_cat ) : ?>
									<div class="guide-card__content">
										<span class="card__tag"><?php echo esc_html( $rel_cat->name ); ?></span>
										<h3 class="card__title">
											<a href="<?php echo esc_url( get_permalink( $rel_post ) ); ?>"><?php echo esc_html( $rel_post->post_title ); ?></a>
										</h3>
										<div class="card__footer">
											<span class="guide-card__read-time">
												<?php
												printf(
													esc_html( _n( '%d min read', '%d min read', $rel_time, 'civime-guides' ) ),
													$rel_time
												);
												?>
											</span>
											<a href="<?php echo esc_url( get_permalink( $rel_post ) ); ?>" class="guide-card__link">
												<?php esc_html_e( 'Read Guide', 'civime-guides' ); ?>
												<span aria-hidden="true">&rarr;</span>
											</a>
										</div>
									</div>
								<?php else : ?>
									<div class="guide-card__content">
										<h3 class="card__title">
											<a href="<?php echo esc_url( get_permalink( $rel_post ) ); ?>"><?php echo esc_html( $rel_post->post_title ); ?></a>
										</h3>
										<div class="card__footer">
											<span class="guide-card__read-time">
												<?php
												printf(
													esc_html( _n( '%d min read', '%d min read', $rel_time, 'civime-guides' ) ),
													$rel_time
												);
												?>
											</span>
											<a href="<?php echo esc_url( get_permalink( $rel_post ) ); ?>" class="guide-card__link">
												<?php esc_html_e( 'Read Guide', 'civime-guides' ); ?>
												<span aria-hidden="true">&rarr;</span>
											</a>
										</div>
									</div>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
				</section>

			<?php endif; ?>

			<aside class="guide-detail__cta">
				<h2><?php esc_html_e( 'Get Involved', 'civime-guides' ); ?></h2>
				<p><?php esc_html_e( 'Ready to take action? Browse upcoming government meetings and make your voice heard.', 'civime-guides' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--primary">
					<?php esc_html_e( 'Browse Meetings', 'civime-guides' ); ?>
				</a>
			</aside>

		</div>
	</div>

</main>

<?php
get_footer();
