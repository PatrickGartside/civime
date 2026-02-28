<?php
/**
 * Main template file — fallback for all content types.
 *
 * @package CiviMe
 */

get_header();
?>

<main id="main" class="site-main" role="main">
    <div class="container section">

        <?php if ( is_home() && ! is_front_page() ) : ?>
            <header class="page-header">
                <h1 class="page-header__title"><?php esc_html_e( 'Latest Posts', 'civime' ); ?></h1>
            </header>
        <?php endif; ?>

        <?php if ( have_posts() ) : ?>

            <div class="card-grid card-grid--3">
                <?php
                while ( have_posts() ) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>

                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="card-thumbnail">
                                <a href="<?php the_permalink(); ?>" tabindex="-1">
                                    <?php the_post_thumbnail( 'civime-card' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <header class="card-header">
                            <?php
                            $category = get_the_category();
                            if ( ! empty( $category ) ) :
                                ?>
                                <span class="card__tag"><?php echo esc_html( $category[0]->name ); ?></span>
                            <?php endif; ?>

                            <h2 class="card__title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                        </header>

                        <div class="card__body">
                            <?php the_excerpt(); ?>
                        </div>

                        <footer class="card__footer">
                            <time class="text-muted text-sm" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date() ); ?>
                            </time>
                        </footer>

                    </article>
                <?php endwhile; ?>
            </div>

            <?php
            the_posts_pagination( array(
                'prev_text' => __( 'Previous', 'civime' ),
                'next_text' => __( 'Next', 'civime' ),
                'class'     => 'pagination',
            ) );
            ?>

        <?php else : ?>

            <div class="prose">
                <p><?php esc_html_e( 'No posts found.', 'civime' ); ?></p>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
