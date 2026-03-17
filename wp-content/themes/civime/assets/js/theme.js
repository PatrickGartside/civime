/**
 * CiviMe Theme — Main JavaScript
 *
 * Handles:
 * - Mobile menu toggle
 * - Skip to content smooth scroll
 */

( function () {
    'use strict';

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
        initMobileMenu();
        initSkipLink();
    } );

} )();
