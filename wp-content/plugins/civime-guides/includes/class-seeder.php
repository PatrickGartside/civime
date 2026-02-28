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
}
