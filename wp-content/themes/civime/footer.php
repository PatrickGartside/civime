    <footer class="site-footer" role="contentinfo">
        <div class="container">
            <div class="site-footer__inner">

                <!-- Branding + description -->
                <div class="footer-branding">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-branding" rel="home">
                        <span class="site-title"><?php bloginfo( 'name' ); ?></span>
                    </a>
                    <p class="site-description">
                        <?php esc_html_e( 'Civic engagement for Hawaii', 'civime' ); ?>
                    </p>
                </div>

                <!-- Footer navigation -->
                <nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer navigation', 'civime' ); ?>">
                    <?php
                    wp_nav_menu( array(
                        'theme_location' => 'footer',
                        'menu_class'     => 'footer-nav__list',
                        'container'      => false,
                        'walker'         => new CiviMe_Footer_Nav_Walker(),
                        'fallback_cb'    => 'civime_fallback_footer_nav',
                        'depth'          => 1,
                    ) );
                    ?>
                </nav>

            </div>

            <!-- Bottom bar: copyright + badges -->
            <div class="footer-bottom">
                <p class="footer-copyright">
                    &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
                    <?php
                    printf(
                        /* translators: %s: site name */
                        esc_html__( '%s &mdash; Civic engagement for Hawaii', 'civime' ),
                        esc_html( get_bloginfo( 'name' ) )
                    );
                    ?>
                    <?php
                    $privacy_page = get_privacy_policy_url();
                    if ( $privacy_page ) :
                        ?>
                        &nbsp;&bull;&nbsp;
                        <a href="<?php echo esc_url( $privacy_page ); ?>" class="footer-nav__link">
                            <?php esc_html_e( 'Privacy Policy', 'civime' ); ?>
                        </a>
                    <?php endif; ?>
                </p>

                <div class="footer-badges">
                    <a
                        href="https://github.com/patrickgartside/civi.me"
                        class="badge"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="<?php esc_attr_e( 'Open source on GitHub (opens in new tab)', 'civime' ); ?>"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14" aria-hidden="true" focusable="false">
                            <path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.44 9.8 8.21 11.39.6.11.82-.26.82-.58l-.01-2.04c-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.33-1.76-1.33-1.76-1.09-.74.08-.73.08-.73 1.2.08 1.84 1.24 1.84 1.24 1.07 1.83 2.81 1.3 3.49 1 .11-.78.42-1.3.76-1.6-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.13-.3-.54-1.52.12-3.18 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 3-.4c1.02 0 2.04.14 3 .4 2.28-1.55 3.29-1.23 3.29-1.23.66 1.66.25 2.88.12 3.18.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.63-5.48 5.92.43.37.82 1.1.82 2.22l-.01 3.29c0 .32.21.7.83.58C20.57 21.79 24 17.3 24 12c0-6.63-5.37-12-12-12z"/>
                        </svg>
                        <?php esc_html_e( 'Open source', 'civime' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </footer>

</div><!-- .site-wrapper -->

<?php wp_footer(); ?>

</body>
</html>
