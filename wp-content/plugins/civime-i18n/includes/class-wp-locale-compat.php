<?php
/**
 * Custom $wp_locale data for languages without WordPress core packs.
 *
 * For 7 languages (Hawaiian, Ilokano, Chuukese, Marshallese, Samoan, Tongan,
 * Cebuano) WordPress doesn't ship month/weekday names. This class populates
 * $wp_locale->month and $wp_locale->weekday so wp_date() outputs correct names.
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_I18n_WP_Locale_Compat {

	/**
	 * Apply custom month/weekday names to the global $wp_locale.
	 *
	 * @param string $slug The language slug (e.g. 'haw').
	 */
	public static function apply( string $slug ): void {
		global $wp_locale;

		if ( ! $wp_locale ) {
			return;
		}

		$data = self::get_data( $slug );
		if ( ! $data ) {
			return;
		}

		// Months (1-indexed keys as two-digit strings: '01'..'12').
		if ( ! empty( $data['months'] ) ) {
			foreach ( $data['months'] as $i => $name ) {
				$key = str_pad( (string) $i, 2, '0', STR_PAD_LEFT );
				$wp_locale->month[ $key ] = $name;
			}
		}

		// Abbreviated months.
		if ( ! empty( $data['months_abbrev'] ) ) {
			foreach ( $data['months_abbrev'] as $i => $name ) {
				$key      = str_pad( (string) $i, 2, '0', STR_PAD_LEFT );
				$full_key = $data['months'][ $i ] ?? '';
				$wp_locale->month_abbrev[ $full_key ] = $name;
			}
		}

		// Weekdays (0 = Sunday .. 6 = Saturday).
		if ( ! empty( $data['weekdays'] ) ) {
			foreach ( $data['weekdays'] as $i => $name ) {
				$wp_locale->weekday[ $i ] = $name;
			}
		}

		// Abbreviated weekdays.
		if ( ! empty( $data['weekdays_abbrev'] ) ) {
			foreach ( $data['weekdays_abbrev'] as $i => $name ) {
				$full_key = $data['weekdays'][ $i ] ?? '';
				$wp_locale->weekday_abbrev[ $full_key ] = $name;
			}
		}

		// Meridiem.
		if ( ! empty( $data['meridiem'] ) ) {
			$wp_locale->meridiem['am'] = $data['meridiem']['am'] ?? 'am';
			$wp_locale->meridiem['pm'] = $data['meridiem']['pm'] ?? 'pm';
			$wp_locale->meridiem['AM'] = strtoupper( $data['meridiem']['am'] ?? 'AM' );
			$wp_locale->meridiem['PM'] = strtoupper( $data['meridiem']['pm'] ?? 'PM' );
		}
	}

	/**
	 * Get locale data for a given slug.
	 *
	 * Returns null for unknown slugs. Month/weekday data will be populated
	 * incrementally as community translators provide accurate translations.
	 *
	 * @param string $slug Language slug.
	 * @return array|null
	 */
	private static function get_data( string $slug ): ?array {
		$all = [

			// ʻŌlelo Hawaiʻi
			'haw' => [
				'months' => [
					1  => 'Ianuali',
					2  => 'Pepeluali',
					3  => 'Malaki',
					4  => 'ʻApelila',
					5  => 'Mei',
					6  => 'Iune',
					7  => 'Iulai',
					8  => 'ʻAukake',
					9  => 'Kepakemapa',
					10 => 'ʻOkakopa',
					11 => 'Nowemapa',
					12 => 'Kekemapa',
				],
				'months_abbrev' => [
					1  => 'Ian',
					2  => 'Pep',
					3  => 'Mal',
					4  => 'ʻApe',
					5  => 'Mei',
					6  => 'Iun',
					7  => 'Iul',
					8  => 'ʻAuk',
					9  => 'Kep',
					10 => 'ʻOka',
					11 => 'Now',
					12 => 'Kek',
				],
				'weekdays' => [
					0 => 'Lāpule',
					1 => 'Pōʻakahi',
					2 => 'Pōʻalua',
					3 => 'Pōʻakolu',
					4 => 'Pōʻahā',
					5 => 'Pōʻalima',
					6 => 'Pōʻaono',
				],
				'weekdays_abbrev' => [
					0 => 'LP',
					1 => 'P1',
					2 => 'P2',
					3 => 'P3',
					4 => 'P4',
					5 => 'P5',
					6 => 'P6',
				],
				'meridiem' => [ 'am' => 'am', 'pm' => 'pm' ],
			],

			// Ilokano
			'ilo' => [
				'months' => [
					1  => 'Enero',
					2  => 'Pebrero',
					3  => 'Marso',
					4  => 'Abril',
					5  => 'Mayo',
					6  => 'Hunio',
					7  => 'Hulio',
					8  => 'Agosto',
					9  => 'Septiembre',
					10 => 'Oktubre',
					11 => 'Nobiembre',
					12 => 'Disiembre',
				],
				'months_abbrev' => [
					1  => 'Ene',
					2  => 'Peb',
					3  => 'Mar',
					4  => 'Abr',
					5  => 'May',
					6  => 'Hun',
					7  => 'Hul',
					8  => 'Ago',
					9  => 'Sep',
					10 => 'Okt',
					11 => 'Nob',
					12 => 'Dis',
				],
				'weekdays' => [
					0 => 'Domingo',
					1 => 'Lunes',
					2 => 'Martes',
					3 => 'Mierkoles',
					4 => 'Hueves',
					5 => 'Biernes',
					6 => 'Sabado',
				],
				'weekdays_abbrev' => [
					0 => 'Dom',
					1 => 'Lun',
					2 => 'Mar',
					3 => 'Mie',
					4 => 'Hue',
					5 => 'Bie',
					6 => 'Sab',
				],
				'meridiem' => [ 'am' => 'am', 'pm' => 'pm' ],
			],

			// Chuukese
			'chk' => [
				'months' => [
					1  => 'Sanuari',
					2  => 'Feburuari',
					3  => 'Mach',
					4  => 'Apriw',
					5  => 'Mei',
					6  => 'Sune',
					7  => 'Surei',
					8  => 'Okos',
					9  => 'Septemper',
					10 => 'Oktopar',
					11 => 'Nopemper',
					12 => 'Tesemper',
				],
				'months_abbrev' => [
					1  => 'San',
					2  => 'Feb',
					3  => 'Mac',
					4  => 'Apr',
					5  => 'Mei',
					6  => 'Sun',
					7  => 'Sur',
					8  => 'Oko',
					9  => 'Sep',
					10 => 'Okt',
					11 => 'Nop',
					12 => 'Tes',
				],
				'weekdays' => [
					0 => 'Sarapau',
					1 => 'Monen',
					2 => 'Tiusrei',
					3 => 'Wenesrei',
					4 => 'Turesrei',
					5 => 'Farairei',
					6 => 'Sarerei',
				],
				'weekdays_abbrev' => [
					0 => 'Sar',
					1 => 'Mon',
					2 => 'Tiu',
					3 => 'Wen',
					4 => 'Tur',
					5 => 'Far',
					6 => 'Sar',
				],
				'meridiem' => [ 'am' => 'am', 'pm' => 'pm' ],
			],

			// Marshallese
			'mah' => [
				'months' => [
					1  => 'Jānwōde',
					2  => 'Pāpode',
					3  => 'Mājeḷ',
					4  => 'Eprōḷ',
					5  => 'Māe',
					6  => 'Jūn',
					7  => 'Juḷae',
					8  => 'Ọ̄kwōj',
					9  => 'Jeptōba',
					10 => 'Ạktōba',
					11 => 'Nōpōba',
					12 => 'Tijōba',
				],
				'months_abbrev' => [
					1  => 'Jān',
					2  => 'Pāp',
					3  => 'Māj',
					4  => 'Epr',
					5  => 'Māe',
					6  => 'Jūn',
					7  => 'Jul',
					8  => 'Ọ̄kw',
					9  => 'Jep',
					10 => 'Ạkt',
					11 => 'Nōp',
					12 => 'Tij',
				],
				'weekdays' => [
					0 => 'Jabōt',
					1 => 'Mande',
					2 => 'Tūjde',
					3 => 'Wenejde',
					4 => 'Tōjde',
					5 => 'Pāide',
					6 => 'Jāde',
				],
				'weekdays_abbrev' => [
					0 => 'Jab',
					1 => 'Man',
					2 => 'Tūj',
					3 => 'Wen',
					4 => 'Tōj',
					5 => 'Pāi',
					6 => 'Jād',
				],
				'meridiem' => [ 'am' => 'am', 'pm' => 'pm' ],
			],

			// Gagana Sāmoa
			'sm' => [
				'months' => [
					1  => 'Ianuari',
					2  => 'Fepuari',
					3  => 'Mati',
					4  => 'Aperila',
					5  => 'Me',
					6  => 'Iuni',
					7  => 'Iulai',
					8  => 'Aukuso',
					9  => 'Setema',
					10 => 'Oketopa',
					11 => 'Novema',
					12 => 'Tesema',
				],
				'months_abbrev' => [
					1  => 'Ian',
					2  => 'Fep',
					3  => 'Mat',
					4  => 'Ape',
					5  => 'Me',
					6  => 'Iun',
					7  => 'Iul',
					8  => 'Auk',
					9  => 'Set',
					10 => 'Oke',
					11 => 'Nov',
					12 => 'Tes',
				],
				'weekdays' => [
					0 => 'Aso Sā',
					1 => 'Aso Gafua',
					2 => 'Aso Lua',
					3 => 'Aso Lulu',
					4 => 'Aso Tofi',
					5 => 'Aso Faraile',
					6 => 'Aso Toʻanaʻi',
				],
				'weekdays_abbrev' => [
					0 => 'Sā',
					1 => 'Gaf',
					2 => 'Lua',
					3 => 'Lul',
					4 => 'Tof',
					5 => 'Far',
					6 => 'Toʻa',
				],
				'meridiem' => [ 'am' => 'am', 'pm' => 'pm' ],
			],

			// Lea Fakatonga
			'to' => [
				'months' => [
					1  => 'Sānuali',
					2  => 'Fēpueli',
					3  => 'Maʻasi',
					4  => 'ʻĒpeleli',
					5  => 'Mē',
					6  => 'Sune',
					7  => 'Siulai',
					8  => 'ʻAokosi',
					9  => 'Sepitema',
					10 => 'ʻOkatopa',
					11 => 'Nōvema',
					12 => 'Tīsema',
				],
				'months_abbrev' => [
					1  => 'Sān',
					2  => 'Fēp',
					3  => 'Maʻa',
					4  => 'ʻĒpe',
					5  => 'Mē',
					6  => 'Sun',
					7  => 'Siu',
					8  => 'ʻAok',
					9  => 'Sep',
					10 => 'ʻOka',
					11 => 'Nōv',
					12 => 'Tīs',
				],
				'weekdays' => [
					0 => 'Sāpate',
					1 => 'Mōnite',
					2 => 'Tūsite',
					3 => 'Pulelulu',
					4 => 'Tuʻapulelulu',
					5 => 'Falaite',
					6 => 'Tokonaki',
				],
				'weekdays_abbrev' => [
					0 => 'Sāp',
					1 => 'Mōn',
					2 => 'Tūs',
					3 => 'Pul',
					4 => 'Tuʻa',
					5 => 'Fal',
					6 => 'Tok',
				],
				'meridiem' => [ 'am' => 'am', 'pm' => 'pm' ],
			],

			// Cebuano
			'ceb' => [
				'months' => [
					1  => 'Enero',
					2  => 'Pebrero',
					3  => 'Marso',
					4  => 'Abril',
					5  => 'Mayo',
					6  => 'Hunyo',
					7  => 'Hulyo',
					8  => 'Agosto',
					9  => 'Septyembre',
					10 => 'Oktubre',
					11 => 'Nobyembre',
					12 => 'Disyembre',
				],
				'months_abbrev' => [
					1  => 'Ene',
					2  => 'Peb',
					3  => 'Mar',
					4  => 'Abr',
					5  => 'May',
					6  => 'Hun',
					7  => 'Hul',
					8  => 'Ago',
					9  => 'Sep',
					10 => 'Okt',
					11 => 'Nob',
					12 => 'Dis',
				],
				'weekdays' => [
					0 => 'Dominggo',
					1 => 'Lunes',
					2 => 'Martes',
					3 => 'Miyerkules',
					4 => 'Huwebes',
					5 => 'Biyernes',
					6 => 'Sabado',
				],
				'weekdays_abbrev' => [
					0 => 'Dom',
					1 => 'Lun',
					2 => 'Mar',
					3 => 'Miy',
					4 => 'Huw',
					5 => 'Biy',
					6 => 'Sab',
				],
				'meridiem' => [ 'am' => 'am', 'pm' => 'pm' ],
			],

		];

		return $all[ $slug ] ?? null;
	}
}
