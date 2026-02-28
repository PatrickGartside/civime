<?php
/**
 * Front page template.
 *
 * Structure is defined here; content is managed via WordPress (pages, ACF, etc.)
 * Falls back gracefully when no content has been set.
 *
 * @package CiviMe
 */

get_header();

// Fetch custom front page content if a static front page is set
$front_page = get_option( 'page_on_front' ) ? get_post( get_option( 'page_on_front' ) ) : null;
?>

<main id="main" class="site-main" role="main">

    <!-- =====================================================================
         Hero Section
         ===================================================================== -->
    <section class="hero" aria-labelledby="hero-heading">
        <div class="container hero__inner">

            <div class="hero__content">
                <span class="hero__eyebrow"><?php esc_html_e( 'Built for Hawaii', 'civime' ); ?></span>

                <h1 class="hero__heading" id="hero-heading">
                    <?php
                    if ( $front_page && ! empty( get_post_meta( $front_page->ID, '_civime_hero_heading', true ) ) ) {
                        echo esc_html( get_post_meta( $front_page->ID, '_civime_hero_heading', true ) );
                    } else {
                        esc_html_e( 'Civic engagement\nfor every kamaʻāina.', 'civime' );
                    }
                    ?>
                </h1>

                <p class="hero__description">
                    <?php
                    if ( $front_page && ! empty( get_post_meta( $front_page->ID, '_civime_hero_description', true ) ) ) {
                        echo esc_html( get_post_meta( $front_page->ID, '_civime_hero_description', true ) );
                    } else {
                        esc_html_e(
                            'Government information shouldn\'t require a law degree to navigate. Civi.Me makes it simple to stay informed, show up, and have your voice heard — in plain language, for free.',
                            'civime'
                        );
                    }
                    ?>
                </p>

                <div class="hero__actions">
                    <a href="<?php echo esc_url( home_url( '/what-matters/' ) ); ?>" class="btn btn--primary btn--lg">
                        <?php esc_html_e( 'What matters to you?', 'civime' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--secondary btn--lg">
                        <?php esc_html_e( 'Browse meetings', 'civime' ); ?>
                    </a>
                </div>
            </div>

            <div class="hero__illustration" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 320" fill="none">
                    <!-- Capitol / civic building silhouette -->
                    <rect x="120" y="160" width="160" height="120" rx="4" fill="rgba(255,255,255,0.12)"/>
                    <rect x="140" y="180" width="24" height="40" rx="2" fill="rgba(255,255,255,0.18)"/>
                    <rect x="188" y="180" width="24" height="40" rx="2" fill="rgba(255,255,255,0.18)"/>
                    <rect x="236" y="180" width="24" height="40" rx="2" fill="rgba(255,255,255,0.18)"/>
                    <rect x="140" y="240" width="24" height="40" rx="2" fill="rgba(255,255,255,0.18)"/>
                    <rect x="188" y="240" width="24" height="40" rx="2" fill="rgba(255,255,255,0.18)"/>
                    <rect x="236" y="240" width="24" height="40" rx="2" fill="rgba(255,255,255,0.18)"/>
                    <!-- Dome -->
                    <path d="M160 160 Q200 80 240 160" fill="rgba(255,255,255,0.10)" stroke="rgba(255,255,255,0.20)" stroke-width="2"/>
                    <rect x="190" y="100" width="20" height="60" rx="4" fill="rgba(255,255,255,0.15)"/>
                    <circle cx="200" cy="92" r="12" fill="rgba(255,255,255,0.20)"/>
                    <!-- Pillars -->
                    <rect x="130" y="148" width="8" height="132" rx="2" fill="rgba(255,255,255,0.20)"/>
                    <rect x="160" y="148" width="8" height="132" rx="2" fill="rgba(255,255,255,0.15)"/>
                    <rect x="232" y="148" width="8" height="132" rx="2" fill="rgba(255,255,255,0.15)"/>
                    <rect x="262" y="148" width="8" height="132" rx="2" fill="rgba(255,255,255,0.20)"/>
                    <!-- Pediment -->
                    <polygon points="120,160 200,120 280,160" fill="rgba(255,255,255,0.14)" stroke="rgba(255,255,255,0.22)" stroke-width="1.5"/>
                    <!-- Steps -->
                    <rect x="100" y="280" width="200" height="8" rx="2" fill="rgba(255,255,255,0.10)"/>
                    <rect x="90" y="288" width="220" height="8" rx="2" fill="rgba(255,255,255,0.08)"/>
                    <rect x="80" y="296" width="240" height="8" rx="2" fill="rgba(255,255,255,0.06)"/>
                    <!-- People silhouettes -->
                    <circle cx="100" cy="262" r="8" fill="rgba(255,255,255,0.18)"/>
                    <rect x="94" y="270" width="12" height="16" rx="4" fill="rgba(255,255,255,0.14)"/>
                    <circle cx="310" cy="258" r="9" fill="rgba(255,255,255,0.16)"/>
                    <rect x="303" y="267" width="14" height="18" rx="5" fill="rgba(255,255,255,0.12)"/>
                    <circle cx="340" cy="264" r="7" fill="rgba(255,255,255,0.14)"/>
                    <rect x="335" y="271" width="10" height="14" rx="4" fill="rgba(255,255,255,0.10)"/>
                    <!-- Palm tree accent -->
                    <rect x="52" y="230" width="6" height="76" rx="3" fill="rgba(255,255,255,0.12)"/>
                    <path d="M55 230 Q30 200 15 210" stroke="rgba(255,255,255,0.16)" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <path d="M55 230 Q40 195 25 198" stroke="rgba(255,255,255,0.14)" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <path d="M55 230 Q70 195 85 200" stroke="rgba(255,255,255,0.16)" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <path d="M55 230 Q75 205 90 215" stroke="rgba(255,255,255,0.12)" stroke-width="3" fill="none" stroke-linecap="round"/>
                </svg>
            </div>

        </div>
    </section>

    <!-- =====================================================================
         The Problem Section
         ===================================================================== -->
    <section class="section" aria-labelledby="problem-heading">
        <div class="container">

                <div class="section-header section-header--centered">
                    <span class="section-eyebrow"><?php esc_html_e( 'The challenge', 'civime' ); ?></span>
                    <h2 class="section-title" id="problem-heading">
                        <?php esc_html_e( 'Civic participation is functionally inaccessible', 'civime' ); ?>
                    </h2>
                    <p class="section-description">
                        <?php esc_html_e(
                            'Most people want to be engaged — but the barriers make it feel impossible. We\'re removing them.',
                            'civime'
                        ); ?>
                    </p>
                </div>

                <ul class="problem-grid" role="list">

                    <li class="problem-item">
                        <div class="problem-item__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <div class="problem-item__content">
                            <h3><?php esc_html_e( 'Buried information', 'civime' ); ?></h3>
                            <p><?php esc_html_e( 'Meeting agendas are buried in PDFs across dozens of county and state websites with no consistent format.', 'civime' ); ?></p>
                        </div>
                    </li>

                    <li class="problem-item">
                        <div class="problem-item__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                        </div>
                        <div class="problem-item__content">
                            <h3><?php esc_html_e( 'No timely alerts', 'civime' ); ?></h3>
                            <p><?php esc_html_e( 'Residents find out about decisions that affect their neighborhoods after the fact — if at all.', 'civime' ); ?></p>
                        </div>
                    </li>

                    <li class="problem-item">
                        <div class="problem-item__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10,9 9,9 8,9"/>
                            </svg>
                        </div>
                        <div class="problem-item__content">
                            <h3><?php esc_html_e( 'Bureaucratic language', 'civime' ); ?></h3>
                            <p><?php esc_html_e( 'Government documents are written for lawyers, not residents. Plain-language summaries are rare.', 'civime' ); ?></p>
                        </div>
                    </li>

                    <li class="problem-item">
                        <div class="problem-item__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <div class="problem-item__content">
                            <h3><?php esc_html_e( 'Who are my representatives?', 'civime' ); ?></h3>
                            <p><?php esc_html_e( 'Most residents can\'t name their city council member, let alone their neighborhood board chair.', 'civime' ); ?></p>
                        </div>
                    </li>

                </ul>

        </div>
    </section>

    <!-- =====================================================================
         What's Coming Section
         ===================================================================== -->
    <!-- =====================================================================
         What Matters to Me — Topic Picker Teaser
         ===================================================================== -->
    <section class="section section--surface" id="whats-coming" aria-labelledby="topics-heading">
        <div class="container">

            <div class="section-header section-header--centered">
                <span class="section-eyebrow"><?php esc_html_e( 'Start here', 'civime' ); ?></span>
                <h2 class="section-title" id="topics-heading">
                    <?php esc_html_e( 'Tell us what matters to you', 'civime' ); ?>
                </h2>
                <p class="section-description">
                    <?php esc_html_e(
                        'Pick the topics you care about — environment, housing, education, health — and we\'ll show you the government meetings and councils that handle those issues.',
                        'civime'
                    ); ?>
                </p>
            </div>

            <div class="front-topics-preview">
                <?php
                // All 16 topics
                $topic_preview = [
                    [ 'icon' => "\xF0\x9F\x8C\xBF",             'name' => __( 'Environment & Land', 'civime' ),      'slug' => 'environment',    'label' => __( 'Leaf', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x8F\xA0",             'name' => __( 'Housing & Development', 'civime' ),    'slug' => 'housing',        'label' => __( 'House', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x93\x9A",             'name' => __( 'Education', 'civime' ),                'slug' => 'education',      'label' => __( 'Books', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x8F\xA5",             'name' => __( 'Health & Wellness', 'civime' ),        'slug' => 'health',         'label' => __( 'Hospital', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x9A\x8C",             'name' => __( 'Transportation', 'civime' ),           'slug' => 'transportation', 'label' => __( 'Bus', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x9B\xA1\xEF\xB8\x8F", 'name' => __( 'Public Safety', 'civime' ),           'slug' => 'public-safety',  'label' => __( 'Shield', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x92\xBC",             'name' => __( 'Economy & Labor', 'civime' ),          'slug' => 'economy',        'label' => __( 'Briefcase', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x8E\xAD",             'name' => __( 'Culture & Arts', 'civime' ),           'slug' => 'culture',        'label' => __( 'Performing arts', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x8C\xBE",             'name' => __( 'Agriculture & Food', 'civime' ),       'slug' => 'agriculture',    'label' => __( 'Sheaf of rice', 'civime' ) ],
                    [ 'icon' => "\xE2\x9A\xA1",                 'name' => __( 'Energy & Utilities', 'civime' ),       'slug' => 'energy',         'label' => __( 'Lightning bolt', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x8C\x8A",             'name' => __( 'Water & Ocean', 'civime' ),            'slug' => 'water',          'label' => __( 'Ocean wave', 'civime' ) ],
                    [ 'icon' => "\xE2\x99\xBF",                 'name' => __( 'Disability & Access', 'civime' ),      'slug' => 'disability',     'label' => __( 'Wheelchair symbol', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x8E\x96\xEF\xB8\x8F", 'name' => __( 'Veterans & Military', 'civime' ),     'slug' => 'veterans',       'label' => __( 'Military medal', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x92\xBB",             'name' => __( 'Technology & Innovation', 'civime' ),  'slug' => 'technology',     'label' => __( 'Laptop', 'civime' ) ],
                    [ 'icon' => "\xF0\x9F\x93\x8A",             'name' => __( 'Budget & Finance', 'civime' ),         'slug' => 'budget',         'label' => __( 'Bar chart', 'civime' ) ],
                    [ 'icon' => "\xE2\x9A\x96\xEF\xB8\x8F",     'name' => __( 'Governance & Ethics', 'civime' ),     'slug' => 'governance',     'label' => __( 'Balance scale', 'civime' ) ],
                ];
                ?>
                <div class="front-topics-grid">
                    <?php foreach ( $topic_preview as $topic ) : ?>
                        <a
                            href="<?php echo esc_url( home_url( '/topics/' . $topic['slug'] . '/' ) ); ?>"
                            class="front-topic-chip"
                        >
                            <span class="front-topic-chip__icon" role="img" aria-label="<?php echo esc_attr( $topic['label'] ); ?>"><?php echo esc_html( $topic['icon'] ); ?></span>
                            <span class="front-topic-chip__name"><?php echo esc_html( $topic['name'] ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="front-topics-cta">
                    <a href="<?php echo esc_url( home_url( '/what-matters/' ) ); ?>" class="btn btn--primary btn--lg">
                        <?php esc_html_e( 'Pick Your Topics', 'civime' ); ?>
                    </a>
                    <p class="front-topics-note">
                        <?php esc_html_e( 'Pick the topics you care about and we\'ll show you the meetings that matter.', 'civime' ); ?>
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- =====================================================================
         Features Section
         ===================================================================== -->
    <section class="section" aria-labelledby="features-heading">
        <div class="container">

            <div class="section-header section-header--centered">
                <span class="section-eyebrow"><?php esc_html_e( 'Civic tools', 'civime' ); ?></span>
                <h2 class="section-title" id="features-heading">
                    <?php esc_html_e( 'What you can do on Civi.Me', 'civime' ); ?>
                </h2>
            </div>

            <div class="card-grid card-grid--3">

                <!-- Meetings card — LIVE -->
                <a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="card card--linked" aria-labelledby="card-meetings-title">
                    <div class="card__image" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 140" fill="none">
                            <rect width="400" height="140" fill="var(--color-bg)"/>
                            <!-- Calendar grid -->
                            <rect x="100" y="20" width="200" height="100" rx="8" fill="var(--color-surface)" stroke="var(--color-border)" stroke-width="1.5"/>
                            <rect x="100" y="20" width="200" height="28" rx="8" fill="var(--color-primary)" opacity="0.15"/>
                            <rect x="100" y="44" width="200" height="4" rx="0" fill="var(--color-border)" opacity="0.5"/>
                            <!-- Calendar dots / cells -->
                            <circle cx="130" cy="68" r="6" fill="var(--color-border)"/>
                            <circle cx="160" cy="68" r="6" fill="var(--color-border)"/>
                            <circle cx="190" cy="68" r="6" fill="var(--color-primary)" opacity="0.6"/>
                            <circle cx="220" cy="68" r="6" fill="var(--color-border)"/>
                            <circle cx="250" cy="68" r="6" fill="var(--color-border)"/>
                            <circle cx="270" cy="68" r="6" fill="var(--color-border)"/>
                            <circle cx="130" cy="92" r="6" fill="var(--color-border)"/>
                            <circle cx="160" cy="92" r="6" fill="var(--color-border)"/>
                            <circle cx="190" cy="92" r="6" fill="var(--color-border)"/>
                            <circle cx="220" cy="92" r="6" fill="var(--color-accent)" opacity="0.5"/>
                            <circle cx="250" cy="92" r="6" fill="var(--color-border)"/>
                            <circle cx="270" cy="92" r="6" fill="var(--color-border)"/>
                            <!-- Pin markers -->
                            <circle cx="140" cy="14" r="4" fill="var(--color-primary)" opacity="0.4"/>
                            <circle cx="260" cy="14" r="4" fill="var(--color-primary)" opacity="0.4"/>
                        </svg>
                    </div>
                    <div class="card__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <span class="card__tag card__tag--live"><?php esc_html_e( 'Live', 'civime' ); ?></span>
                    <h3 class="card__title" id="card-meetings-title">
                        <?php esc_html_e( 'Browse Meetings', 'civime' ); ?>
                    </h3>
                    <div class="card__body">
                        <p><?php esc_html_e(
                            'All public meetings — boards, commissions, and councils — in one calendar. Filter by topic, council, or date. AI-generated summaries explain what\'s on the agenda.',
                            'civime'
                        ); ?></p>
                    </div>
                </a>

                <!-- Council Profiles card — LIVE -->
                <a href="<?php echo esc_url( home_url( '/councils/' ) ); ?>" class="card card--linked" aria-labelledby="card-councils-title">
                    <div class="card__image" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 140" fill="none">
                            <rect width="400" height="140" fill="var(--color-bg)"/>
                            <!-- People silhouettes -->
                            <circle cx="140" cy="52" r="18" fill="var(--color-primary)" opacity="0.15"/>
                            <rect x="126" y="74" width="28" height="34" rx="10" fill="var(--color-primary)" opacity="0.12"/>
                            <circle cx="200" cy="46" r="22" fill="var(--color-primary)" opacity="0.20"/>
                            <rect x="183" y="72" width="34" height="40" rx="12" fill="var(--color-primary)" opacity="0.16"/>
                            <circle cx="260" cy="52" r="18" fill="var(--color-primary)" opacity="0.15"/>
                            <rect x="246" y="74" width="28" height="34" rx="10" fill="var(--color-primary)" opacity="0.12"/>
                            <!-- Connecting lines -->
                            <line x1="158" y1="60" x2="178" y2="54" stroke="var(--color-primary)" stroke-width="1.5" opacity="0.15"/>
                            <line x1="222" y1="54" x2="242" y2="60" stroke="var(--color-primary)" stroke-width="1.5" opacity="0.15"/>
                            <!-- Small accent dots -->
                            <circle cx="100" cy="80" r="8" fill="var(--color-secondary)" opacity="0.12"/>
                            <circle cx="300" cy="80" r="8" fill="var(--color-secondary)" opacity="0.12"/>
                        </svg>
                    </div>
                    <div class="card__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <span class="card__tag card__tag--live"><?php esc_html_e( 'Live', 'civime' ); ?></span>
                    <h3 class="card__title" id="card-councils-title">
                        <?php esc_html_e( 'Council Profiles', 'civime' ); ?>
                    </h3>
                    <div class="card__body">
                        <p><?php esc_html_e(
                            'Learn what each board and commission actually does — in plain language. See who sits on the board, upcoming meetings, open seats, and how to participate.',
                            'civime'
                        ); ?></p>
                    </div>
                </a>

                <!-- Notifications card — LIVE -->
                <a href="<?php echo esc_url( home_url( '/meetings/subscribe/' ) ); ?>" class="card card--linked" aria-labelledby="card-notifications-title">
                    <div class="card__image" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 140" fill="none">
                            <rect width="400" height="140" fill="var(--color-bg)"/>
                            <!-- Bell shape -->
                            <path d="M200 28 C200 28 170 40 170 72 L170 88 L230 88 L230 72 C230 40 200 28 200 28Z" fill="var(--color-accent)" opacity="0.15" stroke="var(--color-accent)" stroke-width="1.5" opacity="0.25"/>
                            <rect x="164" y="88" width="72" height="8" rx="4" fill="var(--color-accent)" opacity="0.18"/>
                            <circle cx="200" cy="104" r="6" fill="var(--color-accent)" opacity="0.20"/>
                            <rect x="197" y="16" width="6" height="14" rx="3" fill="var(--color-accent)" opacity="0.25"/>
                            <!-- Sound waves -->
                            <path d="M238 58 Q252 70 238 82" stroke="var(--color-accent)" stroke-width="2" fill="none" opacity="0.15" stroke-linecap="round"/>
                            <path d="M248 50 Q268 70 248 90" stroke="var(--color-accent)" stroke-width="2" fill="none" opacity="0.10" stroke-linecap="round"/>
                            <path d="M162 58 Q148 70 162 82" stroke="var(--color-accent)" stroke-width="2" fill="none" opacity="0.15" stroke-linecap="round"/>
                            <path d="M152 50 Q132 70 152 90" stroke="var(--color-accent)" stroke-width="2" fill="none" opacity="0.10" stroke-linecap="round"/>
                            <!-- Envelope hints -->
                            <rect x="80" y="90" width="40" height="28" rx="4" fill="var(--color-primary)" opacity="0.08"/>
                            <path d="M84 94 L100 106 L116 94" stroke="var(--color-primary)" stroke-width="1.5" fill="none" opacity="0.12"/>
                            <rect x="280" y="90" width="40" height="28" rx="4" fill="var(--color-primary)" opacity="0.08"/>
                            <path d="M284 94 L300 106 L316 94" stroke="var(--color-primary)" stroke-width="1.5" fill="none" opacity="0.12"/>
                        </svg>
                    </div>
                    <div class="card__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                    </div>
                    <span class="card__tag card__tag--live"><?php esc_html_e( 'Live', 'civime' ); ?></span>
                    <h3 class="card__title" id="card-notifications-title">
                        <?php esc_html_e( 'Get Alerts', 'civime' ); ?>
                    </h3>
                    <div class="card__body">
                        <p><?php esc_html_e(
                            'Subscribe to topics or specific councils. Get email alerts when meetings are scheduled — before it\'s too late to participate.',
                            'civime'
                        ); ?></p>
                    </div>
                </a>

            </div>

        </div>
    </section>

    <!-- =====================================================================
         CTA Section
         ===================================================================== -->
    <section class="cta-section section--primary section" aria-labelledby="cta-heading">
        <div class="container cta-section__inner">

            <span class="section-eyebrow"><?php esc_html_e( 'Join us', 'civime' ); ?></span>
            <h2 class="section-title" id="cta-heading">
                <?php esc_html_e( 'Help build civic infrastructure for Hawaii', 'civime' ); ?>
            </h2>
            <p class="section-description">
                <?php esc_html_e(
                    'Civi.Me is open source and community-driven. Whether you\'re a developer, a civic advocate, or just someone who cares — there\'s a place for you.',
                    'civime'
                ); ?>
            </p>

            <div class="cta-section__actions">
                <a
                    href="https://github.com/patrickgartside/civi.me"
                    class="btn btn--primary btn--lg"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?php esc_html_e( 'View on GitHub', 'civime' ); ?>
                </a>
                <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn btn--secondary btn--lg">
                    <?php esc_html_e( 'Get in touch', 'civime' ); ?>
                </a>
            </div>

        </div>
    </section>

    <?php
    // If a static front page has additional block/classic content, output it
    if ( $front_page && have_posts() ) :
        while ( have_posts() ) :
            the_post();
            $page_content = get_the_content();
            if ( ! empty( trim( $page_content ) ) ) :
                ?>
                <section class="section">
                    <div class="container">
                        <div class="prose">
                            <?php the_content(); ?>
                        </div>
                    </div>
                </section>
                <?php
            endif;
        endwhile;
    endif;
    ?>

</main>

<?php
get_footer();
