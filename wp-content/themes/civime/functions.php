<?php
/**
 * CiviMe Theme Functions
 *
 * @package CiviMe
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CIVIME_VERSION', '0.1.0' );
define( 'CIVIME_DIR', get_template_directory() );
define( 'CIVIME_URI', get_template_directory_uri() );

/**
 * Theme setup: supports, menus, image sizes.
 */
function civime_setup(): void {
    load_theme_textdomain( 'civime', CIVIME_DIR . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
        'navigation-widgets',
    ) );

    // Color palette for the block editor
    add_theme_support( 'editor-color-palette', array(
        array(
            'name'  => __( 'Ocean Blue', 'civime' ),
            'slug'  => 'primary',
            'color' => '#1a5276',
        ),
        array(
            'name'  => __( 'Reef Teal', 'civime' ),
            'slug'  => 'secondary',
            'color' => '#148f77',
        ),
        array(
            'name'  => __( 'Sunset Orange', 'civime' ),
            'slug'  => 'accent',
            'color' => '#d4721a',
        ),
        array(
            'name'  => __( 'Near White', 'civime' ),
            'slug'  => 'background',
            'color' => '#fafbfc',
        ),
        array(
            'name'  => __( 'Near Black', 'civime' ),
            'slug'  => 'text',
            'color' => '#1a1a2e',
        ),
    ) );

    add_theme_support( 'custom-line-height' );
    add_theme_support( 'custom-spacing' );
    add_theme_support( 'appearance-tools' );

    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'civime' ),
        'footer'  => __( 'Footer Menu', 'civime' ),
    ) );

    // Thumbnail sizes
    add_image_size( 'civime-card', 600, 400, true );
    add_image_size( 'civime-hero', 1400, 700, true );
}
add_action( 'after_setup_theme', 'civime_setup' );

/**
 * Set content width for embeds and media.
 */
function civime_content_width(): void {
    $GLOBALS['content_width'] = 1200;
}
add_action( 'after_setup_theme', 'civime_content_width', 0 );

/**
 * Enqueue styles and scripts.
 */
