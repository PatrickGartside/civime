<?php
/**
 * Locale registry and switching orchestration.
 *
 * Detects ?lang= param or civime_lang cookie, calls switch_to_locale(),
 * loads text domains centrally, and persists choice via cookie.
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_I18n_Locale {

	/**
	 * Full registry of the 15 OLA languages plus English.
	 *
	 * Each entry: slug => [ native_name, wp_locale, has_wp_pack ].
	 *
	 * @var array<string, array{string, string, bool}>
	 */
	private const LOCALES = [
		'en'      => [ 'English',    'en_US',  true  ],
		'haw'     => [ 'ʻŌlelo Hawaiʻi', 'haw', false ],
		'tl'      => [ 'Tagalog',    'tl',     true  ],
		'ja'      => [ '日本語',      'ja',     true  ],
		'ilo'     => [ 'Ilokano',    'ilo',    false ],
		'zh-hans' => [ '简体中文',    'zh_CN',  true  ],
		'zh-hant' => [ '繁體中文',    'zh_TW',  true  ],
		'ko'      => [ '한국어',      'ko_KR',  true  ],
		'es'      => [ 'Español',    'es_ES',  true  ],
		'vi'      => [ 'Tiếng Việt', 'vi',     true  ],
		'sm'      => [ 'Gagana Sāmoa', 'sm',   false ],
		'to'      => [ 'Lea Fakatonga', 'to',   false ],
		'mah'     => [ 'Marshallese', 'mah',   false ],
		'chk'     => [ 'Chuukese',   'chk',    false ],
		'th'      => [ 'ไทย',        'th',     true  ],
		'ceb'     => [ 'Cebuano',    'ceb',    false ],
	];

	private const COOKIE_NAME = 'civime_lang';
	private const COOKIE_DAYS = 7;

	/** @var string The active locale slug (e.g. 'es'). */
	private string $active_slug = 'en';

	/** @var bool Whether switch_to_locale() succeeded natively. */
	private bool $locale_switched = false;

	/**
	 * Text domains to load centrally.
	 *
	 * @var array<string, string> domain => relative path from wp-content/plugins/
	 */
	private const TEXT_DOMAINS = [
		'civime'               => 'civime-i18n/languages/civime',
		'civime-meetings'      => 'civime-i18n/languages/civime-meetings',
		'civime-notifications' => 'civime-i18n/languages/civime-notifications',
		'civime-core'          => 'civime-i18n/languages/civime-core',
		'civime-guides'        => 'civime-i18n/languages/civime-guides',
	];

	/**
	 * Initialize locale detection, switching, and text domain loading.
	 */
	public function init(): void {
		$this->active_slug = $this->detect_slug();

		if ( 'en' !== $this->active_slug ) {
			$wp_locale = self::get_wp_locale( $this->active_slug );

			if ( $wp_locale ) {
				$this->locale_switched = switch_to_locale( $wp_locale );

				// For non-standard locale codes (haw, ilo, chk, etc.),
				// switch_to_locale() returns false. Override get_locale() manually.
				if ( ! $this->locale_switched ) {
					add_filter( 'locale', function () use ( $wp_locale ): string {
						return $wp_locale;
					} );
				}

				// Apply custom month/weekday names for languages without WP packs.
				if ( ! self::has_wp_pack( $this->active_slug ) ) {
					CiviMe_I18n_WP_Locale_Compat::apply( $this->active_slug );
				}
			}
		}

		$this->set_cookie();
		$this->load_text_domains();

		// Translate nav menu items, site tagline, and taxonomy terms for non-English locales.
		// Also add home_url filter for URL-based language persistence.
		if ( 'en' !== $this->active_slug ) {
			add_filter( 'wp_nav_menu_objects', [ $this, 'translate_menu_items' ] );
			add_filter( 'bloginfo', [ $this, 'translate_tagline' ], 10, 2 );
			add_filter( 'get_term', [ $this, 'translate_term' ] );
			add_filter( 'home_url', [ $this, 'localize_home_url' ], 10, 2 );
		}

		// Expose the active slug to other classes/templates.
		add_filter( 'civime_i18n_active_slug', [ $this, 'get_active_slug' ] );

		// Restore locale at the very end of the request.
		add_action( 'shutdown', function (): void {
			if ( $this->locale_switched ) {
				restore_previous_locale();
			}
		} );
	}

	/**
	 * Get the currently active locale slug.
	 */
	public function get_active_slug(): string {
		return $this->active_slug;
	}

	/**
	 * Append ?lang= to frontend home_url() calls.
	 *
	 * Skips admin, REST, cron, and XMLRPC contexts.
	 * Skips URLs that already contain a lang= parameter.
	 *
	 * @param string $url  The complete home URL.
	 * @param string $path The requested path relative to home.
	 * @return string
	 */
	public function localize_home_url( string $url, string $path ): string {
		// Never modify URLs in admin, REST, cron, or XMLRPC contexts.
		if ( is_admin() ) {
			return $url;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $url;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return $url;
		}
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return $url;
		}

		// Skip REST API, feed, and xmlrpc URLs rendered in frontend HTML.
		if ( str_contains( $url, '/wp-json' ) || str_contains( $url, '/feed' ) || str_contains( $url, 'xmlrpc.php' ) ) {
			return $url;
		}

		// Skip if URL already has lang= parameter.
		if ( str_contains( $url, 'lang=' ) ) {
			return $url;
		}

		return add_query_arg( 'lang', $this->active_slug, $url );
	}

	/**
	 * Translate nav menu item titles for the active locale.
	 *
	 * @param WP_Post[] $items Menu item objects.
	 * @return WP_Post[]
	 */
	public function translate_menu_items( array $items ): array {
		$translations = self::get_menu_translations( $this->active_slug );

		foreach ( $items as $item ) {
			// Translate title.
			if ( ! empty( $translations ) && isset( $translations[ $item->title ] ) ) {
				$item->title = $translations[ $item->title ];
			}

			// Append ?lang= to menu item URL if not already present.
			if ( ! str_contains( $item->url, 'lang=' ) ) {
				$item->url = add_query_arg( 'lang', $this->active_slug, $item->url );
			}
		}

		return $items;
	}

	/**
	 * Translate the site tagline for the active locale.
	 *
	 * @param string $output The requested bloginfo value.
	 * @param string $show   The type of info requested (e.g. 'description').
	 * @return string
	 */
	public function translate_tagline( string $output, string $show ): string {
		if ( 'description' !== $show ) {
			return $output;
		}

		$taglines = [
			'haw'     => 'Mea Hana Kālaiʻāina no ka Poʻe o Hawaiʻi',
			'tl'      => 'Mga Kasangkapan sa Pakikilahok Sibiko para sa mga Tao ng Hawaii',
			'ja'      => 'ハワイの人々のための市民参加ツール',
			'ilo'     => 'Dagiti Ramit ti Pannakipaset Sibiko para iti Umili ti Hawaii',
			'zh-hans' => 'Hawaii 居民公民参与工具',
			'zh-hant' => 'Hawaii 居民公民參與工具',
			'ko'      => 'Hawaii 주민을 위한 시민 참여 도구',
			'es'      => 'Herramientas de Participación Cívica para la Gente de Hawaii',
			'vi'      => 'Công cụ Tham gia Dân sự cho Người dân Hawaii',
			'sm'      => 'Meafaigaluega mo le Auai Faʻaleatunuʻu mo Tagata o Hawaii',
			'to'      => 'Ngaahi Meʻangāue Fakakolo ki he Kakai ʻo Hawaii',
			'mah'     => 'Jikōj ko an Drelōñ in Aelōñ kein Hawaii',
			'chk'     => 'Ekkewe Mettoch en Aea non Aramas in Hawaii',
			'th'      => 'เครื่องมือการมีส่วนร่วมของพลเมืองสำหรับชาว Hawaii',
			'ceb'     => 'Mga Himan sa Sibikong Pag-apil para sa mga Tawo sa Hawaii',
		];

		return $taglines[ $this->active_slug ] ?? $output;
	}

	/**
	 * Translate taxonomy term names for the active locale.
	 *
	 * @param WP_Term $term Term object.
	 * @return WP_Term
	 */
	public function translate_term( $term ) {
		if ( ! ( $term instanceof \WP_Term ) || 'guide_category' !== $term->taxonomy ) {
			return $term;
		}

		$translations = self::get_term_translations( $this->active_slug );

		if ( isset( $translations[ $term->name ] ) ) {
			$term->name = $translations[ $term->name ];
		}

		return $term;
	}

	/**
	 * Guide category translations keyed by locale.
	 *
	 * @param string $slug Locale slug.
	 * @return array<string, string> English name => translated name.
	 */
	private static function get_term_translations( string $slug ): array {
		$map = [
			'haw' => [
				'Testimony'          => 'Hōʻike',
				'Advocacy'           => 'Kākoʻo',
				'Getting Started'    => 'Hoʻomaka',
				'Voting &amp; Elections' => 'Koho Balota',
				'Compliance'         => 'Hoʻokō Kānāwai',
			],
			'tl' => [
				'Testimony'          => 'Testimonya',
				'Advocacy'           => 'Adbokasiya',
				'Getting Started'    => 'Pagsisimula',
				'Voting &amp; Elections' => 'Pagboto at Halalan',
				'Compliance'         => 'Pagsunod',
			],
			'ja' => [
				'Testimony'          => '証言',
				'Advocacy'           => 'アドボカシー',
				'Getting Started'    => 'はじめに',
				'Voting &amp; Elections' => '投票と選挙',
				'Compliance'         => 'コンプライアンス',
			],
			'ilo' => [
				'Testimony'          => 'Testimonio',
				'Advocacy'           => 'Panangidepensa',
				'Getting Started'    => 'Panagrugi',
				'Voting &amp; Elections' => 'Panagbotos ken Eleksion',
				'Compliance'         => 'Panagtungpal',
			],
			'zh-hans' => [
				'Testimony'          => '证词',
				'Advocacy'           => '倡导',
				'Getting Started'    => '入门',
				'Voting &amp; Elections' => '投票与选举',
				'Compliance'         => '合规',
			],
			'zh-hant' => [
				'Testimony'          => '證詞',
				'Advocacy'           => '倡導',
				'Getting Started'    => '入門',
				'Voting &amp; Elections' => '投票與選舉',
				'Compliance'         => '合規',
			],
			'ko' => [
				'Testimony'          => '증언',
				'Advocacy'           => '옹호',
				'Getting Started'    => '시작하기',
				'Voting &amp; Elections' => '투표 및 선거',
				'Compliance'         => '규정 준수',
			],
			'es' => [
				'Testimony'          => 'Testimonio',
				'Advocacy'           => 'Abogacía',
				'Getting Started'    => 'Primeros Pasos',
				'Voting &amp; Elections' => 'Votación y Elecciones',
				'Compliance'         => 'Cumplimiento',
			],
			'vi' => [
				'Testimony'          => 'Lời chứng',
				'Advocacy'           => 'Vận động',
				'Getting Started'    => 'Bắt đầu',
				'Voting &amp; Elections' => 'Bỏ phiếu và Bầu cử',
				'Compliance'         => 'Tuân thủ',
			],
			'sm' => [
				'Testimony'          => 'Molimau',
				'Advocacy'           => 'Tautua',
				'Getting Started'    => 'Amata',
				'Voting &amp; Elections' => 'Palota ma Faigapalota',
				'Compliance'         => 'Usitaia',
			],
			'to' => [
				'Testimony'          => 'Fakamoʻoni',
				'Advocacy'           => 'Fakafofonga',
				'Getting Started'    => 'Kamata',
				'Voting &amp; Elections' => 'Fili mo e Fili',
				'Compliance'         => 'Talangofua',
			],
			'mah' => [
				'Testimony'          => 'Kakōļkōļ',
				'Advocacy'           => 'Jipañ',
				'Getting Started'    => 'Jino',
				'Voting &amp; Elections' => 'Bōt im Kōtaan',
				'Compliance'         => 'Pokake',
			],
			'chk' => [
				'Testimony'          => 'Kapas Eis',
				'Advocacy'           => 'Aninisin Aramas',
				'Getting Started'    => 'Tapiiri',
				'Voting &amp; Elections' => 'Fos me Esinesin',
				'Compliance'         => 'Fen Angangen Alluk',
			],
			'th' => [
				'Testimony'          => 'การให้การ',
				'Advocacy'           => 'การสนับสนุน',
				'Getting Started'    => 'เริ่มต้น',
				'Voting &amp; Elections' => 'การลงคะแนนและการเลือกตั้ง',
				'Compliance'         => 'การปฏิบัติตาม',
			],
			'ceb' => [
				'Testimony'          => 'Testimonya',
				'Advocacy'           => 'Adbokasiya',
				'Getting Started'    => 'Pagsugod',
				'Voting &amp; Elections' => 'Pagbotar ug Eleksyon',
				'Compliance'         => 'Pagtuman',
			],
		];

		return $map[ $slug ] ?? [];
	}

	/**
	 * Menu title translations keyed by locale.
	 *
	 * @param string $slug Locale slug.
	 * @return array<string, string> English title => translated title.
	 */
	public static function get_menu_translations( string $slug ): array {
		$map = [
			'haw' => [
				'Get Involved'       => 'E Komo Mai',
				'Public Meetings'    => 'Hālāwai Lehulehu',
				'Tools and Guides'   => 'Mea Hana a me Alakaʻi',
				'Get Notified'       => 'Loaʻa Hoʻolaha',
				'Sunshine Law'       => 'Kānāwai Lā',
				'About'              => 'E Pili Ana',
				'Upcoming Meetings'  => 'Hālāwai e Hiki Mai Ana',
				'Guides'             => 'Alakaʻi',
				'Your Right to Know' => 'Kou Kuleana e ʻIke',
				'Privacy Policy'     => 'Kulekele Pilikino',
				'GitHub'             => 'GitHub',
			],
			'tl' => [
				'Get Involved'       => 'Lumahok',
				'Public Meetings'    => 'Mga Pampublikong Pagpupulong',
				'Tools and Guides'   => 'Mga Kasangkapan at Gabay',
				'Get Notified'       => 'Maabisuhan',
				'Sunshine Law'       => 'Batas sa Transparency',
				'About'              => 'Tungkol',
				'Upcoming Meetings'  => 'Mga Paparating na Pagpupulong',
				'Guides'             => 'Mga Gabay',
				'Your Right to Know' => 'Ang Iyong Karapatang Malaman',
				'Privacy Policy'     => 'Patakaran sa Privacy',
				'GitHub'             => 'GitHub',
			],
			'ja' => [
				'Get Involved'       => '参加する',
				'Public Meetings'    => '公開会議',
				'Tools and Guides'   => 'ツールとガイド',
				'Get Notified'       => '通知を受け取る',
				'Sunshine Law'       => '情報公開法',
				'About'              => 'このサイトについて',
				'Upcoming Meetings'  => '今後の会議',
				'Guides'             => 'ガイド',
				'Your Right to Know' => '知る権利',
				'Privacy Policy'     => 'プライバシーポリシー',
				'GitHub'             => 'GitHub',
			],
			'ilo' => [
				'Get Involved'       => 'Makipaset',
				'Public Meetings'    => 'Dagiti Publiko a Gimong',
				'Tools and Guides'   => 'Dagiti Ramit ken Giya',
				'Get Notified'       => 'Maipakaammo',
				'Sunshine Law'       => 'Linteg ti Kinatalged',
				'About'              => 'Maipapan',
				'Upcoming Meetings'  => 'Dagiti Umay a Gimong',
				'Guides'             => 'Dagiti Giya',
				'Your Right to Know' => 'Ti Karbenganmo a Maammuan',
				'Privacy Policy'     => 'Annuroten ti Pribado',
				'GitHub'             => 'GitHub',
			],
			'zh-hans' => [
				'Get Involved'       => '参与',
				'Public Meetings'    => '公开会议',
				'Tools and Guides'   => '工具与指南',
				'Get Notified'       => '获取通知',
				'Sunshine Law'       => '阳光法案',
				'About'              => '关于',
				'Upcoming Meetings'  => '即将举行的会议',
				'Guides'             => '指南',
				'Your Right to Know' => '您的知情权',
				'Privacy Policy'     => '隐私政策',
				'GitHub'             => 'GitHub',
			],
			'zh-hant' => [
				'Get Involved'       => '參與',
				'Public Meetings'    => '公開會議',
				'Tools and Guides'   => '工具與指南',
				'Get Notified'       => '獲取通知',
				'Sunshine Law'       => '陽光法案',
				'About'              => '關於',
				'Upcoming Meetings'  => '即將舉行的會議',
				'Guides'             => '指南',
				'Your Right to Know' => '您的知情權',
				'Privacy Policy'     => '隱私權政策',
				'GitHub'             => 'GitHub',
			],
			'ko' => [
				'Get Involved'       => '참여하기',
				'Public Meetings'    => '공개 회의',
				'Tools and Guides'   => '도구 및 가이드',
				'Get Notified'       => '알림 받기',
				'Sunshine Law'       => '정보공개법',
				'About'              => '소개',
				'Upcoming Meetings'  => '예정된 회의',
				'Guides'             => '가이드',
				'Your Right to Know' => '알 권리',
				'Privacy Policy'     => '개인정보 처리방침',
				'GitHub'             => 'GitHub',
			],
			'es' => [
				'Get Involved'       => 'Participe',
				'Public Meetings'    => 'Reuniones Públicas',
				'Tools and Guides'   => 'Herramientas y Guías',
				'Get Notified'       => 'Recibir Notificaciones',
				'Sunshine Law'       => 'Ley de Transparencia',
				'About'              => 'Acerca de',
				'Upcoming Meetings'  => 'Próximas Reuniones',
				'Guides'             => 'Guías',
				'Your Right to Know' => 'Su Derecho a Saber',
				'Privacy Policy'     => 'Política de Privacidad',
				'GitHub'             => 'GitHub',
			],
			'vi' => [
				'Get Involved'       => 'Tham gia',
				'Public Meetings'    => 'Họp công khai',
				'Tools and Guides'   => 'Công cụ và Hướng dẫn',
				'Get Notified'       => 'Nhận thông báo',
				'Sunshine Law'       => 'Luật Minh bạch',
				'About'              => 'Giới thiệu',
				'Upcoming Meetings'  => 'Cuộc họp sắp tới',
				'Guides'             => 'Hướng dẫn',
				'Your Right to Know' => 'Quyền được biết',
				'Privacy Policy'     => 'Chính sách bảo mật',
				'GitHub'             => 'GitHub',
			],
			'sm' => [
				'Get Involved'       => 'Auai',
				'Public Meetings'    => 'Fono a le Atunuʻu',
				'Tools and Guides'   => 'Meafaigaluega ma Taʻiala',
				'Get Notified'       => 'Maua Faʻailo',
				'Sunshine Law'       => 'Tulafono o le Lā',
				'About'              => 'Faamatalaga',
				'Upcoming Meetings'  => 'Fono o Loʻo Oʻo Mai',
				'Guides'             => 'Taʻiala',
				'Your Right to Know' => 'Lou Aia e Iloa',
				'Privacy Policy'     => 'Tulafono o le Leʻilēaga',
				'GitHub'             => 'GitHub',
			],
			'to' => [
				'Get Involved'       => 'Kau Mai',
				'Public Meetings'    => 'Ngaahi Fakataha Fakafonua',
				'Tools and Guides'   => 'Ngaahi Meʻangāue mo e Fakahinohino',
				'Get Notified'       => 'Maʻu Fakalāloa',
				'Sunshine Law'       => 'Lao Maama',
				'About'              => 'Fekauʻaki',
				'Upcoming Meetings'  => 'Ngaahi Fakataha ʻOku Haʻu',
				'Guides'             => 'Ngaahi Fakahinohino',
				'Your Right to Know' => 'Ho Totonu ke ʻIlo',
				'Privacy Policy'     => 'Tuʻutuʻuni Fakapulipuli',
				'GitHub'             => 'GitHub',
			],
			'mah' => [
				'Get Involved'       => 'Drelōñ',
				'Public Meetings'    => 'Kōjjelā ko an Aolep',
				'Tools and Guides'   => 'Jikōj ko im Katak ko',
				'Get Notified'       => 'Bōk Kōjeļā',
				'Sunshine Law'       => 'Kien in Aḷ',
				'About'              => 'Kōn',
				'Upcoming Meetings'  => 'Kōjjelā ko Rej Itok',
				'Guides'             => 'Katak ko',
				'Your Right to Know' => 'Am Jimwe in Jeļā',
				'Privacy Policy'     => 'Kien in Būruon',
				'GitHub'             => 'GitHub',
			],
			'chk' => [
				'Get Involved'       => 'Kopwe Aea',
				'Public Meetings'    => 'Ekkena Meinisin',
				'Tools and Guides'   => 'Ekkewe Mettoch me Aninis',
				'Get Notified'       => 'Tongeni Eis',
				'Sunshine Law'       => 'Ewe Alluk en Araw',
				'About'              => 'Usun',
				'Upcoming Meetings'  => 'Ekkena A Feino',
				'Guides'             => 'Aninis',
				'Your Right to Know' => 'Om Pwung Ke Sinei',
				'Privacy Policy'     => 'Policy en Kopwene',
				'GitHub'             => 'GitHub',
			],
			'th' => [
				'Get Involved'       => 'มีส่วนร่วม',
				'Public Meetings'    => 'การประชุมสาธารณะ',
				'Tools and Guides'   => 'เครื่องมือและคู่มือ',
				'Get Notified'       => 'รับการแจ้งเตือน',
				'Sunshine Law'       => 'กฎหมายความโปร่งใส',
				'About'              => 'เกี่ยวกับ',
				'Upcoming Meetings'  => 'การประชุมที่กำลังจะมาถึง',
				'Guides'             => 'คู่มือ',
				'Your Right to Know' => 'สิทธิในการรับรู้',
				'Privacy Policy'     => 'นโยบายความเป็นส่วนตัว',
				'GitHub'             => 'GitHub',
			],
			'ceb' => [
				'Get Involved'       => 'Pag-apil',
				'Public Meetings'    => 'Mga Publikong Tigom',
				'Tools and Guides'   => 'Mga Himan ug Giya',
				'Get Notified'       => 'Pagpahibalo',
				'Sunshine Law'       => 'Balaod sa Transparency',
				'About'              => 'Bahin',
				'Upcoming Meetings'  => 'Mga Umaabot nga Tigom',
				'Guides'             => 'Mga Giya',
				'Your Right to Know' => 'Imong Katungod nga Mahibalo',
				'Privacy Policy'     => 'Palisiya sa Pagkapribado',
				'GitHub'             => 'GitHub',
			],
		];

		return $map[ $slug ] ?? [];
	}

	/**
	 * Get the full locale registry.
	 *
	 * @return array<string, array{string, string, bool}>
	 */
	public static function get_locales(): array {
		return self::LOCALES;
	}

	/**
	 * Get native language name for a slug.
	 */
	public static function get_native_name( string $slug ): string {
		return self::LOCALES[ $slug ][0] ?? $slug;
	}

	/**
	 * Get the WordPress locale code for a slug.
	 */
	public static function get_wp_locale( string $slug ): string {
		return self::LOCALES[ $slug ][1] ?? '';
	}

	/**
	 * Check if a slug has a WordPress core language pack.
	 */
	public static function has_wp_pack( string $slug ): bool {
		return self::LOCALES[ $slug ][2] ?? false;
	}

	/**
	 * Get all locale slugs that have at least one .mo file available.
	 *
	 * Checks civime domain first, then civime-meetings, etc.
	 * English is always included.
	 *
	 * @return string[]
	 */
	public static function get_available_slugs(): array {
		$available = [ 'en' ];

		foreach ( self::LOCALES as $slug => $info ) {
			if ( 'en' === $slug ) {
				continue;
			}

			$wp_locale = $info[1];

			foreach ( self::TEXT_DOMAINS as $domain => $rel_path ) {
				$mo_file = WP_PLUGIN_DIR . '/' . $rel_path . '/' . $domain . '-' . $wp_locale . '.mo';
				if ( file_exists( $mo_file ) ) {
					$available[] = $slug;
					break;
				}
			}
		}

		return $available;
	}

	/**
	 * Check if a slug is valid (exists in registry).
	 */
	public static function is_valid_slug( string $slug ): bool {
		return isset( self::LOCALES[ $slug ] );
	}

	/**
	 * Detect the active locale slug from query param or cookie.
	 */
	private function detect_slug(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$param = isset( $_GET['lang'] ) ? sanitize_text_field( wp_unslash( $_GET['lang'] ) ) : '';

		if ( '' !== $param && self::is_valid_slug( $param ) ) {
			return $param;
		}

		$cookie = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : '';

		if ( '' !== $cookie && self::is_valid_slug( $cookie ) ) {
			return $cookie;
		}

		return 'en';
	}

	/**
	 * Set the persistence cookie.
	 */
	private function set_cookie(): void {
		// Only set/update cookie when there's a ?lang= param.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['lang'] ) ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		$cookie_days = (int) get_option( 'civime_i18n_cookie_days', 7 );
		$expires     = time() + ( $cookie_days * DAY_IN_SECONDS );

		setcookie(
			self::COOKIE_NAME,
			$this->active_slug,
			[
				'expires'  => $expires,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * Load all CiviMe text domains from the central languages/ directory.
	 *
	 * Uses load_textdomain() with explicit file paths because
	 * load_plugin_textdomain() relies on get_locale() which may not
	 * recognise custom locale codes like 'haw'.
	 */
	private function load_text_domains(): void {
		if ( 'en' === $this->active_slug ) {
			return;
		}

		$wp_locale = self::get_wp_locale( $this->active_slug );

		foreach ( self::TEXT_DOMAINS as $domain => $rel_path ) {
			$mo_file = WP_PLUGIN_DIR . '/' . $rel_path . '/' . $domain . '-' . $wp_locale . '.mo';

			if ( file_exists( $mo_file ) ) {
				load_textdomain( $domain, $mo_file );
			}
		}
	}
}
