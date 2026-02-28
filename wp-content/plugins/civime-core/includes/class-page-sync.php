<?php
/**
 * CiviMe Core — Page Content Sync
 *
 * Syncs WordPress page content from source HTML files in wp-content/page-content/.
 * Run via WP-CLI: wp eval 'CiviMe_Page_Sync::sync();'
 *
 * Only updates pages that already exist (matched by slug). Does not create new pages.
 *
 * @package CiviMe_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Page_Sync {

	/**
	 * Map of page slugs to their source files and expected parent slug (if any).
	 *
	 * @return array<string, array{file: string, parent?: string}>
	 */
	private static function get_page_map(): array {
		return [
			'ambassador-toolkit' => [
				'file'   => 'ambassador-toolkit.html',
				'parent' => 'get-involved',
			],
			'about'              => [ 'file' => 'about.html' ],
			'contact'            => [ 'file' => 'contact.html' ],
			'get-involved'       => [ 'file' => 'get-involved.html' ],
			'privacy-policy'     => [ 'file' => 'privacy-policy.html' ],
		];
	}

	/**
	 * Sync all mapped pages from their source files.
	 */
	public static function sync(): void {
		$content_dir = WP_CONTENT_DIR . '/page-content/';
		$pages       = self::get_page_map();
		$updated     = 0;

		foreach ( $pages as $slug => $config ) {
			$file_path = $content_dir . $config['file'];

			if ( ! file_exists( $file_path ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::warning( "Source file not found: {$file_path}" );
				}
				continue;
			}

			// Build the full path for nested pages (e.g., get-involved/ambassador-toolkit).
			$full_path = isset( $config['parent'] ) ? $config['parent'] . '/' . $slug : $slug;
			$page      = get_page_by_path( $full_path );

			if ( ! $page ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::log( "Skipped: /{$full_path}/ (page not found in WP — create it manually first)" );
				}
				continue;
			}

			$new_content = file_get_contents( $file_path );
			$old_content = $page->post_content;

			if ( trim( $new_content ) === trim( $old_content ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::log( "No changes: /{$full_path}/" );
				}
				continue;
			}

			$result = wp_update_post( [
				'ID'           => $page->ID,
				'post_content' => $new_content,
			], true );

			if ( is_wp_error( $result ) ) {
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::error( "Failed to update /{$full_path}/: {$result->get_error_message()}", false );
				}
				continue;
			}

			$updated++;
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::success( "Updated: /{$full_path}/ (ID: {$page->ID})" );
			}
		}

		if ( defined( 'WP_CLI' ) ) {
			WP_CLI::log( "{$updated} page(s) updated." );
		}
	}
}
