<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-wrapper">

    <a class="skip-link" href="#main"><?php esc_html_e( 'Skip to main content', 'civime' ); ?></a>

    <header class="site-header" role="banner">
        <div class="container">
            <div class="site-header__inner">

                <!-- Branding -->
                <a class="site-branding" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                    <svg class="site-logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <line x1="3" y1="22" x2="21" y2="22"/>
                        <line x1="6" y1="18" x2="6" y2="11"/>
                        <line x1="10" y1="18" x2="10" y2="11"/>
                        <line x1="14" y1="18" x2="14" y2="11"/>
                        <line x1="18" y1="18" x2="18" y2="11"/>
                        <polygon points="12 2 20 7 4 7"/>
                    </svg>
                    <span class="site-title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
                </a>

                <!-- Header actions: dark mode toggle + mobile menu trigger -->
                <div class="header-actions">

                    <?php if ( class_exists( 'CiviMe_I18n_Switcher' ) ) : ?>
                        <?php CiviMe_I18n_Switcher::render( 'header' ); ?>
                    <?php endif; ?>

                    <button
                        class="mobile-menu-toggle"
                        type="button"
                        aria-expanded="false"
                        aria-controls="mobile-nav"
                        aria-label="<?php esc_attr_e( 'Open navigation menu', 'civime' ); ?>"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>

                </div>
            </div>
        </div>
        <div class="site-nav-row">
            <div class="container">
                <div class="site-nav-row__inner">
                    <nav class="primary-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'civime' ); ?>">
                        <?php
                        wp_nav_menu( array(
                            'theme_location' => 'primary',
                            'menu_class'     => 'primary-nav__list',
                            'container'      => false,
                            'walker'         => new CiviMe_Nav_Walker(),
                            'fallback_cb'    => 'civime_fallback_primary_nav',
                            'depth'          => 1,
                        ) );
                        ?>
                    </nav>

                    <button
                        class="dark-mode-toggle"
                        type="button"
                        aria-label="<?php esc_attr_e( 'Switch to dark mode', 'civime' ); ?>"
                        aria-live="polite"
                    >
                        <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                        <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile navigation drawer -->
    <div class="mobile-nav-backdrop" aria-hidden="true"></div>

    <nav
        id="mobile-nav"
        class="mobile-nav"
        aria-label="<?php esc_attr_e( 'Mobile navigation', 'civime' ); ?>"
        inert
    >
        <div class="mobile-nav__header">
            <svg class="site-logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <line x1="3" y1="22" x2="21" y2="22"/>
                <line x1="6" y1="18" x2="6" y2="11"/>
                <line x1="10" y1="18" x2="10" y2="11"/>
                <line x1="14" y1="18" x2="14" y2="11"/>
                <line x1="18" y1="18" x2="18" y2="11"/>
                <polygon points="12 2 20 7 4 7"/>
            </svg>
            <span class="site-title" aria-hidden="true"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
            <button
                class="mobile-nav__close"
                type="button"
                aria-label="<?php esc_attr_e( 'Close navigation menu', 'civime' ); ?>"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <?php
        wp_nav_menu( array(
            'theme_location' => 'primary',
            'menu_class'     => 'mobile-nav__list',
            'container'      => false,
            'walker'         => new CiviMe_Mobile_Nav_Walker(),
            'fallback_cb'    => 'civime_fallback_primary_nav',
            'depth'          => 1,
        ) );
        ?>

        <?php if ( class_exists( 'CiviMe_I18n_Switcher' ) ) : ?>
            <?php CiviMe_I18n_Switcher::render( 'mobile' ); ?>
        <?php endif; ?>

        <div class="mobile-nav__footer">
            <button
                class="dark-mode-toggle"
                type="button"
                aria-label="<?php esc_attr_e( 'Switch to dark mode', 'civime' ); ?>"
                aria-live="polite"
            >
                <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/>
                    <line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
                <span class="dark-mode-toggle__label"><?php esc_html_e( 'Dark mode', 'civime' ); ?></span>
            </button>
        </div>
    </nav>