function civime_scripts(): void {
    wp_enqueue_style(
        'civime-fonts',
        'https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;500;600;700&display=swap',
        array(),
        null
    );

    // Main theme stylesheet (style.css — holds only the theme header)
    wp_enqueue_style(
        'civime-style',
        get_stylesheet_uri(),
        array(),
        CIVIME_VERSION
    );

    // Full CSS bundle
    wp_enqueue_style(
        'civime-theme',
        CIVIME_URI . '/assets/css/theme.css',
        array( 'civime-style' ),
        CIVIME_VERSION
    );

    // Minimal JS
    wp_enqueue_script(
        'civime-theme',
        CIVIME_URI . '/assets/js/theme.js',
        array(),
        CIVIME_VERSION,
        array(
            'strategy'  => 'defer',
            'in_footer' => true,
        )
    );

    // Comments reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'civime_scripts' );

/**
 * Generate a per-request CSP nonce for inline scripts allowed by
 * the Content-Security-Policy header.
 */
function civime_csp_nonce(): string {
    static $nonce = null;
    if ( null === $nonce ) {
        $nonce = base64_encode( random_bytes( 16 ) );
    }
    return $nonce;
}

/**
 * Add preconnect link for Google Fonts origin.
 */
function civime_preconnect_fonts(): void {
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}
add_action( 'wp_head', 'civime_preconnect_fonts', 2 );

/**
 * Adds `lang` attribute and correct dir to <html> (WordPress handles lang,
 * but we ensure dir is present for RTL readiness).
 */
function civime_add_html_class( string $output ): string {
    return $output;
}
add_filter( 'language_attributes', 'civime_add_html_class' );

/**
 * Icon registry — available icons for navigation menu items.
 *
 * All icons are from Lucide (https://lucide.dev/) — MIT licensed, 24×24 grid,
 * 2px stroke, round caps/joins.
 *
 * @return array<string, array{label: string, svg: string}> Slug → label + SVG inner markup.
 */
function civime_icon_registry(): array {
    return [
        'lightbulb'    => [
            'label' => __( 'Lightbulb', 'civime' ),
            'svg'   => '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
        ],
        'calendar'     => [
            'label' => __( 'Calendar', 'civime' ),
            'svg'   => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
        ],
        'building'     => [
            'label' => __( 'Building', 'civime' ),
            'svg'   => '<line x1="3" x2="21" y1="22" y2="22"/><line x1="6" x2="6" y1="18" y2="11"/><line x1="10" x2="10" y1="18" y2="11"/><line x1="14" x2="14" y1="18" y2="11"/><line x1="18" x2="18" y1="18" y2="11"/><polygon points="12 2 20 7 4 7"/>',
        ],
        'sun'          => [
            'label' => __( 'Sun', 'civime' ),
            'svg'   => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
        ],
        'book'         => [
            'label' => __( 'Book', 'civime' ),
            'svg'   => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        ],
        'bell-ring'    => [
            'label' => __( 'Bell (ringing)', 'civime' ),
            'svg'   => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/><line x1="1" y1="1" x2="4" y2="4"/><line x1="23" y1="1" x2="20" y2="4"/>',
        ],
        'bell'         => [
            'label' => __( 'Bell', 'civime' ),
            'svg'   => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        ],
        'users'        => [
            'label' => __( 'Users', 'civime' ),
            'svg'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        ],
        'home'         => [
            'label' => __( 'Home', 'civime' ),
            'svg'   => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        ],
        'search'       => [
            'label' => __( 'Search', 'civime' ),
            'svg'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        ],
        'shield'       => [
            'label' => __( 'Shield', 'civime' ),
            'svg'   => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>',
        ],
        'scale'        => [
            'label' => __( 'Scale / Justice', 'civime' ),
            'svg'   => '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
        ],
        'file-text'    => [
            'label' => __( 'Document', 'civime' ),
            'svg'   => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        ],
        'megaphone'    => [
            'label' => __( 'Megaphone', 'civime' ),
            'svg'   => '<path d="m3 11 18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        ],
        'heart'        => [
            'label' => __( 'Heart', 'civime' ),
            'svg'   => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        ],
        'globe'        => [
            'label' => __( 'Globe', 'civime' ),
            'svg'   => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        ],
        'map-pin'      => [
            'label' => __( 'Map Pin', 'civime' ),
            'svg'   => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        ],
        'mail'         => [
            'label' => __( 'Mail', 'civime' ),
            'svg'   => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        ],
        'info'         => [
            'label' => __( 'Info', 'civime' ),
            'svg'   => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        ],
        'link'         => [
            'label' => __( 'Link', 'civime' ),
            'svg'   => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        ],
    ];
}

/**
 * Return an inline SVG icon by slug, menu-item meta, or URL fallback.
 *
 * @param string $url          The menu item URL.
 * @param string $title        The menu item title (fallback matching).
 * @param int    $menu_item_id The menu item post ID (0 = skip meta lookup).
 * @return string              SVG markup or empty string.
 */
function civime_nav_icon( string $url, string $title = '', int $menu_item_id = 0 ): string {
    $registry = civime_icon_registry();
    $slug     = '';

    // 1. Check menu item meta (admin-selected icon).
    if ( $menu_item_id > 0 ) {
        $meta_slug = get_post_meta( $menu_item_id, '_civime_nav_icon', true );
        if ( 'none' === $meta_slug ) {
            return '';
        }
        if ( $meta_slug && isset( $registry[ $meta_slug ] ) ) {
            $slug = $meta_slug;
        }
    }

    // 2. Fall back to URL-path matching.
    if ( '' === $slug ) {
        $path = wp_parse_url( $url, PHP_URL_PATH );
        $path = '/' . trim( $path ?? '', '/' ) . '/';

        $url_map = [
            '/what-matters/'       => 'lightbulb',
            '/meetings/'           => 'calendar',
            '/councils/'           => 'building',
            '/your-right-to-know/' => 'sun',
            '/sunshine-law/'       => 'sun',
            '/guides/'             => 'book',
            '/get-notified/'       => 'bell-ring',
            '/notifications/'      => 'bell',
            '/get-involved/'       => 'users',
        ];

        foreach ( $url_map as $url_slug => $icon_slug ) {
            if ( str_contains( $path, $url_slug ) ) {
                $slug = $icon_slug;
                break;
            }
        }
    }

    // 3. Fall back to title matching.
    if ( '' === $slug && '' !== $title ) {
        $title_map = [
            'get notified' => 'bell-ring',
            'sunshine law' => 'sun',
            'get involved' => 'users',
        ];

        $lower = strtolower( $title );
        foreach ( $title_map as $label => $icon_slug ) {
            if ( str_contains( $lower, $label ) ) {
                $slug = $icon_slug;
                break;
            }
        }
    }

    if ( '' === $slug || ! isset( $registry[ $slug ] ) ) {
        return '';
    }

    return '<svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $registry[ $slug ]['svg']
        . '</svg>';
}

/**
 * Return an SVG icon for the current page header based on URL.
 *
 * Reuses the icon registry but maps by URL path. Returns empty string
 * when no icon matches, so pages without a mapped icon render cleanly.
 */
function civime_page_header_icon(): string {
    $registry = civime_icon_registry();
    $path     = '/' . trim( wp_parse_url( get_permalink(), PHP_URL_PATH ) ?? '', '/' ) . '/';

    $url_map = [
        '/what-matters/'             => 'lightbulb',
        '/meetings/'                 => 'calendar',
        '/councils/'                 => 'building',
        '/your-right-to-know/'       => 'sun',
        '/sunshine-law/'             => 'sun',
        '/guides/'                   => 'book',
        '/get-notified/'             => 'bell-ring',
        '/notifications/'            => 'bell',
        '/get-involved/'             => 'users',
        '/public-participation/'     => 'megaphone',
        '/public-records/'           => 'file-text',
        '/accessibility-compliance/' => 'shield',
    ];

    $slug = '';
    foreach ( $url_map as $url_slug => $icon_slug ) {
        if ( str_contains( $path, $url_slug ) ) {
            $slug = $icon_slug;
            break;
        }
    }

    if ( '' === $slug || ! isset( $registry[ $slug ] ) ) {
        return '';
    }

    return '<svg class="page-header__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $registry[ $slug ]['svg']
        . '</svg>';
}

/**
 * Add an "Icon" dropdown to each menu item in the Appearance → Menus editor.
 *
 * @param int      $item_id Menu item ID.
 * @param \WP_Post $item    Menu item data object.
 * @param int      $depth   Depth.
 * @param \stdClass $args   Walker args.
 */
function civime_nav_menu_icon_field( $item_id, $item, $depth, $args ): void {
    $current = get_post_meta( $item_id, '_civime_nav_icon', true );
    $icons   = civime_icon_registry();
    ?>
    <p class="field-civime-icon description description-wide">
        <label for="edit-menu-item-civime-icon-<?php echo esc_attr( $item_id ); ?>">
            <?php esc_html_e( 'Icon', 'civime' ); ?>
            <select
                id="edit-menu-item-civime-icon-<?php echo esc_attr( $item_id ); ?>"
                name="menu-item-civime-icon[<?php echo esc_attr( $item_id ); ?>]"
                style="width:100%"
            >
                <option value=""><?php esc_html_e( '— Auto (match by URL) —', 'civime' ); ?></option>
                <option value="none" <?php selected( $current, 'none' ); ?>><?php esc_html_e( '— No icon —', 'civime' ); ?></option>
                <?php foreach ( $icons as $slug => $icon ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current, $slug ); ?>>
                        <?php echo esc_html( $icon['label'] ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </p>
    <?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'civime_nav_menu_icon_field', 10, 4 );

/**
 * Save the icon selection when a menu is saved.
 *
 * @param int $menu_id         ID of the menu.
 * @param int $menu_item_db_id ID of the menu item.
 */
function civime_save_nav_menu_icon( $menu_id, $menu_item_db_id ): void {
    $key = 'menu-item-civime-icon';

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP core verifies the menu nonce.
    if ( ! isset( $_POST[ $key ][ $menu_item_db_id ] ) ) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $value = sanitize_text_field( wp_unslash( $_POST[ $key ][ $menu_item_db_id ] ) );

    $registry = civime_icon_registry();
    if ( '' === $value ) {
        delete_post_meta( $menu_item_db_id, '_civime_nav_icon' );
    } elseif ( 'none' === $value || isset( $registry[ $value ] ) ) {
        update_post_meta( $menu_item_db_id, '_civime_nav_icon', $value );
    }
}
add_action( 'wp_update_nav_menu_item', 'civime_save_nav_menu_icon', 10, 2 );

/**
 * Custom nav walker that adds our CSS classes to nav links.
 */
class CiviMe_Nav_Walker extends Walker_Nav_Menu {

    /**
     * @param string   $output  Passed by reference.
     * @param \WP_Post $item    Menu item data object.
     * @param int      $depth   Depth of menu item.
     * @param \stdClass $args   An object of wp_nav_menu() arguments.
     * @param int      $id      Current item ID.
     */
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
        $classes     = empty( $item->classes ) ? array() : (array) $item->classes;
        $is_current  = in_array( 'current-menu-item', $classes, true );
        $link_class  = 'primary-nav__link';

        if ( $is_current ) {
            $link_class .= ' current-menu-item';
        }

        $aria_current = $is_current ? ' aria-current="page"' : '';
        $url          = esc_url( $item->url );
        $title        = esc_html( $item->title );
        $target       = ! empty( $item->target ) ? ' target="' . esc_attr( $item->target ) . '"' : '';
        $rel          = ! empty( $item->xfn ) ? ' rel="' . esc_attr( $item->xfn ) . '"' : '';

        $icon = civime_nav_icon( $item->url, $item->title, (int) $item->ID );

        $output .= "<li>";
        $output .= "<a href=\"{$url}\" class=\"{$link_class}\"{$aria_current}{$target}{$rel}>{$icon}{$title}</a>";
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ): void {
        $output .= "</li>\n";
    }
}

/**
 * Mobile nav walker — same semantics, different CSS class.
 */
class CiviMe_Mobile_Nav_Walker extends Walker_Nav_Menu {

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
        $classes     = empty( $item->classes ) ? array() : (array) $item->classes;
        $is_current  = in_array( 'current-menu-item', $classes, true );
        $link_class  = 'mobile-nav__link';

        if ( $is_current ) {
            $link_class .= ' current-menu-item';
        }

        $aria_current = $is_current ? ' aria-current="page"' : '';
        $url          = esc_url( $item->url );
        $title        = esc_html( $item->title );
        $target       = ! empty( $item->target ) ? ' target="' . esc_attr( $item->target ) . '"' : '';
        $icon         = civime_nav_icon( $item->url, $item->title, (int) $item->ID );

        $output .= "<li>";
        $output .= "<a href=\"{$url}\" class=\"{$link_class}\"{$aria_current}{$target}>{$icon}{$title}</a>";
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ): void {
        $output .= "</li>\n";
    }
}

/**
 * Footer nav walker.
 */
class CiviMe_Footer_Nav_Walker extends Walker_Nav_Menu {

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
        $url   = esc_url( $item->url );
        $title = esc_html( $item->title );
        $icon  = civime_nav_icon( $item->url, $item->title, (int) $item->ID );

        $output .= "<li>";
        $output .= "<a href=\"{$url}\" class=\"footer-nav__link\">{$icon}{$title}</a>";
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ): void {
        $output .= "</li>\n";
    }
}

