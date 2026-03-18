<?php
/**
 * Hreflang SEO tags.
 *
 * Outputs <link rel="alternate" hreflang=""> for all available locales.
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_I18n_Hreflang {

	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_tags' ], 1 );
	}

	/**
	 * Output hreflang link elements.
	 */
	public function output_tags(): void {
		// Suppress hreflang on noindex pages — contradictory signals for search engines.
		if ( function_exists( 'civime_is_noindex_page' ) && civime_is_noindex_page() ) {
			return;
		}

		$available = CiviMe_I18n_Locale::get_available_slugs();

		if ( count( $available ) < 2 ) {
			return;
		}

		// Get the current URL without any lang parameter.
		$current_url = home_url( add_query_arg( [] ) );
		$base_url    = remove_query_arg( 'lang', $current_url );

		// x-default and en both point to the base URL (no ?lang=).
		echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $base_url ) . '" />' . "\n";
		echo '<link rel="alternate" hreflang="en" href="' . esc_url( $base_url ) . '" />' . "\n";

		foreach ( $available as $slug ) {
			if ( 'en' === $slug ) {
				continue;
			}

			$lang_url = add_query_arg( 'lang', $slug, $base_url );
			echo '<link rel="alternate" hreflang="' . esc_attr( $slug ) . '" href="' . esc_url( $lang_url ) . '" />' . "\n";
		}
	}
}
