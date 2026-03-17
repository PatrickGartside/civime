<?php
/**
 * Language switcher widget.
 *
 * Renders a <form method="get"> with a <select> of available languages.
 * Works without JavaScript; auto-submits with JS for convenience.
 *
 * @package CiviMe_I18n
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CiviMe_I18n_Switcher {

	/**
	 * Render the language switcher.
	 *
	 * @param string $context Where the switcher is placed: 'header', 'footer', or 'mobile'.
	 */
	public static function render( string $context = 'header' ): void {
		// Check if switcher is globally disabled.
		if ( ! get_option( 'civime_i18n_switcher_enabled', true ) ) {
			return;
		}

		// Check if this location is enabled.
		$locations = get_option( 'civime_i18n_switcher_locations', [ 'header', 'footer', 'mobile' ] );
		if ( ! in_array( $context, $locations, true ) ) {
			return;
		}

		$available = CiviMe_I18n_Locale::get_available_slugs();

		// Don't render if only English is available.
		if ( count( $available ) < 2 ) {
			return;
		}

		$active_slug = apply_filters( 'civime_i18n_active_slug', 'en' );
		$modifier    = esc_attr( $context );
		?>
		<form class="lang-switcher lang-switcher--<?php echo $modifier; ?>" method="get" action="">
			<?php
			// Preserve existing query params (except 'lang') so filters etc. survive.
			$allowed_params = [ 'topic', 'council', 'search', 'page', 'paged', 's', 'category', 'guide_category' ];
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			foreach ( $_GET as $key => $value ) {
				if ( 'lang' === $key ) {
					continue;
				}
				$key = sanitize_text_field( wp_unslash( $key ) );
				if ( ! in_array( $key, $allowed_params, true ) ) {
					continue;
				}
				$value = sanitize_text_field( wp_unslash( $value ) );
				?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<?php
			}
			?>

			<label for="lang-select-<?php echo $modifier; ?>" class="lang-switcher__label">
				<svg class="lang-switcher__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true" focusable="false">
					<circle cx="12" cy="12" r="10"/>
					<line x1="2" y1="12" x2="22" y2="12"/>
					<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
				</svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Language', 'civime-i18n' ); ?></span>
			</label>

			<select
				id="lang-select-<?php echo $modifier; ?>"
				name="lang"
				class="lang-switcher__select"
			>
				<?php foreach ( $available as $slug ) : ?>
					<option
						value="<?php echo esc_attr( $slug ); ?>"
						lang="<?php echo esc_attr( $slug ); ?>"
						<?php selected( $slug, $active_slug ); ?>
					>
						<?php echo esc_html( CiviMe_I18n_Locale::get_native_name( $slug ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<noscript>
				<button type="submit" class="lang-switcher__submit">
					<?php esc_html_e( 'Go', 'civime-i18n' ); ?>
				</button>
			</noscript>
		</form>
		<?php
	}
}
