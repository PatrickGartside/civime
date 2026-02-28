<?php
/**
 * CiviMe Notifications Shortcodes
 *
 * [civime_subscribe_cta] — renders a compact call-to-action card linking
 * to the subscribe page. Accepts optional council_id to pre-select a council.
 *
 * @package CiviMe_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'civime_subscribe_cta', 'civime_subscribe_cta_shortcode' );

/**
 * Render a subscribe CTA block.
 *
 * Attributes:
 *   council_id  — Pre-select a council in the subscribe form.
 *   title       — CTA heading (default: "Get Notified").
 *   description — CTA body text.
 *   button_text — Button label (default: "Subscribe").
 *
 * @param array|string $atts Shortcode attributes.
 * @return string HTML output.
 */
function civime_subscribe_cta_shortcode( $atts ): string {
	$atts = shortcode_atts(
		[
			'council_id'  => 0,
			'title'       => __( 'Get Notified', 'civime-notifications' ),
			'description' => __( 'Get alerts when new meetings or agendas are posted for the councils you care about.', 'civime-notifications' ),
			'button_text' => __( 'Subscribe', 'civime-notifications' ),
		],
		$atts,
		'civime_subscribe_cta'
	);

	$council_id  = absint( $atts['council_id'] );
	$subscribe_url = home_url( '/meetings/subscribe/' );

	if ( $council_id > 0 ) {
		$subscribe_url = add_query_arg( 'council_id', $council_id, $subscribe_url );
	}

	ob_start();
	?>
	<aside class="civime-subscribe-cta">
		<h2 class="civime-subscribe-cta__title"><?php echo esc_html( $atts['title'] ); ?></h2>
		<p class="civime-subscribe-cta__description"><?php echo esc_html( $atts['description'] ); ?></p>
		<a href="<?php echo esc_url( $subscribe_url ); ?>" class="btn btn--primary">
			<?php echo esc_html( $atts['button_text'] ); ?>
		</a>
	</aside>
	<?php
	return ob_get_clean();
}
