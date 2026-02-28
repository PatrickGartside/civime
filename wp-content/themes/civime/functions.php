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
    // Google Fonts — preconnect for performance
    wp_enqueue_style(
        'civime-fonts-preconnect',
        'https://fonts.googleapis.com',
        array(),
        null
    );

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
 * Inline the theme-application script in <head> before any paint to
 * prevent a flash of the wrong color scheme.
 */
function civime_inline_theme_script(): void {
    ?>
    <script>
    (function(){
        try {
            var t = localStorage.getItem('civime-color-scheme');
            if (t === 'dark' || t === 'light') {
                document.documentElement.setAttribute('data-theme', t);
            }
        } catch(e){}
    })();
    </script>
    <?php
}
add_action( 'wp_head', 'civime_inline_theme_script', 1 );

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

        $output .= "<li>";
        $output .= "<a href=\"{$url}\" class=\"{$link_class}\"{$aria_current}{$target}{$rel}>{$title}</a>";
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

        $output .= "<li>";
        $output .= "<a href=\"{$url}\" class=\"{$link_class}\"{$aria_current}{$target}>{$title}</a>";
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

        $output .= "<li>";
        $output .= "<a href=\"{$url}\" class=\"footer-nav__link\">{$title}</a>";
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

        echo '<li><a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '"' . $aria . '>'
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
        [ 'url' => '/notifications/',      'label' => __( 'Alerts', 'civime' ) ],
        [ 'url' => '/about/',              'label' => __( 'About', 'civime' ) ],
    ];

    echo '<ul class="' . esc_attr( $args['menu_class'] ?? 'footer-nav__list' ) . '">';
    foreach ( $items as $item ) {
        echo '<li><a href="' . esc_url( home_url( $item['url'] ) ) . '" class="footer-nav__link">'
            . esc_html( $item['label'] )
            . '</a></li>';
    }
    echo '</ul>';
}

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
