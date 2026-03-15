<?php
/**
 * Translated page content loader.
 *
 * Swaps English post_content with translated HTML files from
 * wp-content/page-content/{lang}/{slug}.html when a non-English
 * locale is active.
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_I18n_Page_Content {

	/** @var string Active locale slug. */
	private string $lang;

	public function __construct() {
		$this->lang = apply_filters( 'civime_i18n_active_slug', 'en' );

		if ( 'en' === $this->lang ) {
			return;
		}

		add_filter( 'the_content', [ $this, 'translate_content' ], 8 );
		add_filter( 'the_title', [ $this, 'translate_title' ], 10, 2 );
	}

	/**
	 * Replace page content with translated HTML file if available.
	 *
	 * @param string $content Original post content.
	 * @return string
	 */
	public function translate_content( string $content ): string {
		if ( ! is_page() || ! in_the_loop() ) {
			return $content;
		}

		$slug      = get_post_field( 'post_name', get_the_ID() );
		$cache_key = 'civime_page_' . $this->lang . '_' . $slug;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			// Sentinel empty string means file doesn't exist.
			return '' === $cached ? $content : $cached;
		}

		$file = WP_CONTENT_DIR . '/page-content/' . $this->lang . '/' . $slug . '.html';

		if ( ! file_exists( $file ) ) {
			set_transient( $cache_key, '', 12 * HOUR_IN_SECONDS );
			return $content;
		}

		$translated = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $translated || '' === $translated ) {
			set_transient( $cache_key, '', 12 * HOUR_IN_SECONDS );
			return $content;
		}

		set_transient( $cache_key, $translated, 12 * HOUR_IN_SECONDS );

		return $translated;
	}

	/**
	 * Translate page titles for the active locale.
	 *
	 * @param string $title   Original title.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function translate_title( string $title, int $post_id = 0 ): string {
		if ( ! is_page() || ! in_the_loop() ) {
			return $title;
		}

		$translations = self::get_title_translations( $this->lang );

		return $translations[ $title ] ?? $title;
	}

	/**
	 * Page title translations keyed by locale.
	 *
	 * Draws from menu translations and adds additional page titles.
	 *
	 * @param string $slug Locale slug.
	 * @return array<string, string> English title => translated title.
	 */
	private static function get_title_translations( string $slug ): array {
		// Start with menu translations (covers most page titles).
		$titles = CiviMe_I18n_Locale::get_menu_translations( $slug );

		// Additional page titles not in menus.
		$extra = [
			'haw' => [
				'Contact'                => 'Hoʻokaʻaʻike',
				'Events'                 => 'Nā Hanana',
				'Getting Started'        => 'Hoʻomaka',
				'How to Testify'         => 'Pehea e Hōʻike Ai',
				'Voting in Hawaii'       => 'Koho Balota ma Hawaiʻi',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Waihona Mea Hana ʻElele',
				'Letter Writing Kit'     => 'Waihona Kākau Leka',
				'Accessibility & Compliance' => 'Hiki ke Komo & Hoʻokō',
				'Public Participation'   => 'Komo ʻAna o ka Lehulehu',
				'Public Records'         => 'Moʻolelo Lehulehu',
			],
			'tl' => [
				'Contact'                => 'Makipag-ugnayan',
				'Events'                 => 'Mga Kaganapan',
				'Getting Started'        => 'Pagsisimula',
				'How to Testify'         => 'Paano Magpatotoo',
				'Voting in Hawaii'       => 'Pagboto sa Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Toolkit ng Ambassador',
				'Letter Writing Kit'     => 'Kit sa Pagsulat ng Liham',
				'Accessibility & Compliance' => 'Accessibility at Pagsunod',
				'Public Participation'   => 'Pakikilahok ng Publiko',
				'Public Records'         => 'Mga Pampublikong Rekord',
			],
			'ja' => [
				'Contact'                => 'お問い合わせ',
				'Events'                 => 'イベント',
				'Getting Started'        => 'はじめに',
				'How to Testify'         => '証言の方法',
				'Voting in Hawaii'       => 'ハワイでの投票',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'アンバサダーツールキット',
				'Letter Writing Kit'     => '手紙作成キット',
				'Accessibility & Compliance' => 'アクセシビリティとコンプライアンス',
				'Public Participation'   => '市民参加',
				'Public Records'         => '公文書',
			],
			'ilo' => [
				'Contact'                => 'Kontaken',
				'Events'                 => 'Dagiti Pasamak',
				'Getting Started'        => 'Panagrugi',
				'How to Testify'         => 'Kasano ti Agtestigo',
				'Voting in Hawaii'       => 'Panagbotos idiay Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Toolkit ti Ambassador',
				'Letter Writing Kit'     => 'Kit ti Panagsurat iti Surat',
				'Accessibility & Compliance' => 'Accessibility ken Panagtungpal',
				'Public Participation'   => 'Pannakipaset ti Publiko',
				'Public Records'         => 'Dagiti Rekord Publiko',
			],
			'zh-hans' => [
				'Contact'                => '联系我们',
				'Events'                 => '活动',
				'Getting Started'        => '入门',
				'How to Testify'         => '如何作证',
				'Voting in Hawaii'       => '在Hawaii投票',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => '大使工具包',
				'Letter Writing Kit'     => '写信工具包',
				'Accessibility & Compliance' => '无障碍与合规',
				'Public Participation'   => '公众参与',
				'Public Records'         => '公共记录',
			],
			'zh-hant' => [
				'Contact'                => '聯繫我們',
				'Events'                 => '活動',
				'Getting Started'        => '入門',
				'How to Testify'         => '如何作證',
				'Voting in Hawaii'       => '在Hawaii投票',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => '大使工具包',
				'Letter Writing Kit'     => '寫信工具包',
				'Accessibility & Compliance' => '無障礙與合規',
				'Public Participation'   => '公眾參與',
				'Public Records'         => '公共記錄',
			],
			'ko' => [
				'Contact'                => '연락처',
				'Events'                 => '이벤트',
				'Getting Started'        => '시작하기',
				'How to Testify'         => '증언 방법',
				'Voting in Hawaii'       => 'Hawaii 투표',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => '앰배서더 툴킷',
				'Letter Writing Kit'     => '편지 작성 키트',
				'Accessibility & Compliance' => '접근성 및 규정 준수',
				'Public Participation'   => '시민 참여',
				'Public Records'         => '공공 기록',
			],
			'es' => [
				'Contact'                => 'Contacto',
				'Events'                 => 'Eventos',
				'Getting Started'        => 'Primeros Pasos',
				'How to Testify'         => 'Cómo Testificar',
				'Voting in Hawaii'       => 'Votar en Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Kit del Embajador',
				'Letter Writing Kit'     => 'Kit de Escritura de Cartas',
				'Accessibility & Compliance' => 'Accesibilidad y Cumplimiento',
				'Public Participation'   => 'Participación Pública',
				'Public Records'         => 'Registros Públicos',
			],
			'vi' => [
				'Contact'                => 'Liên hệ',
				'Events'                 => 'Sự kiện',
				'Getting Started'        => 'Bắt đầu',
				'How to Testify'         => 'Cách làm chứng',
				'Voting in Hawaii'       => 'Bỏ phiếu tại Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Bộ công cụ Đại sứ',
				'Letter Writing Kit'     => 'Bộ viết thư',
				'Accessibility & Compliance' => 'Khả năng tiếp cận và Tuân thủ',
				'Public Participation'   => 'Sự tham gia của công chúng',
				'Public Records'         => 'Hồ sơ công',
			],
			'sm' => [
				'Contact'                => 'Faʻafesootaʻi',
				'Events'                 => 'Mea na Tutupu',
				'Getting Started'        => 'Amata',
				'How to Testify'         => 'Faʻapefea ona Molimau',
				'Voting in Hawaii'       => 'Palota i Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Meafaigaluega a le Ambassador',
				'Letter Writing Kit'     => 'Meafaigaluega Tusi',
				'Accessibility & Compliance' => 'Avanoa ma le Usitaia',
				'Public Participation'   => 'Auai a le Atunuʻu',
				'Public Records'         => 'Faamaumauga a le Malo',
			],
			'to' => [
				'Contact'                => 'Fetuʻutaki',
				'Events'                 => 'Ngaahi Meʻa',
				'Getting Started'        => 'Kamata',
				'How to Testify'         => 'Founga ke Fakamoʻoni',
				'Voting in Hawaii'       => 'Fili ʻi Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Meʻangāue ʻa e ʻAmipasitoa',
				'Letter Writing Kit'     => 'Meʻangāue Tohi',
				'Accessibility & Compliance' => 'Malava ke Maʻu mo e Talangofua',
				'Public Participation'   => 'Kau ʻa e Kakai',
				'Public Records'         => 'Ngaahi Lekooti Fakafonua',
			],
			'mah' => [
				'Contact'                => 'Kōnnaan',
				'Events'                 => 'Wāween ko',
				'Getting Started'        => 'Jino',
				'How to Testify'         => 'Ewi Kakōļkōļ',
				'Voting in Hawaii'       => 'Bōt ilo Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Jikōj in Ambassador',
				'Letter Writing Kit'     => 'Jikōj in Jeje Leta',
				'Accessibility & Compliance' => 'Maroñ in Jerbal im Pokake',
				'Public Participation'   => 'Drelōñ in Aolep',
				'Public Records'         => 'Rekōt ko an Aolep',
			],
			'chk' => [
				'Contact'                => 'Kōnnei',
				'Events'                 => 'Ekkewe Meen',
				'Getting Started'        => 'Tapiiri',
				'How to Testify'         => 'Met Kopwe Kapas Eis',
				'Voting in Hawaii'       => 'Fos non Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Mettoch en Ambassador',
				'Letter Writing Kit'     => 'Mettoch en Kasen Ika',
				'Accessibility & Compliance' => 'Tongeni Eis me Fen Angangen Alluk',
				'Public Participation'   => 'Aea en Meinisin',
				'Public Records'         => 'Record en Meinisin',
			],
			'th' => [
				'Contact'                => 'ติดต่อ',
				'Events'                 => 'กิจกรรม',
				'Getting Started'        => 'เริ่มต้น',
				'How to Testify'         => 'วิธีการให้การ',
				'Voting in Hawaii'       => 'การลงคะแนนใน Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'ชุดเครื่องมือทูต',
				'Letter Writing Kit'     => 'ชุดเขียนจดหมาย',
				'Accessibility & Compliance' => 'การเข้าถึงและการปฏิบัติตาม',
				'Public Participation'   => 'การมีส่วนร่วมของประชาชน',
				'Public Records'         => 'เอกสารสาธารณะ',
			],
			'ceb' => [
				'Contact'                => 'Kontaka',
				'Events'                 => 'Mga Panghitabo',
				'Getting Started'        => 'Pagsugod',
				'How to Testify'         => 'Unsaon Pagsaksi',
				'Voting in Hawaii'       => 'Pagbotar sa Hawaii',
				'Home'                   => 'Home',
				'Ambassador Toolkit'     => 'Toolkit sa Ambassador',
				'Letter Writing Kit'     => 'Kit sa Pagsulat og Sulat',
				'Accessibility & Compliance' => 'Accessibility ug Pagtuman',
				'Public Participation'   => 'Pag-apil sa Publiko',
				'Public Records'         => 'Mga Rekord sa Publiko',
			],
		];

		if ( isset( $extra[ $slug ] ) ) {
			$titles = array_merge( $titles, $extra[ $slug ] );
		}

		return $titles;
	}

	/**
	 * Flush all page content transients.
	 */
	public static function flush_cache(): void {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_civime_page_%'
			    OR option_name LIKE '_transient_timeout_civime_page_%'"
		);
	}
}
