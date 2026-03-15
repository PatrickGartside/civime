<?php
/**
 * CiviMe Guides — Content Seeder
 *
 * Creates starter guide posts from the page-content source files.
 * Run via WP-CLI: wp eval 'CiviMe_Guides_Seeder::seed();'
 *
 * @package CiviMe_Guides
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Guides_Seeder {

	/**
	 * Seed starter guides from page-content HTML files.
	 *
	 * Skips any guide whose slug already exists to prevent duplicates.
	 */
	public static function seed(): void {
		$guides = [
			[
				'title'    => 'How to Testify at a Hawaii Public Hearing',
				'slug'     => 'how-to-testify',
				'file'     => 'how-to-testify.html',
				'excerpt'  => 'A step-by-step guide for first-time testifiers at public hearings in Hawaii.',
				'category' => 'Testimony',
			],
			[
				'title'    => 'Letter Writing Kit',
				'slug'     => 'letter-writing-kit',
				'file'     => 'letter-writing-kit.html',
				'excerpt'  => 'Templates and instructions for writing to your Hawaii representatives.',
				'category' => 'Advocacy',
			],
			[
				'title'    => 'Getting Started with Civic Engagement',
				'slug'     => 'getting-started',
				'file'     => 'getting-started.html',
				'excerpt'  => 'New to civic engagement? Start here — how government works in Hawaii and where to begin.',
				'category' => 'Getting Started',
			],
			[
				'title'    => 'Voting in Hawaii',
				'slug'     => 'voting-in-hawaii',
				'file'     => 'voting-in-hawaii.html',
				'excerpt'  => 'How to register, vote by mail, and understand what\'s on your ballot in Hawaii.',
				'category' => 'Voting & Elections',
			],
			[
				'title'    => 'Attending a Government Meeting',
				'slug'     => 'attending-a-meeting',
				'file'     => 'attending-a-meeting.html',
				'excerpt'  => 'What to expect when you attend a public meeting in Hawaii — no speaking required.',
				'category' => 'Getting Started',
			],
			[
				'title'    => 'Sunshine Law Compliance Guide',
				'slug'     => 'sunshine-law',
				'file'     => 'sunshine-law.html',
				'excerpt'  => 'A practical guide to HRS Chapter 92 compliance for boards, commissions, and meeting organizers in Hawaii.',
				'category' => 'Compliance',
			],
			[
				'title'    => 'Public Records Request Guide',
				'slug'     => 'public-records',
				'file'     => 'public-records.html',
				'excerpt'  => 'How to process UIPA records requests — timelines, fees, exemptions, and compliance checklists for records officers.',
				'category' => 'Compliance',
			],
			[
				'title'    => 'Accessibility Compliance Guide',
				'slug'     => 'accessibility-compliance',
				'file'     => 'accessibility-compliance.html',
				'excerpt'  => 'Federal and Hawaii accessibility requirements for public meetings, websites, and government documents.',
				'category' => 'Compliance',
			],
			[
				'title'    => 'Public Participation Best Practices',
				'slug'     => 'public-participation',
				'file'     => 'public-participation.html',
				'excerpt'  => 'How to design meetings and processes that encourage meaningful public input — for government agencies and nonprofits.',
				'category' => 'Compliance',
			],
		];

		$content_dir = WP_CONTENT_DIR . '/page-content/';
		$created     = 0;

		foreach ( $guides as $guide ) {
			// Skip if a post with this slug already exists.
			$existing = get_page_by_path( $guide['slug'], OBJECT, 'civime_guide' );
			if ( $existing ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::log( "Skipped: {$guide['title']} (already exists)" );
				}
				continue;
			}

			$file_path = $content_dir . $guide['file'];
			if ( ! file_exists( $file_path ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::warning( "File not found: {$file_path}" );
				}
				continue;
			}

			$content = file_get_contents( $file_path );
			if ( false === $content ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::warning( "Failed to read: {$file_path}" );
				}
				continue;
			}
			$content = wp_kses_post( $content );

			$post_id = wp_insert_post( [
				'post_type'    => 'civime_guide',
				'post_title'   => $guide['title'],
				'post_name'    => $guide['slug'],
				'post_content' => $content,
				'post_excerpt' => $guide['excerpt'],
				'post_status'  => 'publish',
			], true );

			if ( is_wp_error( $post_id ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::error( "Failed to create {$guide['title']}: {$post_id->get_error_message()}", false );
				}
				continue;
			}

			// Assign category.
			$term = term_exists( $guide['category'], 'guide_category' );
			if ( ! $term ) {
				$term = wp_insert_term( $guide['category'], 'guide_category' );
			}
			if ( ! is_wp_error( $term ) ) {
				wp_set_object_terms( $post_id, (int) $term['term_id'], 'guide_category' );
			}

			$created++;
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::success( "Created: {$guide['title']} (ID: {$post_id})" );
			}
		}

		if ( defined( 'WP_CLI' ) ) {
			WP_CLI::log( "{$created} guide(s) created." );
		}
	}

	/**
	 * Seed translated guide posts for a specific locale.
	 *
	 * Looks for HTML files in wp-content/page-content/{lang}/ and creates
	 * translated guide posts with _civime_guide_lang meta.
	 *
	 * Run via WP-CLI: wp eval 'CiviMe_Guides_Seeder::seed_locale("es");'
	 *
	 * @param string $lang Language slug (e.g. 'es', 'ja').
	 */
	public static function seed_locale( string $lang ): void {
		if ( 'en' === $lang ) {
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::error( 'Use seed() for English guides.', false );
			}
			return;
		}

		if ( ! preg_match( '/^[a-z]{2,3}(-[a-z]{2,4})?$/i', $lang ) ) {
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::error( 'Invalid locale slug.', false );
			}
			return;
		}

		$guides = [
			[ 'slug' => 'how-to-testify',           'title' => 'How to Testify at a Hawaii Public Hearing', 'file' => 'how-to-testify.html',           'category' => 'Testimony' ],
			[ 'slug' => 'letter-writing-kit',        'title' => 'Letter Writing Kit',                       'file' => 'letter-writing-kit.html',        'category' => 'Advocacy' ],
			[ 'slug' => 'getting-started',           'title' => 'Getting Started with Civic Engagement',    'file' => 'getting-started.html',           'category' => 'Getting Started' ],
			[ 'slug' => 'voting-in-hawaii',          'title' => 'Voting in Hawaii',                         'file' => 'voting-in-hawaii.html',          'category' => 'Voting & Elections' ],
			[ 'slug' => 'attending-a-meeting',       'title' => 'Attending a Government Meeting',           'file' => 'attending-a-meeting.html',       'category' => 'Getting Started' ],
			[ 'slug' => 'sunshine-law',              'title' => 'Sunshine Law Compliance Guide',            'file' => 'sunshine-law.html',              'category' => 'Compliance' ],
			[ 'slug' => 'public-records',            'title' => 'Public Records Request Guide',             'file' => 'public-records.html',            'category' => 'Compliance' ],
			[ 'slug' => 'accessibility-compliance',  'title' => 'Accessibility Compliance Guide',           'file' => 'accessibility-compliance.html',  'category' => 'Compliance' ],
			[ 'slug' => 'public-participation',      'title' => 'Public Participation Best Practices',      'file' => 'public-participation.html',      'category' => 'Compliance' ],
		];

		$content_dir = WP_CONTENT_DIR . '/page-content/' . $lang . '/';
		$created     = 0;

		foreach ( $guides as $guide ) {
			$localized_slug = $guide['slug'] . '-' . $lang;

			// Skip if already exists.
			$existing = get_page_by_path( $localized_slug, OBJECT, 'civime_guide' );
			if ( $existing ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::log( "Skipped: {$guide['title']} [{$lang}] (already exists)" );
				}
				continue;
			}

			$file_path = $content_dir . $guide['file'];
			if ( ! file_exists( $file_path ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::log( "No translation: {$guide['title']} [{$lang}]" );
				}
				continue;
			}

			$content = file_get_contents( $file_path );
			if ( false === $content ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::warning( "Failed to read: {$file_path}" );
				}
				continue;
			}
			$content = wp_kses_post( $content );

			$post_id = wp_insert_post( [
				'post_type'    => 'civime_guide',
				'post_title'   => $guide['title'],
				'post_name'    => $localized_slug,
				'post_content' => $content,
				'post_status'  => 'publish',
			], true );

			if ( is_wp_error( $post_id ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::error( "Failed: {$guide['title']} [{$lang}]: {$post_id->get_error_message()}", false );
				}
				continue;
			}

			update_post_meta( $post_id, '_civime_guide_lang', $lang );
			update_post_meta( $post_id, '_civime_guide_source_slug', $guide['slug'] );

			// Assign category.
			$term = term_exists( $guide['category'], 'guide_category' );
			if ( ! $term ) {
				$term = wp_insert_term( $guide['category'], 'guide_category' );
			}
			if ( ! is_wp_error( $term ) ) {
				wp_set_object_terms( $post_id, (int) $term['term_id'], 'guide_category' );
			}

			$created++;
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::success( "Created: {$guide['title']} [{$lang}] (ID: {$post_id})" );
			}
		}

		if ( defined( 'WP_CLI' ) ) {
			WP_CLI::log( "{$created} translated guide(s) created for [{$lang}]." );
		}
	}

	/**
	 * Sync existing guide content from source HTML files.
	 *
	 * Updates post_content for guides that already exist. Does not create new guides.
	 * Run via WP-CLI: wp eval 'CiviMe_Guides_Seeder::sync();'
	 */
	public static function sync(): void {
		$guides = [
			[
				'title' => 'How to Testify at a Hawaii Public Hearing',
				'slug'  => 'how-to-testify',
				'file'  => 'how-to-testify.html',
			],
			[
				'title' => 'Letter Writing Kit',
				'slug'  => 'letter-writing-kit',
				'file'  => 'letter-writing-kit.html',
			],
			[
				'title' => 'Getting Started with Civic Engagement',
				'slug'  => 'getting-started',
				'file'  => 'getting-started.html',
			],
			[
				'title' => 'Voting in Hawaii',
				'slug'  => 'voting-in-hawaii',
				'file'  => 'voting-in-hawaii.html',
			],
			[
				'title' => 'Attending a Government Meeting',
				'slug'  => 'attending-a-meeting',
				'file'  => 'attending-a-meeting.html',
			],
			[
				'title' => 'Sunshine Law Compliance Guide',
				'slug'  => 'sunshine-law',
				'file'  => 'sunshine-law.html',
			],
			[
				'title' => 'Public Records Request Guide',
				'slug'  => 'public-records',
				'file'  => 'public-records.html',
			],
			[
				'title' => 'Accessibility Compliance Guide',
				'slug'  => 'accessibility-compliance',
				'file'  => 'accessibility-compliance.html',
			],
			[
				'title' => 'Public Participation Best Practices',
				'slug'  => 'public-participation',
				'file'  => 'public-participation.html',
			],
		];

		$content_dir = WP_CONTENT_DIR . '/page-content/';
		$updated     = 0;

		foreach ( $guides as $guide ) {
			$existing = get_page_by_path( $guide['slug'], OBJECT, 'civime_guide' );
			if ( ! $existing ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::log( "Skipped: {$guide['title']} (not found — run seed() first)" );
				}
				continue;
			}

			$file_path = $content_dir . $guide['file'];
			if ( ! file_exists( $file_path ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::warning( "File not found: {$file_path}" );
				}
				continue;
			}

			$new_content = file_get_contents( $file_path );
			if ( false === $new_content ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::warning( "Failed to read: {$file_path}" );
				}
				continue;
			}
			$new_content = wp_kses_post( $new_content );
			$old_content = $existing->post_content;

			if ( trim( $new_content ) === trim( $old_content ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::log( "No changes: {$guide['title']}" );
				}
				continue;
			}

			$result = wp_update_post( [
				'ID'           => $existing->ID,
				'post_content' => $new_content,
			], true );

			if ( is_wp_error( $result ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::error( "Failed to update {$guide['title']}: {$result->get_error_message()}", false );
				}
				continue;
			}

			$updated++;
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::success( "Updated: {$guide['title']} (ID: {$existing->ID})" );
			}
		}

		if ( defined( 'WP_CLI' ) ) {
			WP_CLI::log( "{$updated} guide(s) updated." );
		}
	}
}
