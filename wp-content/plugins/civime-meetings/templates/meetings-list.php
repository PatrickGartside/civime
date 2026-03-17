<?php
/**
 * Template: Meetings List
 *
 * @package CiviMe_Meetings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$list          = new CiviMe_Meetings_List();
$filters       = $list->get_filters();
$councils      = $list->get_councils();
$meetings      = $list->get_meetings();
$grouped       = $list->get_meetings_grouped_by_date();
$current_page  = $list->get_current_page();
$total_pages   = $list->get_total_pages();
$all_topics    = $list->get_all_topics();
$active_topics = $list->get_active_topics();
$active_slugs  = $filters['topics'] ?? [];
$active_sources = $filters['source'] ?? [];
$has_filters    = '' !== $filters['q']
	|| $filters['council_id'] > 0
	|| '' !== $filters['date_from']
	|| '' !== $filters['date_to']
	|| '' !== $filters['county']
	|| ! empty( $filters['topics'] )
	|| ! empty( $active_sources );
$filter_count   = $list->get_active_filter_count();
$filter_tags    = $list->get_active_filter_tags();

get_header();
?>

<main id="main" class="site-main" role="main">

	<header class="page-header">
		<div class="container">
			<h1 class="page-header__title">
				<svg class="page-header__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
				<?php esc_html_e( 'Public Meetings', 'civime-meetings' ); ?>
			</h1>
			<p class="page-header__description"><?php esc_html_e( 'Browse upcoming government meetings across Hawaii. Filter by topic, council, date, or keyword.', 'civime-meetings' ); ?></p>
		</div>
	</header>

	<?php
	// Local emoji map — avoids mojibake from API response encoding issues.
	$source_labels = [
		'ehawaii'          => __( 'State of Hawaii', 'civime-meetings' ),
		'nco'              => __( 'Honolulu Neighborhood Board', 'civime-meetings' ),
		'honolulu_boards'  => __( 'Honolulu County Committee', 'civime-meetings' ),
		'maui_legistar'    => __( 'Maui County Committee', 'civime-meetings' ),
	];

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

	$source_count = count( $active_sources );
	$topic_count  = count( $active_slugs );
	$search_has_filters = '' !== $filters['q']
		|| $filters['council_id'] > 0
		|| '' !== $filters['date_from']
		|| '' !== $filters['date_to']
		|| '' !== $filters['county'];
	?>

	<div class="section meetings-source-picker-section">
		<div class="container">
			<details class="meetings-filters-panel"<?php echo $source_count > 0 ? ' open' : ''; ?>>
				<summary class="meetings-filters-panel__toggle">
					<?php
					if ( $source_count > 0 ) {
						printf(
							esc_html( _n( 'Filter by Source (%d selected)', 'Filter by Source (%d selected)', $source_count, 'civime-meetings' ) ),
							$source_count
						);
					} else {
						esc_html_e( 'Filter by Source', 'civime-meetings' );
					}
					?>
				</summary>
			<div class="meetings-source-picker" role="group" aria-label="<?php esc_attr_e( 'Filter by source', 'civime-meetings' ); ?>">
				<div class="meetings-source-picker__grid">
					<?php foreach ( $source_labels as $source_key => $source_label ) :
						$is_active = in_array( $source_key, $active_sources, true );

						// Build URL: toggle this source on/off
						if ( $is_active ) {
							$new_sources = array_values( array_filter( $active_sources, function ( $s ) use ( $source_key ) {
								return $s !== $source_key;
							} ) );
						} else {
							$new_sources = array_merge( $active_sources, [ $source_key ] );
						}

						$toggle_args = array_filter( [
							'q'          => $filters['q'],
							'council_id' => $filters['council_id'] > 0 ? $filters['council_id'] : null,
							'date_from'  => $filters['date_from'],
							'date_to'    => $filters['date_to'],
							'county'     => $filters['county'],
							'topics'     => ! empty( $active_slugs ) ? implode( ',', $active_slugs ) : null,
							'source'     => ! empty( $new_sources ) ? implode( ',', $new_sources ) : null,
						] );
						$toggle_url = ! empty( $toggle_args )
							? add_query_arg( $toggle_args, home_url( '/meetings/' ) )
							: home_url( '/meetings/' );
						?>
						<a
							href="<?php echo esc_url( $toggle_url ); ?>"
							class="meetings-source-chip<?php echo $is_active ? ' meetings-source-chip--active' : ''; ?>"
							role="checkbox"
							aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>"
							aria-label="<?php echo esc_attr( $source_label ); ?>"
						>
							<span class="meetings-source-chip__name"><?php echo esc_html( $source_label ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
				<?php if ( ! empty( $active_sources ) ) : ?>
					<div class="meetings-source-picker__status">
						<span class="meetings-source-picker__count">
							<?php
							printf(
								esc_html( _n( '%d source selected', '%d sources selected', count( $active_sources ), 'civime-meetings' ) ),
								count( $active_sources )
							);
							?>
						</span>
						<?php
						$source_clear_args = array_filter( [
							'q'          => $filters['q'],
							'council_id' => $filters['council_id'] > 0 ? $filters['council_id'] : null,
							'date_from'  => $filters['date_from'],
							'date_to'    => $filters['date_to'],
							'county'     => $filters['county'],
							'topics'     => ! empty( $active_slugs ) ? implode( ',', $active_slugs ) : null,
						] );
						$source_clear_url = ! empty( $source_clear_args )
							? add_query_arg( $source_clear_args, home_url( '/meetings/' ) )
							: home_url( '/meetings/' );
						?>
						<a href="<?php echo esc_url( $source_clear_url ); ?>" class="meetings-source-picker__clear">
							<?php esc_html_e( 'Clear all', 'civime-meetings' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
			</details>
		</div>
	</div>

	<?php if ( ! empty( $all_topics ) ) : ?>
	<div class="section meetings-topic-picker-section">
		<div class="container">
			<details class="meetings-filters-panel"<?php echo $topic_count > 0 ? ' open' : ''; ?>>
				<summary class="meetings-filters-panel__toggle">
					<?php
					if ( $topic_count > 0 ) {
						printf(
							esc_html( _n( 'Filter by Topic (%d selected)', 'Filter by Topic (%d selected)', $topic_count, 'civime-meetings' ) ),
							$topic_count
						);
					} else {
						esc_html_e( 'Filter by Topic', 'civime-meetings' );
					}
					?>
				</summary>
			<div class="meetings-topic-picker" role="group" aria-label="<?php esc_attr_e( 'Filter by topic', 'civime-meetings' ); ?>">
				<div class="meetings-topic-picker__grid">
					<?php foreach ( $all_topics as $topic ) :
						$slug      = $topic['slug'] ?? '';
						$is_active = in_array( $slug, $active_slugs, true );

						// Build URL: toggle this slug on/off
						if ( $is_active ) {
							$new_slugs = array_values( array_filter( $active_slugs, function ( $s ) use ( $slug ) {
								return $s !== $slug;
							} ) );
						} else {
							$new_slugs = array_merge( $active_slugs, [ $slug ] );
						}

						$toggle_args = array_filter( [
							'q'          => $filters['q'],
							'council_id' => $filters['council_id'] > 0 ? $filters['council_id'] : null,
							'date_from'  => $filters['date_from'],
							'date_to'    => $filters['date_to'],
							'county'     => $filters['county'],
							'topics'     => ! empty( $new_slugs ) ? implode( ',', $new_slugs ) : null,
							'source'     => ! empty( $active_sources ) ? implode( ',', $active_sources ) : null,
						] );
						$toggle_url = ! empty( $toggle_args )
							? add_query_arg( $toggle_args, home_url( '/meetings/' ) )
							: home_url( '/meetings/' );
						?>
						<a
							href="<?php echo esc_url( $toggle_url ); ?>"
							class="meetings-topic-chip<?php echo $is_active ? ' meetings-topic-chip--active' : ''; ?>"
							role="checkbox"
							aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>"
							aria-label="<?php echo esc_attr( class_exists( 'CiviMe_I18n_Topic_Names' ) ? CiviMe_I18n_Topic_Names::get( $slug ) : ( $topic['name'] ?? '' ) ); ?>"
						>
							<?php
							$icon = $topic_icons[ $slug ] ?? '';
							if ( '' !== $icon ) : ?>
								<span class="meetings-topic-chip__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
							<?php endif; ?>
							<span class="meetings-topic-chip__name"><?php echo esc_html( class_exists( 'CiviMe_I18n_Topic_Names' ) ? CiviMe_I18n_Topic_Names::get( $slug ) : ( $topic['name'] ?? '' ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
				<?php if ( ! empty( $active_slugs ) ) : ?>
					<div class="meetings-topic-picker__status">
						<span class="meetings-topic-picker__count">
							<?php
							printf(
								esc_html( _n( '%d topic selected', '%d topics selected', count( $active_slugs ), 'civime-meetings' ) ),
								count( $active_slugs )
							);
							?>
						</span>
						<?php
						$clear_args = array_filter( [
							'q'          => $filters['q'],
							'council_id' => $filters['council_id'] > 0 ? $filters['council_id'] : null,
							'date_from'  => $filters['date_from'],
							'date_to'    => $filters['date_to'],
							'county'     => $filters['county'],
							'source'     => ! empty( $active_sources ) ? implode( ',', $active_sources ) : null,
						] );
						$clear_url = ! empty( $clear_args )
							? add_query_arg( $clear_args, home_url( '/meetings/' ) )
							: home_url( '/meetings/' );
						?>
						<a href="<?php echo esc_url( $clear_url ); ?>" class="meetings-topic-picker__clear">
							<?php esc_html_e( 'Clear all', 'civime-meetings' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
			</details>
		</div>
	</div>
	<?php endif; ?>

	<div class="section meetings-search-section">
		<div class="container">

			<details class="meetings-filters-panel"<?php echo $search_has_filters ? ' open' : ''; ?>>
				<summary class="meetings-filters-panel__toggle">
					<?php esc_html_e( 'Search Tool', 'civime-meetings' ); ?>
				</summary>
			<form
				class="meetings-filters"
				method="get"
				action="<?php echo esc_url( home_url( '/meetings/' ) ); ?>"
				role="search"
				aria-label="<?php esc_attr_e( 'Filter meetings', 'civime-meetings' ); ?>"
			>
				<div class="meetings-filters__row">

					<div class="meetings-filters__field">
						<label for="meetings-search" class="meetings-filters__label">
							<?php esc_html_e( 'Search', 'civime-meetings' ); ?>
						</label>
						<input
							type="search"
							id="meetings-search"
							name="q"
							value="<?php echo esc_attr( $filters['q'] ); ?>"
							placeholder="<?php esc_attr_e( 'Search meetings…', 'civime-meetings' ); ?>"
							class="meetings-filters__input"
							aria-label="<?php esc_attr_e( 'Search meetings by keyword', 'civime-meetings' ); ?>"
						>
					</div>

					<div class="meetings-filters__field meetings-filters__field--combobox">
						<label for="meetings-council" class="meetings-filters__label">
							<?php esc_html_e( 'Council', 'civime-meetings' ); ?>
						</label>
						<div class="combobox" data-combobox>
							<input
								type="text"
								id="meetings-council"
								class="meetings-filters__input combobox__input"
								role="combobox"
								autocomplete="off"
								aria-autocomplete="list"
								aria-expanded="false"
								aria-controls="meetings-council-listbox"
								aria-label="<?php esc_attr_e( 'Search councils by name', 'civime-meetings' ); ?>"
								placeholder="<?php esc_attr_e( 'All Councils', 'civime-meetings' ); ?>"
								<?php if ( $filters['council_id'] > 0 ) :
									$selected_name = '';
									foreach ( $councils as $c ) {
										if ( (int) $c['id'] === $filters['council_id'] ) {
											$selected_name = $c['name'];
											break;
										}
									}
									?>
									value="<?php echo esc_attr( $selected_name ); ?>"
								<?php endif; ?>
							>
							<input type="hidden" name="council_id" value="<?php echo esc_attr( (string) $filters['council_id'] ); ?>" data-combobox-value>
							<ul
								id="meetings-council-listbox"
								class="combobox__listbox"
								role="listbox"
								aria-label="<?php esc_attr_e( 'Councils', 'civime-meetings' ); ?>"
								hidden
							>
								<li role="option" data-value="" class="combobox__option">
									<?php esc_html_e( 'All Councils', 'civime-meetings' ); ?>
								</li>
								<?php
								// Group councils by county for accessible browsing.
								$grouped_councils = [];
								foreach ( $councils as $council ) {
									$county = $council['county'] ?: __( 'Other', 'civime-meetings' );
									$grouped_councils[ $county ][] = $council;
								}
								ksort( $grouped_councils );

								foreach ( $grouped_councils as $county_label => $county_councils ) : ?>
									<li role="presentation" class="combobox__group-label" aria-hidden="true">
										<?php echo esc_html( ucwords( $county_label ) ); ?>
									</li>
									<?php foreach ( $county_councils as $council ) : ?>
										<li
											role="option"
											data-value="<?php echo esc_attr( (string) $council['id'] ); ?>"
											class="combobox__option"
											<?php if ( $filters['council_id'] === (int) $council['id'] ) : ?>
												aria-selected="true"
											<?php endif; ?>
										>
											<?php echo esc_html( $council['name'] ); ?>
										</li>
									<?php endforeach; ?>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>

					<div class="meetings-filters__field">
						<label for="meetings-county" class="meetings-filters__label">
							<?php esc_html_e( 'County', 'civime-meetings' ); ?>
						</label>
						<select id="meetings-county" name="county" class="meetings-filters__select">
							<option value=""><?php esc_html_e( 'All Counties', 'civime-meetings' ); ?></option>
							<option value="state" <?php selected( $filters['county'], 'state' ); ?>><?php esc_html_e( 'State', 'civime-meetings' ); ?></option>
							<option value="honolulu" <?php selected( $filters['county'], 'honolulu' ); ?>><?php esc_html_e( 'Honolulu', 'civime-meetings' ); ?></option>
							<option value="maui" <?php selected( $filters['county'], 'maui' ); ?>><?php esc_html_e( 'Maui', 'civime-meetings' ); ?></option>
							<option value="hawaii" <?php selected( $filters['county'], 'hawaii' ); ?>>Hawai&#x02BB;i</option>
							<option value="kauai" <?php selected( $filters['county'], 'kauai' ); ?>>Kaua&#x02BB;i</option>
						</select>
					</div>

					<div class="meetings-filters__field">
						<label for="meetings-date-from" class="meetings-filters__label">
							<?php esc_html_e( 'From', 'civime-meetings' ); ?>
						</label>
						<input
							type="date"
							id="meetings-date-from"
							name="date_from"
							value="<?php echo esc_attr( $filters['date_from'] ); ?>"
							class="meetings-filters__input"
						>
					</div>

					<div class="meetings-filters__field">
						<label for="meetings-date-to" class="meetings-filters__label">
							<?php esc_html_e( 'To', 'civime-meetings' ); ?>
						</label>
						<input
							type="date"
							id="meetings-date-to"
							name="date_to"
							value="<?php echo esc_attr( $filters['date_to'] ); ?>"
							class="meetings-filters__input"
						>
					</div>

					<?php if ( ! empty( $filters['topics'] ) ) : ?>
					<input type="hidden" name="topics" value="<?php echo esc_attr( implode( ',', $filters['topics'] ) ); ?>">
				<?php endif; ?>

				<?php if ( ! empty( $active_sources ) ) : ?>
					<input type="hidden" name="source" value="<?php echo esc_attr( implode( ',', $active_sources ) ); ?>">
				<?php endif; ?>

					<div class="meetings-filters__actions">
						<button type="submit" class="btn btn--primary">
							<?php esc_html_e( 'Filter', 'civime-meetings' ); ?>
						</button>
						<?php if ( $has_filters ) : ?>
							<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="btn btn--ghost">
								<?php esc_html_e( 'Clear', 'civime-meetings' ); ?>
							</a>
						<?php endif; ?>
					</div>

				</div>
			</form>
			</details>

		</div>
	</div>

	<?php if ( ! empty( $filter_tags ) ) : ?>
		<div class="container">
			<div class="meetings-filter-bar" role="group" aria-label="<?php esc_attr_e( 'Active filters', 'civime-meetings' ); ?>">
				<?php foreach ( $filter_tags as $tag ) : ?>
					<span class="meetings-filter-tag">
						<span class="meetings-filter-tag__label"><?php echo esc_html( $tag['label'] ); ?></span>
						<a href="<?php echo esc_url( $tag['remove_url'] ); ?>" class="meetings-filter-tag__remove" aria-label="<?php echo esc_attr( sprintf( __( 'Remove filter: %s', 'civime-meetings' ), $tag['label'] ) ); ?>">&times;</a>
					</span>
				<?php endforeach; ?>
				<?php if ( count( $filter_tags ) >= 2 ) : ?>
					<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>" class="meetings-filter-bar__clear">
						<?php esc_html_e( 'Clear all', 'civime-meetings' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="section">
		<div class="container">

			<?php if ( $list->has_error() ) : ?>

				<div class="meetings-notice meetings-notice--warning" role="alert">
					<p><strong><?php esc_html_e( 'Meeting data is temporarily unavailable.', 'civime-meetings' ); ?></strong></p>
					<p>
						<?php
						printf(
							/* translators: %s: link to about page */
							esc_html__( 'We\'re working on connecting to the meeting database. Check back soon, or browse our %s to learn more about Civi.Me.', 'civime-meetings' ),
							'<a href="' . esc_url( home_url( '/about' ) ) . '">' . esc_html__( 'about page', 'civime-meetings' ) . '</a>'
						);
						?>
					</p>
				</div>

			<?php elseif ( empty( $meetings ) ) : ?>

				<div class="meetings-notice meetings-notice--info" role="status">
					<p><?php esc_html_e( 'No meetings found matching your filters.', 'civime-meetings' ); ?></p>
					<p>
						<a href="<?php echo esc_url( home_url( '/meetings/' ) ); ?>">
							<?php esc_html_e( 'View all upcoming meetings', 'civime-meetings' ); ?>
						</a>
					</p>
				</div>

			<?php else : ?>

				<?php
				$total_count  = $list->get_total_count();
				$offset_start = ( ( $current_page - 1 ) * $list->get_per_page() ) + 1;
				$offset_end   = $offset_start + count( $meetings ) - 1;
				?>
				<p class="meetings-results__count" aria-live="polite">
					<?php
					printf(
						/* translators: 1: first item number, 2: last item number, 3: total count */
						esc_html__( 'Showing %1$d–%2$d of %3$d meetings', 'civime-meetings' ),
						$offset_start,
						$offset_end,
						$total_count
					);
					?>
				</p>

				<?php foreach ( $grouped as $date_string => $date_meetings ) : ?>

					<section class="meetings-date-group" aria-labelledby="date-heading-<?php echo esc_attr( $date_string ); ?>">

						<h2
							id="date-heading-<?php echo esc_attr( $date_string ); ?>"
							class="meetings-date-group__heading"
						>
							<?php
							echo esc_html( wp_date( 'l, F j, Y', strtotime( $date_string ) ) );
							?>
						</h2>

						<?php foreach ( $date_meetings as $meeting ) : ?>

							<?php
							$state_id    = $meeting['state_id'] ?? '';
							$meeting_url = home_url( '/meetings/' . rawurlencode( $state_id ) );
							$has_summary = ! empty( $meeting['has_summary'] );
							$time_raw    = $meeting['time'] ?? '';
							$time_label  = '' !== $time_raw ? wp_date( 'g:i A', strtotime( $time_raw ), new DateTimeZone( 'UTC' ) ) : '';
							?>

							<article class="meeting-card" aria-label="<?php echo esc_attr( $meeting['council_name'] ?? '' ); ?>">

								<div class="meeting-card__body">

									<h3 class="meeting-card__title">
										<a href="<?php echo esc_url( $meeting_url ); ?>">
											<?php echo esc_html( $meeting['council_name'] ?? '' ); ?>
										</a>
									</h3>

									<p class="meeting-card__subtitle">
										<?php echo esc_html( $meeting['title'] ?? '' ); ?>
									</p>

									<div class="meeting-card__meta">

										<?php if ( '' !== $time_label ) : ?>
											<span class="meeting-card__time">
												<span class="screen-reader-text"><?php esc_html_e( 'Time:', 'civime-meetings' ); ?></span>
												<?php echo esc_html( $time_label ); ?>
											</span>
										<?php endif; ?>

										<?php if ( ! empty( $meeting['location'] ) ) : ?>
											<span class="meeting-card__location">
												<span class="screen-reader-text"><?php esc_html_e( 'Location:', 'civime-meetings' ); ?></span>
												<?php echo esc_html( $meeting['location'] ); ?>
											</span>
										<?php endif; ?>

										<?php if ( $has_summary ) : ?>
											<span class="meeting-card__badge" aria-label="<?php esc_attr_e( 'AI-generated summary available', 'civime-meetings' ); ?>">
												<?php esc_html_e( 'AI Summary', 'civime-meetings' ); ?>
											</span>
										<?php endif; ?>

									</div>

								</div>

								<?php $source_key = $meeting['source'] ?? ''; ?>
								<div class="meeting-card__action">
									<a href="<?php echo esc_url( $meeting_url ); ?>" class="btn btn--small">
										<?php esc_html_e( 'View Details', 'civime-meetings' ); ?>
										<span class="screen-reader-text">
											<?php
											/* translators: %s: council name */
											printf( esc_html__( 'for %s', 'civime-meetings' ), esc_html( $meeting['council_name'] ?? '' ) );
											?>
										</span>
									</a>
									<?php
									$source_label = $source_labels[ $source_key ] ?? '';
									if ( '' !== $source_label ) : ?>
										<span class="meeting-card__badge meeting-card__badge--source meeting-card__badge--<?php echo esc_attr( $source_key ); ?>">
											<?php echo esc_html( $source_label ); ?>
										</span>
									<?php endif; ?>
								</div>

							</article>

						<?php endforeach; ?>

					</section>

				<?php endforeach; ?>

				<?php if ( $total_pages > 1 ) : ?>

					<nav class="meetings-pagination" aria-label="<?php esc_attr_e( 'Meeting pages', 'civime-meetings' ); ?>">

						<?php
						// Preserve all active filter params when building page URLs.
						$pagination_base_args = array_filter( [
							'q'          => $filters['q'],
							'council_id' => $filters['council_id'] > 0 ? $filters['council_id'] : null,
							'date_from'  => $filters['date_from'],
							'date_to'    => $filters['date_to'],
							'county'     => $filters['county'],
							'topics'     => ! empty( $filters['topics'] ) ? implode( ',', $filters['topics'] ) : null,
							'source'     => ! empty( $active_sources ) ? implode( ',', $active_sources ) : null,
						] );

						$prev_page = $current_page - 1;
						$next_page = $current_page + 1;
						?>

						<?php if ( $current_page > 1 ) : ?>
							<a
								href="<?php echo esc_url( add_query_arg( array_merge( $pagination_base_args, [ 'page' => $prev_page ] ), home_url( '/meetings/' ) ) ); ?>"
								class="meetings-pagination__link meetings-pagination__link--prev"
								rel="prev"
							>
								<span aria-hidden="true">&laquo;</span>
								<?php esc_html_e( 'Previous', 'civime-meetings' ); ?>
							</a>
						<?php endif; ?>

						<ol class="meetings-pagination__pages" role="list">
							<?php
							// Show a sliding window of pages: always first, last, current, and two on each side.
							$page_window_start = max( 1, $current_page - 2 );
							$page_window_end   = min( $total_pages, $current_page + 2 );

							if ( $page_window_start > 1 ) :
								?>
								<li>
									<a
										href="<?php echo esc_url( add_query_arg( array_merge( $pagination_base_args, [ 'page' => 1 ] ), home_url( '/meetings/' ) ) ); ?>"
										class="meetings-pagination__page"
										aria-label="<?php esc_attr_e( 'Page 1', 'civime-meetings' ); ?>"
									>1</a>
								</li>
								<?php if ( $page_window_start > 2 ) : ?>
									<li class="meetings-pagination__ellipsis" aria-hidden="true">&hellip;</li>
								<?php endif; ?>
							<?php endif; ?>

							<?php for ( $page_num = $page_window_start; $page_num <= $page_window_end; $page_num++ ) : ?>
								<li>
									<?php if ( $page_num === $current_page ) : ?>
										<span
											class="meetings-pagination__page meetings-pagination__page--current"
											aria-current="page"
											aria-label="<?php echo esc_attr( sprintf( __( 'Page %d, current page', 'civime-meetings' ), $page_num ) ); ?>"
										>
											<?php echo esc_html( (string) $page_num ); ?>
										</span>
									<?php else : ?>
										<a
											href="<?php echo esc_url( add_query_arg( array_merge( $pagination_base_args, [ 'page' => $page_num ] ), home_url( '/meetings/' ) ) ); ?>"
											class="meetings-pagination__page"
											aria-label="<?php echo esc_attr( sprintf( __( 'Page %d', 'civime-meetings' ), $page_num ) ); ?>"
										>
											<?php echo esc_html( (string) $page_num ); ?>
										</a>
									<?php endif; ?>
								</li>
							<?php endfor; ?>

							<?php if ( $page_window_end < $total_pages ) : ?>
								<?php if ( $page_window_end < $total_pages - 1 ) : ?>
									<li class="meetings-pagination__ellipsis" aria-hidden="true">&hellip;</li>
								<?php endif; ?>
								<li>
									<a
										href="<?php echo esc_url( add_query_arg( array_merge( $pagination_base_args, [ 'page' => $total_pages ] ), home_url( '/meetings/' ) ) ); ?>"
										class="meetings-pagination__page"
										aria-label="<?php echo esc_attr( sprintf( __( 'Page %d', 'civime-meetings' ), $total_pages ) ); ?>"
									>
										<?php echo esc_html( (string) $total_pages ); ?>
									</a>
								</li>
							<?php endif; ?>
						</ol>

						<?php if ( $current_page < $total_pages ) : ?>
							<a
								href="<?php echo esc_url( add_query_arg( array_merge( $pagination_base_args, [ 'page' => $next_page ] ), home_url( '/meetings/' ) ) ); ?>"
								class="meetings-pagination__link meetings-pagination__link--next"
								rel="next"
							>
								<?php esc_html_e( 'Next', 'civime-meetings' ); ?>
								<span aria-hidden="true">&raquo;</span>
							</a>
						<?php endif; ?>

					</nav>

				<?php endif; ?>

			<?php endif; ?>

		</div>
	</div>

</main>

<?php
get_footer();
