<?php
/**
 * POT/PO file parser for translation statistics.
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_I18n_Pot_Parser {

	/**
	 * Count translatable strings in a .pot file.
	 *
	 * Counts non-empty msgid entries (excludes the header empty msgid).
	 *
	 * @param string $pot_path Absolute path to .pot file.
	 * @return int
	 */
	public static function count_strings( string $pot_path ): int {
		if ( ! file_exists( $pot_path ) ) {
			return 0;
		}

		$contents = file_get_contents( $pot_path );
		if ( false === $contents ) {
			return 0;
		}

		// Match msgid "..." where the string is non-empty.
		preg_match_all( '/^msgid\s+"(.+)"/m', $contents, $matches );

		return count( $matches[1] );
	}

	/**
	 * Count translated strings in a .po file.
	 *
	 * Counts msgstr entries that are non-empty and follow a non-empty msgid.
	 *
	 * @param string $po_path Absolute path to .po file.
	 * @return int
	 */
	public static function count_translated( string $po_path ): int {
		if ( ! file_exists( $po_path ) ) {
			return 0;
		}

		$contents = file_get_contents( $po_path );
		if ( false === $contents ) {
			return 0;
		}

		// Split into blocks separated by blank lines.
		$blocks     = preg_split( '/\n{2,}/', $contents );
		$translated = 0;

		foreach ( $blocks as $block ) {
			// Skip the header block (empty msgid).
			if ( preg_match( '/^msgid\s+""\s*$/m', $block ) && ! preg_match( '/^msgid\s+".+"/m', $block ) ) {
				continue;
			}

			// Check this block has a non-empty msgid and a non-empty msgstr.
			if ( preg_match( '/^msgid\s+".+"/m', $block ) && preg_match( '/^msgstr\s+"(.+)"/m', $block ) ) {
				$translated++;
			}
		}

		return $translated;
	}

	/**
	 * Get full translation statistics across all domains and languages.
	 *
	 * @return array{
	 *     domains: array<string, array{pot_path: string, string_count: int}>,
	 *     languages: array<string, array{
	 *         native_name: string,
	 *         wp_locale: string,
	 *         has_wp_pack: bool,
	 *         domains: array<string, array{translated: int, total: int, percent: float}>,
	 *         overall_percent: float,
	 *         status: string
	 *     }>,
	 *     total_strings: int,
	 *     active_count: int,
	 *     translating_count: int
	 * }
	 */
	public static function get_translation_stats(): array {
		$locales      = CiviMe_I18n_Locale::get_locales();
		$lang_base    = CIVIME_I18N_PATH . 'languages/';

		// The 5 text domains and their .pot file locations.
		$domain_map = [
			'civime'               => $lang_base . 'civime/civime.pot',
			'civime-core'          => $lang_base . 'civime-core/civime-core.pot',
			'civime-meetings'      => $lang_base . 'civime-meetings/civime-meetings.pot',
			'civime-notifications' => $lang_base . 'civime-notifications/civime-notifications.pot',
			'civime-guides'        => $lang_base . 'civime-guides/civime-guides.pot',
		];

		// Count strings per domain.
		$domains       = [];
		$total_strings = 0;
		foreach ( $domain_map as $domain => $pot_path ) {
			$count           = self::count_strings( $pot_path );
			$domains[ $domain ] = [
				'pot_path'     => $pot_path,
				'string_count' => $count,
			];
			$total_strings += $count;
		}

		// Per-language stats.
		$languages        = [];
		$active_count     = 0;
		$translating_count = 0;

		foreach ( $locales as $slug => $info ) {
			if ( 'en' === $slug ) {
				continue;
			}

			$native_name = $info[0];
			$wp_locale   = $info[1];
			$has_wp_pack = $info[2];

			$lang_domains     = [];
			$lang_translated  = 0;
			$lang_total       = 0;

			foreach ( $domain_map as $domain => $pot_path ) {
				$domain_dir = dirname( $pot_path );
				$po_path    = $domain_dir . '/' . $domain . '-' . $wp_locale . '.po';
				$translated = self::count_translated( $po_path );
				$domain_total = $domains[ $domain ]['string_count'];

				$lang_domains[ $domain ] = [
					'translated' => $translated,
					'total'      => $domain_total,
					'percent'    => $domain_total > 0 ? round( ( $translated / $domain_total ) * 100, 1 ) : 0.0,
				];

				$lang_translated += $translated;
				$lang_total      += $domain_total;
			}

			$overall_percent = $lang_total > 0 ? round( ( $lang_translated / $lang_total ) * 100, 1 ) : 0.0;

			if ( $overall_percent >= 100.0 ) {
				$status = 'complete';
			} elseif ( $overall_percent > 0 ) {
				$status = 'partial';
			} else {
				$status = 'not_started';
			}

			if ( $overall_percent > 0 ) {
				$translating_count++;
			}

			// Check if any .mo file exists for this language.
			$has_mo = false;
			foreach ( $domain_map as $domain => $pot_path ) {
				$mo_path = dirname( $pot_path ) . '/' . $domain . '-' . $wp_locale . '.mo';
				if ( file_exists( $mo_path ) ) {
					$has_mo = true;
					break;
				}
			}
			if ( $has_mo ) {
				$active_count++;
			}

			$languages[ $slug ] = [
				'native_name'     => $native_name,
				'wp_locale'       => $wp_locale,
				'has_wp_pack'     => $has_wp_pack,
				'domains'         => $lang_domains,
				'overall_percent' => $overall_percent,
				'status'          => $status,
			];
		}

		return [
			'domains'           => $domains,
			'languages'         => $languages,
			'total_strings'     => $total_strings,
			'active_count'      => $active_count,
			'translating_count' => $translating_count,
		];
	}
}
