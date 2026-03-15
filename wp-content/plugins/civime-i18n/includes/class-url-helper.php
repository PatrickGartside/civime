<?php
/**
 * Language-aware URL helper.
 *
 * Provides civime_i18n_url() for constructing URLs that preserve the active locale.
 * If we ever switch from ?lang= to subdirectory routing, only this file changes.
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_I18n_URL_Helper {

	/**
	 * Build a language-aware URL.
	 *
	 * For English (default), returns the path as-is.
	 * For other locales, appends ?lang={slug}.
	 *
	 * @param string $path Relative path, e.g. '/meetings/'.
	 * @param string $lang Override locale slug. Empty = use active locale.
	 * @return string Full URL with language parameter if needed.
	 */
	public static function url( string $path, string $lang = '' ): string {
		if ( '' === $lang ) {
			$lang = apply_filters( 'civime_i18n_active_slug', 'en' );
		}

		$base = home_url( $path );

		if ( 'en' === $lang ) {
			return $base;
		}

		return add_query_arg( 'lang', $lang, $base );
	}
}

/**
 * Global helper function for templates.
 *
 * @param string $path Relative path, e.g. '/meetings/'.
 * @param string $lang Override locale slug. Empty = use active locale.
 * @return string
 */
function civime_i18n_url( string $path, string $lang = '' ): string {
	return CiviMe_I18n_URL_Helper::url( $path, $lang );
}
