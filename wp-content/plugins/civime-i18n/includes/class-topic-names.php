<?php
/**
 * Translatable topic name lookup.
 *
 * The 16 topic names are a fixed set from the API. This class wraps them in
 * translation functions so they appear in .pot files and can be translated.
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_I18n_Topic_Names {

	/**
	 * Get the translated name for a topic slug.
	 *
	 * Falls back to the raw slug if not found.
	 *
	 * @param string $slug Topic slug from the API (e.g. 'environment').
	 * @return string Translated topic name.
	 */
	public static function get( string $slug ): string {
		$names = self::all();
		return $names[ $slug ] ?? $slug;
	}

	/**
	 * Get all translatable topic names keyed by slug.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		return [
			'environment'    => __( 'Environment & Land', 'civime-meetings' ),
			'housing'        => __( 'Housing & Development', 'civime-meetings' ),
			'education'      => __( 'Education', 'civime-meetings' ),
			'health'         => __( 'Health', 'civime-meetings' ),
			'transportation' => __( 'Transportation', 'civime-meetings' ),
			'public-safety'  => __( 'Public Safety', 'civime-meetings' ),
			'economy'        => __( 'Economy & Jobs', 'civime-meetings' ),
			'culture'        => __( 'Culture & Arts', 'civime-meetings' ),
			'agriculture'    => __( 'Agriculture', 'civime-meetings' ),
			'energy'         => __( 'Energy', 'civime-meetings' ),
			'water'          => __( 'Water & Ocean', 'civime-meetings' ),
			'disability'     => __( 'Disability & Access', 'civime-meetings' ),
			'veterans'       => __( 'Veterans', 'civime-meetings' ),
			'technology'     => __( 'Technology', 'civime-meetings' ),
			'budget'         => __( 'Budget & Finance', 'civime-meetings' ),
			'governance'     => __( 'Governance', 'civime-meetings' ),
		];
	}
}