/**
 * Fallback primary navigation when no menu is configured in WP Admin.
 *
 * Renders the essential site pages so visitors can always navigate.
 *
 * @param array $args wp_nav_menu arguments.
 */
function civime_fallback_primary_nav( array $args ): void {
    $items = [
        [ 'url' => '/what-matters/',      'label' => __( 'Topics', 'civime' ) ],
        [ 'url' => '/meetings/',           'label' => __( 'Meetings', 'civime' ) ],
        [ 'url' => '/councils/',            'label' => __( 'Councils', 'civime' ) ],
        [ 'url' => '/guides/',             'label' => __( 'Guides', 'civime' ) ],
        [ 'url' => '/notifications/',      'label' => __( 'Alerts', 'civime' ) ],
    ];

    $menu_class = $args['menu_class'] ?? 'primary-nav__list';
    $link_class = str_contains( $menu_class, 'mobile' ) ? 'mobile-nav__link' : 'primary-nav__link';

    $current_path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    $current_path = '/' . trim( $current_path ?? '', '/' ) . '/';

    echo '<ul class="' . esc_attr( $menu_class ) . '">';
    foreach ( $items as $item ) {
        $url       = home_url( $item['url'] );
        $is_active = str_starts_with( $current_path, $item['url'] );
        $aria      = $is_active ? ' aria-current="page"' : '';
        $class     = $link_class . ( $is_active ? ' current-menu-item' : '' );
        $icon      = civime_nav_icon( $item['url'] );

        echo '<li><a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '"' . $aria . '>'
            . $icon
            . esc_html( $item['label'] )
            . '</a></li>';
    }
    echo '</ul>';
}

