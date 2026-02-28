<?php
/**
 * 404 error page template.
 *
 * @package CiviMe
 */

get_header();
?>

<main id="main" class="site-main" role="main">
    <div class="container">

        <section class="error-404" aria-labelledby="error-heading">

            <p class="error-404__code" aria-hidden="true">404</p>

            <h1 class="error-404__title" id="error-heading">
                <?php esc_html_e( 'Page not found', 'civime' ); ?>
            </h1>

            <p class="error-404__message">
                <?php esc_html_e(
                    'The page you\'re looking for doesn\'t exist or has been moved. Try searching below, or head back home.',
                    'civime'
                ); ?>
            </p>

            <div style="max-width: 480px; margin: 0 auto 2rem;">
                <?php get_search_form(); ?>
            </div>

            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">
                <?php esc_html_e( 'Go back home', 'civime' ); ?>
            </a>

        </section>

    </div>
</main>

<?php
get_footer();
