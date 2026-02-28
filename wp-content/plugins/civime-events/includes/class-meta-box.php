<?php
/**
 * CiviMe Events — Admin Meta Box
 *
 * @package CiviMe_Events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Events_Meta_Box {

	private const NONCE_ACTION = 'civime_event_meta_save';
	private const NONCE_NAME   = '_civime_event_nonce';

	/** @var string[] */
	private const VALID_ISLANDS = [
		'oahu'          => 'O&#x02BB;ahu',
		'maui'          => 'Maui',
		'hawaii-island' => 'Hawai&#x02BB;i Island',
		'kauai'         => 'Kaua&#x02BB;i',
		'online'        => 'Online',
	];

	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_civime_event', [ $this, 'save' ], 10, 2 );
	}

	public function add_meta_box(): void {
		add_meta_box(
			'civime_event_details',
			__( 'Event Details', 'civime-events' ),
			[ $this, 'render' ],
			'civime_event',
			'normal',
			'high'
		);
	}

	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$date         = get_post_meta( $post->ID, '_civime_event_date', true );
		$time         = get_post_meta( $post->ID, '_civime_event_time', true );
		$end_time     = get_post_meta( $post->ID, '_civime_event_end_time', true );
		$location     = get_post_meta( $post->ID, '_civime_event_location', true );
		$island       = get_post_meta( $post->ID, '_civime_event_island', true );
		$url          = get_post_meta( $post->ID, '_civime_event_url', true );
		$registration = get_post_meta( $post->ID, '_civime_event_registration_required', true );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="civime-event-date"><?php esc_html_e( 'Date', 'civime-events' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<input type="date" id="civime-event-date" name="_civime_event_date" value="<?php echo esc_attr( $date ); ?>" required>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="civime-event-time"><?php esc_html_e( 'Start Time', 'civime-events' ); ?></label>
				</th>
				<td>
					<input type="time" id="civime-event-time" name="_civime_event_time" value="<?php echo esc_attr( $time ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="civime-event-end-time"><?php esc_html_e( 'End Time', 'civime-events' ); ?></label>
				</th>
				<td>
					<input type="time" id="civime-event-end-time" name="_civime_event_end_time" value="<?php echo esc_attr( $end_time ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="civime-event-location"><?php esc_html_e( 'Location', 'civime-events' ); ?></label>
				</th>
				<td>
					<input type="text" id="civime-event-location" name="_civime_event_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="civime-event-island"><?php esc_html_e( 'Island', 'civime-events' ); ?></label>
				</th>
				<td>
					<select id="civime-event-island" name="_civime_event_island">
						<option value=""><?php esc_html_e( '— Select —', 'civime-events' ); ?></option>
						<?php foreach ( self::VALID_ISLANDS as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $island, $value ); ?>>
								<?php echo $label; // Already contains HTML entities for ʻokina. ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="civime-event-url"><?php esc_html_e( 'Event URL', 'civime-events' ); ?></label>
				</th>
				<td>
					<input type="url" id="civime-event-url" name="_civime_event_url" value="<?php echo esc_url( $url ); ?>" class="regular-text" placeholder="https://">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Registration Required', 'civime-events' ); ?>
				</th>
				<td>
					<label for="civime-event-registration">
						<input type="checkbox" id="civime-event-registration" name="_civime_event_registration_required" value="1" <?php checked( $registration, '1' ); ?>>
						<?php esc_html_e( 'Attendees must register in advance', 'civime-events' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save( int $post_id, WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Date — required, validate format.
		$date_raw = isset( $_POST['_civime_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['_civime_event_date'] ) ) : '';
		$date_obj = DateTime::createFromFormat( 'Y-m-d', $date_raw );
		if ( $date_obj && $date_obj->format( 'Y-m-d' ) === $date_raw ) {
			update_post_meta( $post_id, '_civime_event_date', $date_raw );
		} else {
			delete_post_meta( $post_id, '_civime_event_date' );
		}

		// Time fields — validate H:i format.
		foreach ( [ '_civime_event_time', '_civime_event_end_time' ] as $time_key ) {
			$time_raw = isset( $_POST[ $time_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $time_key ] ) ) : '';
			if ( '' !== $time_raw && preg_match( '/^\d{2}:\d{2}$/', $time_raw ) ) {
				update_post_meta( $post_id, $time_key, $time_raw );
			} else {
				delete_post_meta( $post_id, $time_key );
			}
		}

		// Location — plain text.
		$location = isset( $_POST['_civime_event_location'] ) ? sanitize_text_field( wp_unslash( $_POST['_civime_event_location'] ) ) : '';
		update_post_meta( $post_id, '_civime_event_location', $location );

		// Island — whitelist.
		$island = isset( $_POST['_civime_event_island'] ) ? sanitize_text_field( wp_unslash( $_POST['_civime_event_island'] ) ) : '';
		if ( isset( self::VALID_ISLANDS[ $island ] ) ) {
			update_post_meta( $post_id, '_civime_event_island', $island );
		} else {
			delete_post_meta( $post_id, '_civime_event_island' );
		}

		// URL.
		$url = isset( $_POST['_civime_event_url'] ) ? esc_url_raw( wp_unslash( $_POST['_civime_event_url'] ) ) : '';
		update_post_meta( $post_id, '_civime_event_url', $url );

		// Registration required — boolean.
		$registration = isset( $_POST['_civime_event_registration_required'] ) ? '1' : '0';
		update_post_meta( $post_id, '_civime_event_registration_required', $registration );
	}
}
