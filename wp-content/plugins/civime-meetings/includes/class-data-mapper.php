<?php
/**
 * Data Mapper
 *
 * Translates Access100 API response shapes into the flat field names
 * that the plugin templates expect. Keeps all field-renaming logic in
 * one place so templates and controllers stay unchanged when the API
 * evolves.
 *
 * @package CiviMe_Meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Meetings_Data_Mapper {

	/**
	 * Map a single meeting item from an API list response.
	 *
	 * Flattens nested council, renames date/time fields, and sets
	 * safe defaults for fields the API doesn't provide.
	 */
	public static function map_meeting_list_item( array $item ): array {
		$item['date']         = $item['meeting_date'] ?? '';
		$item['time']         = $item['meeting_time'] ?? '';
		$item['council_id']   = $item['council']['id'] ?? ( $item['council_id'] ?? 0 );
		$item['council_name'] = $item['council']['name'] ?? ( $item['council_name'] ?? '' );
		$item['has_summary']  = ! empty( $item['summary_text'] );

		return $item;
	}

	/**
	 * Map a single meeting from an API detail response.
	 *
	 * Applies the same list-item mappings, plus detail-specific renames
	 * (zoom, notice URL, agenda text, attachments).
	 */
	public static function map_meeting_detail( array $item ): array {
		$item = self::map_meeting_list_item( $item );

		$item['zoom_url']    = $item['zoom_link'] ?? '';
		$item['notice_url']  = $item['detail_url'] ?? '';
		$item['agenda_text'] = $item['description'] ?? '';

		// Derive agenda_url from the first PDF attachment, if any.
		$item['agenda_url'] = '';
		if ( ! empty( $item['attachments'] ) && is_array( $item['attachments'] ) ) {
			foreach ( $item['attachments'] as $att ) {
				$url = $att['file_url'] ?? $att['url'] ?? '';
				if ( '' !== $url && str_ends_with( strtolower( $url ), '.pdf' ) ) {
					$item['agenda_url'] = $url;
					break;
				}
			}

			// Remap attachment sub-fields.
			$item['attachments'] = array_map( function ( array $att ): array {
				$att['url']  = $att['file_url'] ?? ( $att['url'] ?? '' );
				$att['name'] = $att['file_name'] ?? ( $att['name'] ?? '' );
				return $att;
			}, $item['attachments'] );
		}

		// Fields the templates reference but the API doesn't provide.
		$item['end_time'] = $item['end_time'] ?? '';
		$item['address']  = $item['address'] ?? '';

		// Pass through topics data from API response.
		$item['topics'] = $item['topics'] ?? [ 'direct' => [], 'inherited' => [] ];

		return $item;
	}

	/**
	 * Map a single council from an API response.
	 *
	 * Renames upcoming_meeting_count → meeting_count and sets safe
	 * defaults for county and next_meeting_date.
	 */
	public static function map_council( array $item ): array {
		$item['meeting_count']    = $item['upcoming_meeting_count'] ?? ( $item['meeting_count'] ?? 0 );
		$item['county']           = $item['county'] ?? '';
		$item['next_meeting_date'] = $item['next_meeting_date'] ?? '';

		return $item;
	}
}
