<?php
/**
 * Template Name: Compliance Hub
 *
 * Displays compliance guides from the civime_guide post type
 * in a card grid layout.
 *
 * @package CiviMe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Get the Compliance term.
$compliance_term = get_term_by( 'name', 'Compliance', 'guide_category' );

// Query compliance guides filtered by active locale.
$locale = apply_filters( 'civime_i18n_active_slug', 'en' );

$args = [
	'post_type'      => 'civime_guide',
	'posts_per_page' => 20,
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
	'meta_query'     => ( 'en' === $locale )
		? [ [ 'key' => '_civime_guide_lang', 'compare' => 'NOT EXISTS' ] ]
		: [ [ 'key' => '_civime_guide_lang', 'value' => $locale ] ],
];

if ( $compliance_term && ! is_wp_error( $compliance_term ) ) {
	$args['tax_query'] = [
		[
			'taxonomy' => 'guide_category',
			'field'    => 'term_id',
			'terms'    => $compliance_term->term_id,
		],
	];
}

$guides = new WP_Query( $args );
?>

<main id="main" class="site-main" role="main">

	<header class="page-header">
		<div class="container">
			<h1 class="page-header__title"><?php echo esc_html( get_the_title() ); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="page-header__description"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<div class="section">
		<div class="container">

			<?php
			// Output any page content from the editor.
			while ( have_posts() ) :
				the_post();
				$page_content = get_the_content();
				if ( ! empty( trim( $page_content ) ) ) :
					?>
					<div class="prose" style="margin-bottom: var(--space-lg);">
						<?php the_content(); ?>
					</div>
					<?php
				endif;
			endwhile;
			?>

			<?php if ( $guides->have_posts() ) : ?>

				<div class="card-grid card-grid--2">

					<?php while ( $guides->have_posts() ) : $guides->the_post(); ?>

						<?php
						$guide_cats  = wp_get_post_terms( get_the_ID(), 'guide_category' );
						$primary_cat = ! empty( $guide_cats ) ? $guide_cats[0] : null;
						$read_time   = class_exists( 'CiviMe_Guides_Post_Type' )
							? CiviMe_Guides_Post_Type::reading_time( get_the_content() )
							: 1;
						?>

						<article class="card guide-card" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
							<div class="guide-card__content">

								<?php if ( $primary_cat ) : ?>
									<span class="card__tag"><?php echo esc_html( $primary_cat->name ); ?></span>
								<?php endif; ?>

								<h2 class="card__title">
									<a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a>
								</h2>

								<?php if ( has_excerpt() ) : ?>
									<p class="card__body"><?php echo esc_html( get_the_excerpt() ); ?></p>
								<?php endif; ?>

								<div class="card__footer">
									<span class="guide-card__read-time">
										<?php
										printf(
											esc_html( _n( '%d min read', '%d min read', $read_time, 'civime' ) ),
											$read_time
										);
										?>
									</span>
									<a href="<?php the_permalink(); ?>" class="guide-card__link">
										<?php esc_html_e( 'Read Guide', 'civime' ); ?>
										<span aria-hidden="true">&rarr;</span>
									</a>
								</div>

							</div>
						</article>

					<?php endwhile; ?>

				</div>

			<?php else : ?>

				<div class="meetings-notice meetings-notice--info" role="status">
					<p><?php esc_html_e( 'Compliance guides are coming soon. Check back shortly.', 'civime' ); ?></p>
				</div>

			<?php endif; ?>

			<?php wp_reset_postdata(); ?>

		</div>
	</div>

</main>

<?php
get_footer();
