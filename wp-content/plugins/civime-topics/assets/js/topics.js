/**
 * CiviMe Topics — Client-side topic selection and persistence.
 *
 * Manages topic selections in localStorage for cross-page topic awareness.
 * Works on:
 *   - Topic picker page: toggle topic selections, save to localStorage
 *   - Meetings page: read localStorage, inject ?topics= param into API calls
 *   - Any page with "Your Topics" bar: display active topics
 *
 * @package CiviMe_Topics
 */

( function () {
	'use strict';

	var STORAGE_KEY = 'civime_topics';

	// ─── Storage helpers ──────────────────────────────────────────

	function getSelectedTopics() {
		try {
			var stored = localStorage.getItem( STORAGE_KEY );
			if ( stored ) {
				var parsed = JSON.parse( stored );
				if ( Array.isArray( parsed ) ) {
					return parsed;
				}
			}
		} catch ( e ) {
			// Ignore parse errors
		}
		return [];
	}

	function saveSelectedTopics( slugs ) {
		try {
			localStorage.setItem( STORAGE_KEY, JSON.stringify( slugs ) );
		} catch ( e ) {
			// localStorage might be full or disabled
		}
	}

	// ─── Topic Picker page ────────────────────────────────────────

	function initTopicPicker() {
		var cards = document.querySelectorAll( '.topic-card[data-topic-slug]' );
		if ( ! cards.length ) {
			return;
		}

		var selected = getSelectedTopics();
		var countEl  = document.getElementById( 'topic-count' );
		var clearBtn = document.getElementById( 'topic-clear-btn' );
		var doneBtn  = document.getElementById( 'topic-done-btn' );

		// Restore selections
		cards.forEach( function ( card ) {
			var slug = card.getAttribute( 'data-topic-slug' );
			if ( selected.indexOf( slug ) !== -1 ) {
				card.setAttribute( 'aria-pressed', 'true' );
			}
		} );

		updateCount();

		// Toggle handler
		cards.forEach( function ( card ) {
			card.addEventListener( 'click', function () {
				var slug      = card.getAttribute( 'data-topic-slug' );
				var isPressed = card.getAttribute( 'aria-pressed' ) === 'true';

				if ( isPressed ) {
					card.setAttribute( 'aria-pressed', 'false' );
					selected = selected.filter( function ( s ) { return s !== slug; } );
				} else {
					card.setAttribute( 'aria-pressed', 'true' );
					selected.push( slug );
				}

				saveSelectedTopics( selected );
				updateCount();
			} );

			// Keyboard: Enter and Space
			card.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					card.click();
				}
			} );
		} );

		// Clear all
		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', function () {
				selected = [];
				saveSelectedTopics( selected );
				cards.forEach( function ( card ) {
					card.setAttribute( 'aria-pressed', 'false' );
				} );
				updateCount();
			} );
		}

		// "Show My Meetings" — append topics to URL
		if ( doneBtn ) {
			doneBtn.addEventListener( 'click', function ( e ) {
				if ( selected.length > 0 ) {
					e.preventDefault();
					var baseUrl = doneBtn.getAttribute( 'href' );
					var sep     = baseUrl.indexOf( '?' ) !== -1 ? '&' : '?';
					window.location.href = baseUrl + sep + 'topics=' + encodeURIComponent( selected.join( ',' ) );
				}
			} );
		}

		function updateCount() {
			if ( countEl ) {
				countEl.textContent = String( selected.length );
			}
		}
	}

	// ─── "Your Topics" bar ────────────────────────────────────────

	function initYourTopicsBar() {
		var bar  = document.getElementById( 'your-topics-bar' );
		var list = document.getElementById( 'your-topics-list' );

		if ( ! bar || ! list ) {
			return;
		}

		var selected = getSelectedTopics();

		if ( selected.length === 0 ) {
			bar.hidden = true;
			return;
		}

		bar.hidden = false;

		// Build tag elements
		list.innerHTML = '';
		selected.forEach( function ( slug ) {
			var tag       = document.createElement( 'span' );
			tag.className = 'your-topics-bar__tag';
			// Convert slug to display name: "public-safety" → "Public Safety"
			tag.textContent = slug.replace( /-/g, ' ' ).replace( /\b\w/g, function ( c ) {
				return c.toUpperCase();
			} );
			list.appendChild( tag );
		} );
	}

	// ─── Initialize ──────────────────────────────────────────────

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	function init() {
		initTopicPicker();
		initYourTopicsBar();
	}

} )();
