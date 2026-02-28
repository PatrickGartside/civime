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
