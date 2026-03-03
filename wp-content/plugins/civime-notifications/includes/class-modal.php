<?php
/**
 * Modal Renderer
 *
 * Outputs the "Notify Me" modal dialog markup via wp_footer, but only on
 * meeting detail pages. The modal is hidden by default and opened by
 * JavaScript when the user clicks a "Get Notified" trigger.
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Notifications_Modal {

	public function __construct() {
		add_action( 'wp_footer', [ $this, 'render' ] );
	}

	/**
	 * Render the modal template on meeting-detail pages only.
	 */
	public function render(): void {
		if ( 'meeting-detail' !== get_query_var( 'civime_route' ) ) {
			return;
		}

		include CIVIME_NOTIFICATIONS_PATH . 'templates/modal-notify.php';
	}
}
