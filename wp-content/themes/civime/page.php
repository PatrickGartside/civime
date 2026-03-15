<?php
/**
 * Standard page template.
 *
 * @package CiviMe
 */

get_header();
?>

<main id="main" class="site-main" role="main">

    <?php while ( have_posts() ) : the_post(); ?>

        <header class="page-header">
            <div class="container">
                <h1 class="page-header__title"><?php echo civime_page_header_icon(); ?><?php echo esc_html( get_the_title() ); ?></h1>
                <?php if ( has_excerpt() ) : ?>
                    <p class="page-header__description"><?php echo esc_html( get_the_excerpt() ); ?></p>
                <?php endif; ?>
            </div>
        </header>

        <article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>

            <div class="page-content section">
                <div class="container">
                    <div class="prose">
                        <?php
                        the_content();

                        wp_link_pages( array(
                            'before'      => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'civime' ) . '">',
                            'after'       => '</nav>',
                            'link_before' => '<span>',
                            'link_after'  => '</span>',
                        ) );
                        ?>
                    </div>
                </div>
            </div>

        </article>

    <?php endwhile; ?>

</main>

<?php
get_footer();
