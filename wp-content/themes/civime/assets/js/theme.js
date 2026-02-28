/**
 * CiviMe Theme — Main JavaScript
 *
 * Handles:
 * - Dark mode toggle (localStorage + system preference)
 * - Mobile menu toggle
 * - Skip to content smooth scroll
 */

( function () {
    'use strict';

    // =========================================================================
    // Dark Mode
    // =========================================================================

    const THEME_STORAGE_KEY = 'civime-color-scheme';
    const documentRoot = document.documentElement;

    /**
     * Reads the stored preference or falls back to null (system will decide via CSS).
     */
    function getStoredTheme() {
        try {
            return localStorage.getItem( THEME_STORAGE_KEY );
        } catch {
            return null;
        }
    }

    /**
     * Persists the user's explicit theme choice.
     */
    function storeTheme( theme ) {
        try {
            localStorage.setItem( THEME_STORAGE_KEY, theme );
        } catch {
            // localStorage unavailable — degrade silently
        }
    }

    /**
     * Applies a theme ('light' | 'dark') or removes the attribute to let the
     * CSS media query take over.
     */
    function applyTheme( theme ) {
        if ( theme === 'light' || theme === 'dark' ) {
            documentRoot.setAttribute( 'data-theme', theme );
        } else {
            documentRoot.removeAttribute( 'data-theme' );
        }
        updateToggleButtonLabel( theme );
    }

    /**
     * Determines whether the system currently prefers dark mode.
     */
    function systemPrefersDark() {
        return window.matchMedia( '(prefers-color-scheme: dark)' ).matches;
    }

    /**
     * Toggles between light and dark, taking the current effective theme into
     * account so the button always switches to the opposite of what is visible.
     */
    function toggleTheme() {
        const stored = getStoredTheme();
        const currentlyDark =
            stored === 'dark' ||
            ( stored === null && systemPrefersDark() );

        const nextTheme = currentlyDark ? 'light' : 'dark';
        storeTheme( nextTheme );
        applyTheme( nextTheme );
    }

    /**
     * Updates the aria-label on the dark mode toggle to reflect the action
     * that will happen when clicked.
     */
    function updateToggleButtonLabel( theme ) {
        const btn = document.querySelector( '.dark-mode-toggle' );
        if ( ! btn ) return;

        const effectiveDark =
            theme === 'dark' ||
            ( ( theme === null || theme === undefined ) && systemPrefersDark() );

        btn.setAttribute(
            'aria-label',
            effectiveDark ? 'Switch to light mode' : 'Switch to dark mode'
        );
    }

    // Apply stored preference immediately (prevents flash)
    const initialTheme = getStoredTheme();
    if ( initialTheme ) {
        applyTheme( initialTheme );
    }

    // =========================================================================
    // Mobile Menu
    // =========================================================================

    function initMobileMenu() {
        const toggleBtn  = document.querySelector( '.mobile-menu-toggle' );
        const closeBtn   = document.querySelector( '.mobile-nav__close' );
        const mobileNav  = document.querySelector( '.mobile-nav' );
        const backdrop   = document.querySelector( '.mobile-nav-backdrop' );

        if ( ! toggleBtn || ! mobileNav ) return;

        function openMenu() {
            mobileNav.classList.add( 'is-open' );
            mobileNav.removeAttribute( 'inert' );
            backdrop && backdrop.classList.add( 'is-visible' );
            toggleBtn.setAttribute( 'aria-expanded', 'true' );
            document.body.style.overflow = 'hidden';

            // Move focus to close button
            if ( closeBtn ) closeBtn.focus();
        }

        function closeMenu() {
            mobileNav.classList.remove( 'is-open' );
            mobileNav.setAttribute( 'inert', '' );
            backdrop && backdrop.classList.remove( 'is-visible' );
            toggleBtn.setAttribute( 'aria-expanded', 'false' );
            document.body.style.overflow = '';

            // Return focus to the toggle
            toggleBtn.focus();
        }

        toggleBtn.addEventListener( 'click', openMenu );
        closeBtn && closeBtn.addEventListener( 'click', closeMenu );
        backdrop && backdrop.addEventListener( 'click', closeMenu );

        // Close on Escape key
        document.addEventListener( 'keydown', function ( event ) {
            if ( event.key === 'Escape' && mobileNav.classList.contains( 'is-open' ) ) {
                closeMenu();
            }
        } );

        // Close menu when a link is followed (navigation happened)
        mobileNav.querySelectorAll( 'a' ).forEach( function ( link ) {
            link.addEventListener( 'click', closeMenu );
        } );
    }

    // =========================================================================
    // Dark Mode Toggle Button Wiring
    // =========================================================================

    function initDarkModeToggle() {
        const btn = document.querySelector( '.dark-mode-toggle' );
        if ( ! btn ) return;

        btn.addEventListener( 'click', toggleTheme );

        // Keep label in sync with system preference changes
        const mediaQuery = window.matchMedia( '(prefers-color-scheme: dark)' );
        mediaQuery.addEventListener( 'change', function () {
            if ( ! getStoredTheme() ) {
                updateToggleButtonLabel( null );
            }
        } );
    }

    // =========================================================================
    // Skip to Content
    // =========================================================================

    function initSkipLink() {
        const skipLink = document.querySelector( '.skip-link' );
        if ( ! skipLink ) return;

        skipLink.addEventListener( 'click', function ( event ) {
            const targetId = skipLink.getAttribute( 'href' );
            if ( ! targetId || ! targetId.startsWith( '#' ) ) return;

            const target = document.querySelector( targetId );
            if ( ! target ) return;

            event.preventDefault();

            // Ensure the element is focusable
            if ( ! target.hasAttribute( 'tabindex' ) ) {
                target.setAttribute( 'tabindex', '-1' );
            }

            target.focus( { preventScroll: false } );
        } );
    }

    // =========================================================================
    // Boot
    // =========================================================================

    document.addEventListener( 'DOMContentLoaded', function () {
        initDarkModeToggle();
        initMobileMenu();
        initSkipLink();

        // Set initial button label now that DOM is ready
        updateToggleButtonLabel( getStoredTheme() );
    } );

} )();