/**
 * Fallback footer navigation when no footer menu is configured.
 *
 * @param array $args wp_nav_menu arguments.
 */
function civime_fallback_footer_nav( array $args ): void {
    $items = [
        [ 'url' => '/what-matters/',      'label' => __( 'Topics', 'civime' ) ],
        [ 'url' => '/meetings/',           'label' => __( 'Meetings', 'civime' ) ],
        [ 'url' => '/councils/',            'label' => __( 'Councils', 'civime' ) ],
        [ 'url' => '/guides/',             'label' => __( 'Guides', 'civime' ) ],
        [ 'url' => '/notifications/',      'label' => __( 'Alerts', 'civime' ) ],
    ];

    echo '<ul class="' . esc_attr( $args['menu_class'] ?? 'footer-nav__list' ) . '">';
    foreach ( $items as $item ) {
        $icon = civime_nav_icon( $item['url'] );

        echo '<li><a href="' . esc_url( home_url( $item['url'] ) ) . '" class="footer-nav__link">'
            . $icon
            . esc_html( $item['label'] )
            . '</a></li>';
    }
    echo '</ul>';
}

/**
 * Security headers: remove X-Powered-By everywhere, CSP on frontend only.
 */
function civime_security_headers(): void {
    header_remove( 'X-Powered-By' );

    if ( is_admin() ) {
        return;
    }

    $nonce = civime_csp_nonce();

    $csp = implode( '; ', array(
        "default-src 'self'",
        "script-src 'self' 'nonce-{$nonce}'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data: https:",
        "connect-src 'self'",
        "worker-src 'self' blob:",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
    ) );

    header( "Content-Security-Policy: {$csp}" );
    header( 'X-Content-Type-Options: nosniff' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
}
add_action( 'send_headers', 'civime_security_headers' );

/**
 * Excerpt length.
 */
function civime_excerpt_length(): int {
    return 30;
}
add_filter( 'excerpt_length', 'civime_excerpt_length' );

/**
 * Excerpt more string.
 */
function civime_excerpt_more(): string {
    return '&hellip;';
}
add_filter( 'excerpt_more', 'civime_excerpt_more' );

/**
 * Body classes — add useful context classes.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function civime_body_classes( array $classes ): array {
    if ( is_singular() ) {
        $classes[] = 'is-singular';
    }

    if ( ! is_singular() ) {
        $classes[] = 'is-archive';
    }

    return $classes;
}
add_filter( 'body_class', 'civime_body_classes' );

/**
 * Wrap oEmbed output in a responsive container.
 *
 * @param string $html  The cached HTML result, stored in post meta.
 * @return string
 */
function civime_responsive_embed_wrapper( string $html ): string {
    if ( empty( $html ) ) {
        return $html;
    }
    return '<div class="embed-responsive">' . $html . '</div>';
}
add_filter( 'embed_oembed_html', 'civime_responsive_embed_wrapper' );

/**
 * Normalize "civi.me" to title-case "Civi.Me" in displayed content and titles.
 *
 * Only affects front-end rendering — stored content is unchanged.
 *
 * @param string $text The text to filter.
 * @return string
 */
function civime_title_case_brand( string $text ): string {
    return str_replace( 'civi.me', 'Civi.Me', $text );
}
add_filter( 'the_content', 'civime_title_case_brand' );
add_filter( 'the_title', 'civime_title_case_brand' );

/**
 * Returns true when the current page should get a noindex robots tag.
 *
 * Used by civime_meta_tags() and class-hreflang.php to suppress contradictory
 * SEO signals on filtered/functional pages.
 */
function civime_is_noindex_page(): bool {
    $meeting_route = get_query_var( 'civime_route', '' );
    $notif_route   = get_query_var( 'civime_notif_route', '' );

    // Filtered meetings list (has query params beyond route vars).
    if ( 'meetings-list' === $meeting_route && ! empty( $_SERVER['QUERY_STRING'] ) ) {
        return true;
    }

    // Subscribe page (registered via notifications router as civime_notif_route=subscribe).
    if ( 'subscribe' === $notif_route ) {
        return true;
    }

    // Notification functional pages.
    if ( in_array( $notif_route, [ 'manage', 'confirmed', 'unsubscribed' ], true ) ) {
        return true;
    }

    return false;
}

/**
 * Output canonical and robots meta tags for virtual CiviMe pages.
 *
 * Priority 5: after hreflang (1) and preconnect (2), before WP core rel_canonical (10).
 */
function civime_meta_tags(): void {
    $meeting_route = get_query_var( 'civime_route', '' );
    $notif_route   = get_query_var( 'civime_notif_route', '' );
    $site_url      = 'https://civi.me';

    // --- Canonical tags ---

    if ( 'meetings-list' === $meeting_route ) {
        // All meetings list views (filtered or not) canonicalize to base /meetings/.
        echo '<link rel="canonical" href="' . esc_url( $site_url . '/meetings/' ) . '" />' . "\n";
    } elseif ( 'meeting-detail' === $meeting_route ) {
        // Self-referencing canonical for detail pages.
        $meeting_id = get_query_var( 'civime_meeting_id', '' );
        if ( '' !== $meeting_id ) {
            echo '<link rel="canonical" href="' . esc_url( $site_url . '/meetings/' . $meeting_id . '/' ) . '" />' . "\n";
        }
    }

    // --- Robots meta tags ---

    if ( 'meetings-list' === $meeting_route && ! empty( $_SERVER['QUERY_STRING'] ) ) {
        // Filtered meetings: noindex but follow links.
        echo '<meta name="robots" content="noindex,follow" />' . "\n";
    } elseif ( 'subscribe' === $notif_route ) {
        echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
    } elseif ( in_array( $notif_route, [ 'manage', 'confirmed', 'unsubscribed' ], true ) ) {
        echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
    }
}
add_action( 'wp_head', 'civime_meta_tags', 5 );

/**
 * Remove WordPress core rel_canonical() on virtual CiviMe pages.
 *
 * WP's built-in rel_canonical() (priority 10 on wp_head) outputs incorrect
 * canonicals on virtual pages because there is no real WP post object.
 * Hooks on 'wp' (not 'wp_head') so it runs before wp_head fires.
 */
function civime_remove_default_canonical(): void {
    $meeting_route = get_query_var( 'civime_route', '' );
    $notif_route   = get_query_var( 'civime_notif_route', '' );

    if ( '' !== $meeting_route || '' !== $notif_route ) {
        remove_action( 'wp_head', 'rel_canonical' );
    }
}
add_action( 'wp', 'civime_remove_default_canonical' );

/**
 * Add CiviMe-specific Disallow rules and Sitemap directive to WordPress's virtual robots.txt.
 *
 * Blocks crawlers from:
 * - Parameterized meeting filter URLs (e.g. /meetings/?council=1, /meetings/?page=2)
 * - Functional subscription and notification pages that should never be indexed
 *
 * Preserves crawlability of:
 * - Base /meetings/ listing (no query string)
 * - Individual meeting detail pages (/meetings/<id>/)
 * - Council pages (/councils/)
 *
 * @param string $output The current robots.txt output.
 * @param bool   $public Whether the site is set to public.
 * @return string
 */
function civime_robots_txt( string $output, bool $public ): string {
    $output .= "\n# CiviMe: Block parameterized meeting filter URLs\n";
    $output .= "User-agent: *\n";
    $output .= "Disallow: /meetings/*?\n";
    $output .= "Disallow: /meetings/subscribe/\n";
    $output .= "Disallow: /notifications/manage/\n";
    $output .= "Disallow: /notifications/confirmed/\n";
    $output .= "Disallow: /notifications/unsubscribed/\n";
    $output .= "\nSitemap: https://civi.me/sitemap.xml\n";
    return $output;
}
add_filter( 'robots_txt', 'civime_robots_txt', 10, 2 );
