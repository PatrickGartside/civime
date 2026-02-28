<?php
/**
 * CiviMe Topics Picker
 *
 * Registers the [civime_topic_picker] shortcode and provides a reusable
 * "Your Topics" bar component for cross-page topic awareness.
 *
 * @package CiviMe_Topics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_Topics_Picker {

	public function __construct() {
		add_shortcode( 'civime_topic_picker', [ $this, 'render_shortcode' ] );
		add_shortcode( 'civime_your_topics', [ $this, 'render_your_topics_bar' ] );
	}

	/**
	 * Render the full topic picker grid.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( array $atts = [] ): string {
		$api    = civime_api();
		$result = $api->get_topics();

		if ( is_wp_error( $result ) ) {
			return '<div class="meetings-notice meetings-notice--warning" role="alert"><p>'
				. esc_html__( 'Topics are temporarily unavailable.', 'civime-topics' )
				. '</p></div>';
		}

		$topics = $result['data'] ?? [];

		if ( empty( $topics ) ) {
			return '<p>' . esc_html__( 'No topics available.', 'civime-topics' ) . '</p>';
		}

		ob_start();
		?>
		<div class="topic-picker" role="group" aria-label="<?php esc_attr_e( 'Select topics that matter to you', 'civime-topics' ); ?>">
			<div class="topic-picker__grid">
				<?php foreach ( $topics as $topic ) : ?>
					<button
						type="button"
						class="topic-card"
						data-topic-slug="<?php echo esc_attr( $topic['slug'] ); ?>"
						aria-pressed="false"
						aria-label="<?php echo esc_attr( $topic['name'] ); ?>"
					>
						<span class="topic-card__icon" aria-hidden="true"><?php echo esc_html( $topic['icon'] ?? '' ); ?></span>
						<span class="topic-card__name"><?php echo esc_html( $topic['name'] ); ?></span>
						<span class="topic-card__description"><?php echo esc_html( $topic['description'] ?? '' ); ?></span>
						<span class="topic-card__count">
							<?php
							printf(
								/* translators: %d: number of councils */
								esc_html( _n( '%d council', '%d councils', (int) ( $topic['council_count'] ?? 0 ), 'civime-topics' ) ),
								(int) ( $topic['council_count'] ?? 0 )
							);
							?>
						</span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the "Your Topics" bar for use on meetings and other pages.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_your_topics_bar( array $atts = [] ): string {
		ob_start();
		?>
		<div
			class="your-topics-bar"
			id="your-topics-bar"
			role="status"
			aria-live="polite"
			aria-label="<?php esc_attr_e( 'Your selected topics', 'civime-topics' ); ?>"
			hidden
		>
			<span class="your-topics-bar__label"><?php esc_html_e( 'Your Topics:', 'civime-topics' ); ?></span>
			<span class="your-topics-bar__list" id="your-topics-list"></span>
			<a href="<?php echo esc_url( home_url( '/what-matters/' ) ); ?>" class="your-topics-bar__edit">
				<?php esc_html_e( 'Edit', 'civime-topics' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'your topic selections', 'civime-topics' ); ?></span>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}
}
