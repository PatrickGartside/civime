<?php
/**
 * Template: Topic Picker ("What Matters to Me")
 *
 * @package CiviMe_Topics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$api    = civime_api();
$result = $api->get_topics();
$topics = [];
$error  = false;

// Local emoji map — avoids mojibake from API response encoding issues.
$topic_icons = [
	'environment'     => "\xF0\x9F\x8C\xBF",
	'housing'         => "\xF0\x9F\x8F\xA0",
	'education'       => "\xF0\x9F\x93\x9A",
	'health'          => "\xF0\x9F\x8F\xA5",
	'transportation'  => "\xF0\x9F\x9A\x8C",
	'public-safety'   => "\xF0\x9F\x9B\xA1\xEF\xB8\x8F",
	'economy'         => "\xF0\x9F\x92\xBC",
	'culture'         => "\xF0\x9F\x8E\xAD",
	'agriculture'     => "\xF0\x9F\x8C\xBE",
	'energy'          => "\xE2\x9A\xA1",
	'water'           => "\xF0\x9F\x8C\x8A",
	'disability'      => "\xE2\x99\xBF",
	'veterans'        => "\xF0\x9F\x8E\x96\xEF\xB8\x8F",
	'technology'      => "\xF0\x9F\x92\xBB",
	'budget'          => "\xF0\x9F\x93\x8A",
	'governance'      => "\xE2\x9A\x96\xEF\xB8\x8F",
];

if ( is_wp_error( $result ) ) {
	$error = true;
} else {
	$topics = $result['data'] ?? [];
}

get_header();
?>

<main id="main" class="site-main" role="main">

	<header class="page-header">
		<div class="container">
			<h1 class="page-header__title"><?php esc_html_e( 'What Matters to You?', 'civime-topics' ); ?></h1>
			<p class="page-header__description">
				<?php esc_html_e( 'Pick the topics you care about. We\'ll show you relevant meetings from the boards and commissions that handle these issues.', 'civime-topics' ); ?>
			</p>
		</div>
	</header>

	<div class="section">
		<div class="container">

			<?php if ( $error ) : ?>

				<div class="meetings-notice meetings-notice--warning" role="alert">
					<p><strong><?php esc_html_e( 'Topics are temporarily unavailable.', 'civime-topics' ); ?></strong></p>
					<p><?php esc_html_e( 'We\'re working on loading the topic list. Check back soon.', 'civime-topics' ); ?></p>
				</div>

			<?php elseif ( empty( $topics ) ) : ?>

				<div class="meetings-notice meetings-notice--info" role="status">
					<p><?php esc_html_e( 'No topics are currently available.', 'civime-topics' ); ?></p>
				</div>

			<?php else : ?>

				<div class="topic-picker" role="group" aria-label="<?php esc_attr_e( 'Select topics that matter to you', 'civime-topics' ); ?>">

					<div class="topic-picker__actions topic-picker__actions--top">
						<p class="topic-picker__hint" role="status" aria-live="polite">
							<span id="topic-count">0</span> <?php esc_html_e( 'topics selected', 'civime-topics' ); ?>
						</p>
					</div>

					<div class="topic-picker__grid">
						<?php foreach ( $topics as $topic ) : ?>
							<button
								type="button"
								class="topic-card"
								data-topic-slug="<?php echo esc_attr( $topic['slug'] ); ?>"
								data-topic-name="<?php echo esc_attr( $topic['name'] ); ?>"
								aria-pressed="false"
							>
								<span class="topic-card__icon" aria-hidden="true"><?php echo esc_html( $topic_icons[ $topic['slug'] ?? '' ] ?? '' ); ?></span>
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

					<div class="topic-picker__actions">
						<a
							href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>"
							class="btn btn--primary btn--lg"
							id="topic-done-btn"
						>
							<?php esc_html_e( 'Show My Meetings', 'civime-topics' ); ?>
						</a>
						<button type="button" class="btn btn--ghost" id="topic-clear-btn">
							<?php esc_html_e( 'Clear All', 'civime-topics' ); ?>
						</button>
					</div>

				</div>

			<?php endif; ?>

		</div>
	</div>

</main>

<?php
get_footer();
