# Execution Plan: civime-meetings Plugin (Step 1.5)

**Objective:** Build the `civime-meetings` WordPress plugin that provides meeting list, meeting detail, and council browse views. The plugin registers custom URL routes (`/meetings/`, `/meetings/{state_id}`, `/meetings/councils/`), calls the Access100 API via the `civime_api()` client from civime-core, and renders server-side HTML using the civime theme's design system. The API is not yet live, so all views must gracefully degrade with friendly fallback messages.

**Plugin directory:** `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/`

**Final file structure:**
```
wp-content/plugins/civime-meetings/
├── civime-meetings.php              # Main plugin file: bootstrap, autoloader, constants
├── includes/
│   ├── class-meetings-router.php    # Rewrite rules, query vars, template hijacking
│   ├── class-meetings-list.php      # Meeting list page controller
│   ├── class-meeting-detail.php     # Meeting detail page controller
│   ├── class-councils-list.php      # Council browse page controller
│   └── shortcodes.php               # Shortcode registrations (optional embeds)
├── templates/
│   ├── meetings-list.php            # Meeting list view template
│   ├── meeting-detail.php           # Meeting detail view template
│   └── councils-list.php            # Council browse view template
└── assets/
    ├── css/
    │   └── meetings.css             # Plugin-specific styles (cards, filters, detail)
    └── js/
        └── meetings.js              # Filter form UX (submit on change, clear filters)
```

---

## Phase 1: Main Plugin File + Autoloader + Router

**Agent:** `@coder`
**Goal:** Rewrite `civime-meetings.php` as the full bootstrap file, create the router class that registers all custom URL routes, and wire up asset enqueueing.
**Depends On:** None
**Files to create/modify:**
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/civime-meetings.php` (overwrite existing stub)
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-meetings-router.php`

### Requirements for `civime-meetings.php`:

1. Keep the existing plugin header block exactly as-is (Plugin Name, Version 0.1.0, etc.).
2. Keep `CIVIME_MEETINGS_VERSION` and `CIVIME_MEETINGS_PATH` constants. Add `CIVIME_MEETINGS_URL` using `plugin_dir_url(__FILE__)`.
3. Add dependency check: on `plugins_loaded`, verify `function_exists('civime_api')`. If civime-core is not active, add an admin notice and bail (do not fatal). The admin notice should say "CiviMe Meetings requires the CiviMe Core plugin to be installed and activated."
4. Register an autoloader using `spl_autoload_register` that maps `CiviMe_Meetings_*` prefixed classes to `includes/class-*.php` files, following the same naming convention as civime-core (strip the `CiviMe_Meetings_` prefix, lowercase, hyphens for underscores, prepend `class-`). Example: `CiviMe_Meetings_Router` maps to `includes/class-router.php`.
5. On `plugins_loaded` (after the dependency check passes), instantiate `new CiviMe_Meetings_Router()` and call `require_once CIVIME_MEETINGS_PATH . 'includes/shortcodes.php'`.
6. Register an activation hook that calls `flush_rewrite_rules()`.
7. Register a deactivation hook that calls `flush_rewrite_rules()`.
8. Enqueue plugin assets on the `wp_enqueue_scripts` hook, but ONLY when the current request matches a meetings route. Check this by looking at the `civime_route` query var (see router below). Enqueue:
   - `civime-meetings-css`: `assets/css/meetings.css` with dependency on `civime-theme` (the main theme stylesheet handle), version = `CIVIME_MEETINGS_VERSION`.
   - `civime-meetings-js`: `assets/js/meetings.js` with no dependencies, version = `CIVIME_MEETINGS_VERSION`, loaded deferred in footer.

### Requirements for `class-meetings-router.php` (`CiviMe_Meetings_Router`):

This class owns the URL routing. It must register custom rewrite rules so that `/meetings/`, `/meetings/councils/`, and `/meetings/{anything}` are handled by the plugin without needing WordPress Pages to exist.

1. **Query variables:** Register two custom query vars via `query_vars` filter:
   - `civime_route` - values: `meetings-list`, `meeting-detail`, `councils-list`
   - `civime_meeting_id` - the state_id segment from the URL

2. **Rewrite rules:** On `init`, add these rewrite rules (in this order -- order matters, more specific rules first):
   - `^meetings/councils/?$` => `index.php?civime_route=councils-list`
   - `^meetings/([^/]+)/?$` => `index.php?civime_route=meeting-detail&civime_meeting_id=$matches[1]`
   - `^meetings/?$` => `index.php?civime_route=meetings-list`

3. **Template hijacking:** On the `template_include` filter, check `get_query_var('civime_route')`. If it matches one of our routes, return the path to the corresponding template file from this plugin's `templates/` directory. Otherwise return the original template path unchanged.

4. **Document title:** Filter `document_title_parts` to set appropriate page titles:
   - `meetings-list`: "Meetings" as the title
   - `meeting-detail`: Will be set dynamically in Phase 3 (for now, "Meeting Detail")
   - `councils-list`: "Councils" as the title

5. **Body classes:** Filter `body_class` to add `civime-meetings-page` and a route-specific class (e.g., `civime-meetings-list`, `civime-meeting-detail`, `civime-councils-list`) when on a meetings route.

6. **Canonical URL:** Filter `get_canonical_url` or use `wp_head` to output `<link rel="canonical">` for the meetings pages since they are not real WordPress posts/pages.

**Command:**
```
You are @coder building the civime-meetings WordPress plugin for civi.me, a Hawaii civic engagement platform.

Create two files:

1. `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/civime-meetings.php` — Overwrite the existing stub. This is the main plugin bootstrap file. Requirements:

- Keep the exact plugin header: Plugin Name "CiviMe Meetings", Version 0.1.0, Author Patrick Gartside, Text Domain civime-meetings, License GPL-2.0-or-later, Requires PHP 8.2, Requires at least 6.0.
- ABSPATH guard at top.
- Define constants: CIVIME_MEETINGS_VERSION ('0.1.0'), CIVIME_MEETINGS_PATH (plugin_dir_path), CIVIME_MEETINGS_URL (plugin_dir_url).
- Autoloader via spl_autoload_register: map CiviMe_Meetings_* classes to includes/class-*.php. Strip the "CiviMe_Meetings_" prefix, lowercase, replace underscores with hyphens, prepend "class-". Example: CiviMe_Meetings_Router => includes/class-router.php.
- On 'plugins_loaded', check function_exists('civime_api'). If false, add admin_notice saying "CiviMe Meetings requires the CiviMe Core plugin to be installed and activated." and return early.
- If dependency check passes, instantiate CiviMe_Meetings_Router and require_once the shortcodes.php file.
- Activation hook: flush_rewrite_rules().
- Deactivation hook: flush_rewrite_rules().
- Asset enqueueing on 'wp_enqueue_scripts': ONLY when get_query_var('civime_route') is non-empty. Enqueue civime-meetings-css (assets/css/meetings.css, depends on 'civime-theme', version CIVIME_MEETINGS_VERSION) and civime-meetings-js (assets/js/meetings.js, no deps, version CIVIME_MEETINGS_VERSION, defer, in_footer).

2. `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-meetings-router.php` — New file. Class CiviMe_Meetings_Router. Requirements:

- ABSPATH guard.
- Constructor hooks into: 'init' for add_rewrite_rule, 'query_vars' filter, 'template_include' filter, 'document_title_parts' filter, 'body_class' filter.
- Register query vars: 'civime_route' and 'civime_meeting_id'.
- Rewrite rules (add in this order — specificity matters):
  - ^meetings/councils/?$ => index.php?civime_route=councils-list
  - ^meetings/([^/]+)/?$ => index.php?civime_route=meeting-detail&civime_meeting_id=$matches[1]
  - ^meetings/?$ => index.php?civime_route=meetings-list
  Use add_rewrite_rule with 'top' priority.
- template_include: check get_query_var('civime_route'). Map 'meetings-list' to CIVIME_MEETINGS_PATH . 'templates/meetings-list.php', 'meeting-detail' to templates/meeting-detail.php, 'councils-list' to templates/councils-list.php. If matched, set a global or use status_header(200) and return the template path. Otherwise return original.
- document_title_parts: when civime_route is set, override $title['title']. meetings-list => 'Meetings', councils-list => 'Councils', meeting-detail => 'Meeting Detail' (placeholder — will be refined later).
- body_class: when civime_route is set, add 'civime-meetings-page' and 'civime-' . get_query_var('civime_route') to the classes array.

Follow WordPress PHP coding standards. Use strict types where appropriate. All strings visible to users should use __() or esc_html__() with text domain 'civime-meetings'.
```

---

## Phase 2: Meetings List Controller + Template

**Agent:** `@coder`
**Goal:** Build the meetings list page controller class and its template -- the primary `/meetings/` view showing date-grouped meeting cards with filter controls.
**Depends On:** Phase 1
**Files to create:**
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-meetings-list.php`
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/templates/meetings-list.php`

### Requirements for `class-meetings-list.php` (`CiviMe_Meetings_List`):

This is a controller/data-preparation class, not a WordPress model. It is instantiated in the template and provides data for rendering.

1. **Constructor** accepts no arguments. It reads filter values from `$_GET` (sanitized):
   - `council_id` (int, optional) - filter by council
   - `date_from` (string, Y-m-d format, optional) - defaults to today
   - `date_to` (string, Y-m-d format, optional) - defaults to 30 days from today
   - `q` (string, optional) - keyword search
   - `page` (int, optional) - pagination page number, defaults to 1
   - Define a constant `MEETINGS_PER_PAGE = 20`.

2. **`get_meetings(): array|WP_Error`** — Calls `civime_api()->get_meetings()` passing the filter args (council_id, date_from, date_to, q, limit = MEETINGS_PER_PAGE, offset = (page-1) * MEETINGS_PER_PAGE). Returns the API response.

3. **`get_councils_for_filter(): array|WP_Error`** — Calls `civime_api()->get_councils(['has_upcoming' => true])` to populate the council filter dropdown. Returns the API response.

4. **`get_meetings_grouped_by_date(array $meetings): array`** — Takes the flat array of meetings from the API response `data` key and groups them by date. Returns an associative array keyed by date string (Y-m-d) with arrays of meeting objects as values. The dates should be sorted chronologically.

5. **`get_current_filters(): array`** — Returns an associative array of the current active filter values (useful for templates to set form field values and build pagination URLs).

6. **`get_pagination_data(array $api_response): array`** — Extracts pagination info from the API response's `meta` key. Returns array with keys: `current_page`, `total_pages`, `total_meetings`, `per_page`, `has_prev`, `has_next`. If meta is missing, return sensible defaults.

7. **`format_meeting_date(string $date): string`** — Formats a Y-m-d date into a human-readable format like "Thursday, March 15, 2026". Use `wp_date()` for localization.

8. **`format_meeting_time(string $time): string`** — Formats a time string (H:i:s or H:i) into "1:30 PM" format. Use `wp_date()`.

### Requirements for `templates/meetings-list.php`:

This is a full-page template that calls `get_header()` and `get_footer()`. It uses the theme's design system classes.

1. At the top, instantiate `$list = new CiviMe_Meetings_List()`.
2. Fetch meetings: `$response = $list->get_meetings()`.
3. Fetch councils for filter dropdown: `$councils_response = $list->get_councils_for_filter()`.

4. **Page structure:**
```
get_header()
<main id="main" class="site-main" role="main">
  <header class="page-header">
    <div class="container">
      <h1 class="page-header__title">Meetings</h1>
      <p class="page-header__description">Browse upcoming government meetings across Hawaii...</p>
    </div>
  </header>

  <div class="section">
    <div class="container">
      <!-- Filter bar -->
      <!-- Meeting cards grouped by date -->
      <!-- Pagination -->
    </div>
  </div>
</main>
get_footer()
```

5. **Filter bar** — A `<form>` with method GET and action pointing to `/meetings/`. Contains:
   - Council dropdown (`<select>` with class `form-control`): "All Councils" default option, then each council from the API. Pre-select if `council_id` filter is active.
   - Date from input (`<input type="date">`): pre-filled with current date_from filter.
   - Date to input (`<input type="date">`): pre-filled with current date_to filter.
   - Keyword search (`<input type="search" placeholder="Search meetings...">`).
   - Submit button (`<button class="btn btn--primary btn--sm">Filter</button>`).
   - If any filters are active, show a "Clear filters" link back to `/meetings/`.
   - Wrap the form in a `<div class="meetings-filters">` container.
   - The form should use CSS grid for layout (defined in meetings.css later).

6. **Error/empty states:**
   - If `$response` is `WP_Error`, show a notice div: `<div class="notice notice--warning">` with message "We're having trouble connecting to the meeting database right now. Please try again in a few minutes." Do NOT show the raw error message to users.
   - If `$response` is valid but `data` is empty, show a notice: "No meetings found matching your criteria. Try adjusting your filters or check back soon."

7. **Meeting cards grouped by date:**
   - Call `$grouped = $list->get_meetings_grouped_by_date($response['data'])`.
   - For each date group, render a `<section>` with:
     - A date heading: `<h2 class="meetings-date-heading">Thursday, March 15, 2026</h2>`
     - A list of meeting cards below it.
   - Each meeting card uses the theme's `.card` component:
     ```html
     <article class="card meetings-card">
       <div class="meetings-card__meta">
         <span class="meetings-card__time">1:30 PM</span>
         <span class="meetings-card__council">Board of Education</span>
       </div>
       <h3 class="card__title">
         <a href="/meetings/{state_id}">General Business Meeting</a>
       </h3>
       <div class="card__body">
         <p class="meetings-card__location">Queen Liliuokalani Building, Room 404</p>
       </div>
       <div class="card__footer">
         <a href="/meetings/{state_id}" class="btn btn--secondary btn--sm">View Details</a>
       </div>
     </article>
     ```
   - Use proper escaping: `esc_html()`, `esc_url()`, `esc_attr()` on all output.
   - The meeting title/name may come from the API as `title` or `description` or `notice` -- use the first non-empty one, or fall back to "Meeting — {council_name}".
   - Link to detail page: `home_url('/meetings/' . $meeting['state_id'])`.

8. **Pagination:**
   - Get pagination data: `$pagination = $list->get_pagination_data($response)`.
   - Render a `<nav class="meetings-pagination" aria-label="Meeting pages">` block.
   - Show "Previous" and "Next" links (as `<a class="btn btn--secondary btn--sm">`) using the current filter query parameters plus `&page=N`.
   - Show "Page X of Y" text between them.
   - Do not render pagination if there is only one page.
   - Previous link should be disabled (just a `<span>`) when on page 1. Next link disabled when on the last page.

9. **Accessibility:**
   - Use `<main>`, `<article>`, `<nav>`, `<section>` semantically.
   - All form inputs must have associated `<label>` elements (can be visually hidden using `.sr-only` if needed for compact layout).
   - Meeting card links must have sufficient link text (not just "View Details" without context -- use `aria-label` to include the meeting name).
   - Pagination nav must have `aria-label`.

**Command:**
```
You are @coder building the civime-meetings WordPress plugin for civi.me.

Create two files for the meetings list page (the /meetings/ route):

1. `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-meetings-list.php`

Class CiviMe_Meetings_List — a controller that prepares data for the meetings list template. Requirements:
- ABSPATH guard.
- Reads filter values from $_GET in the constructor (sanitized with absint, sanitize_text_field, etc.):
  - council_id (int|null), date_from (string|null, default today Y-m-d), date_to (string|null, default today+30 days), q (string|null), page (int, default 1).
- Constant MEETINGS_PER_PAGE = 20.
- get_meetings(): array|WP_Error — calls civime_api()->get_meetings() with the filter args. Passes limit = MEETINGS_PER_PAGE, offset = (page-1)*MEETINGS_PER_PAGE.
- get_councils_for_filter(): array|WP_Error — calls civime_api()->get_councils(['has_upcoming' => true]).
- get_meetings_grouped_by_date(array $meetings): array — groups flat meetings array by date key (Y-m-d), sorted chronologically. Assumes each meeting has a 'date' or 'meeting_date' field.
- get_current_filters(): array — returns the current filter values as an assoc array.
- get_pagination_data(array $api_response): array — extracts from $api_response['meta']: current_page, total_pages, total_meetings, per_page, has_prev, has_next. Graceful defaults if meta missing.
- format_meeting_date(string $date): string — Y-m-d to "Thursday, March 15, 2026" using wp_date().
- format_meeting_time(string $time): string — H:i or H:i:s to "1:30 PM" using wp_date().

2. `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/templates/meetings-list.php`

Full page template. Structure:
- get_header() at top, get_footer() at bottom.
- Instantiate CiviMe_Meetings_List, fetch meetings and councils.
- <main id="main" class="site-main" role="main">
- Page header with <h1> "Meetings" and subtitle text about browsing upcoming meetings.
- Filter form: GET form to /meetings/ with: council <select>, date_from <input type="date">, date_to <input type="date">, q <input type="search">, submit button. Use theme form classes (form-control, form-group, form-label). Pre-fill from current filters. Show "Clear filters" link when any filter is active.
- Error state: if WP_Error, show <div class="notice notice--warning"> with friendly message (never expose raw API errors).
- Empty state: if valid but no data, show notice with "No meetings found" message.
- Date-grouped cards: for each date group, <section> with <h2> date heading, then meeting <article class="card meetings-card"> cards. Each card shows: time, council name, meeting title (link to detail), location, "View Details" button.
- Pagination nav with Previous/Next and "Page X of Y".
- All output escaped with esc_html(), esc_url(), esc_attr().
- Accessible: labels on all inputs, aria-label on nav and card links, semantic HTML.

Use the civime theme design system classes: .container, .section, .page-header, .page-header__title, .card, .card__title, .card__body, .card__footer, .btn, .btn--primary, .btn--secondary, .btn--sm, .form-control, .form-group, .form-label, .notice, .notice--warning, .sr-only.

For meeting data, assume the API response has: { data: [...meetings], meta: { total, limit, offset } } and each meeting has fields: state_id, title (or notice), council_name, meeting_date (Y-m-d), meeting_time (H:i:s), location, zoom_url. Use defensive coding — check if keys exist before accessing.

WordPress coding standards. Text domain: 'civime-meetings'.
```

---

## Phase 3: Meeting Detail Controller + Template

**Agent:** `@coder`
**Goal:** Build the single meeting detail page controller and template for the `/meetings/{state_id}` route.
**Depends On:** Phase 1
**Files to create:**
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-meeting-detail.php`
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/templates/meeting-detail.php`

### Requirements for `class-meeting-detail.php` (`CiviMe_Meetings_Detail`):

1. **Constructor** accepts `string $state_id`. Stores it as a property.

2. **`get_meeting(): array|WP_Error`** — Calls `civime_api()->get_meeting($this->state_id)`. Returns the API response.

3. **`get_summary(): array|WP_Error`** — Calls `civime_api()->get_meeting_summary($this->state_id)`. Returns the API response.

4. **`get_ics_url(): string`** — Returns `civime_api()->get_meeting_ics_url($this->state_id)`.

5. **`get_council(): array|WP_Error`** — If the meeting data includes a `council_id`, calls `civime_api()->get_council($council_id)`. This provides richer council info for the detail page. Returns WP_Error if council_id is not available.

6. **`format_full_datetime(string $date, string $time): string`** — Formats the date and time together into "Thursday, March 15, 2026 at 1:30 PM".

7. **`get_back_url(): string`** — Returns the URL to go back to the meetings list. If `$_SERVER['HTTP_REFERER']` contains `/meetings` (and is from the same site), use it (preserves filters). Otherwise default to `home_url('/meetings/')`.

### Requirements for `templates/meeting-detail.php`:

1. At the top, get `$state_id = get_query_var('civime_meeting_id')`. If empty, do a WordPress `wp_redirect(home_url('/meetings/'))` and exit.
2. Instantiate `$detail = new CiviMe_Meetings_Detail($state_id)`.
3. Fetch meeting: `$response = $detail->get_meeting()`.

4. **Error handling:** If `$response` is WP_Error:
   - If error code is `civime_api_error_404`, show a styled "Meeting not found" page (similar to the theme's 404 pattern) with a link back to `/meetings/`.
   - For other errors, show a friendly "trouble loading" notice.

5. **Update the page title dynamically:** Use `add_filter('document_title_parts', ...)` at the top of the template (before `get_header()`) to set the title to the meeting's title/name + council name.

6. **Page structure:**
```
get_header()
<main id="main" class="site-main" role="main">
  <!-- Breadcrumb: Meetings > Council Name > Meeting Title -->
  <nav class="meetings-breadcrumb" aria-label="Breadcrumb">
    <ol>
      <li><a href="/meetings/">Meetings</a></li>
      <li><a href="/meetings/?council_id={id}">Council Name</a></li>
      <li aria-current="page">Meeting Title</li>
    </ol>
  </nav>

  <article class="section meeting-detail">
    <div class="container container--narrow">
      <!-- Header -->
      <header class="meeting-detail__header">
        <span class="meeting-detail__council">Board of Education</span>
        <h1 class="meeting-detail__title">General Business Meeting</h1>
        <div class="meeting-detail__meta">
          <!-- Date/time, location, zoom link -->
        </div>
        <div class="meeting-detail__actions">
          <!-- Add to Calendar button, Get Notified button -->
        </div>
      </header>

      <!-- AI Summary (if available) -->
      <!-- Agenda -->
      <!-- Attachments -->
    </div>
  </article>
</main>
get_footer()
```

7. **Meeting metadata block** — display these fields if they exist in the API response:
   - Date and time (formatted with `format_full_datetime`)
   - Location (physical address)
   - Zoom/virtual link (if present, render as a link with text "Join Online" and `target="_blank" rel="noopener"`)
   - Status (if the meeting has a status like "cancelled", show it prominently with `notice--warning` styling)

8. **Action buttons:**
   - "Add to Calendar" — links to the ICS URL from `$detail->get_ics_url()`. Use `<a class="btn btn--secondary btn--sm" download>` with an SVG calendar icon.
   - "Get Notified" — links to `/meetings/subscribe` (will be built in civime-notifications later). For now, just link to `home_url('/meetings/subscribe')`. Use `<a class="btn btn--primary btn--sm">`.

9. **AI Summary section:**
   - Fetch summary: `$summary_response = $detail->get_summary()`.
   - If available and not WP_Error, render inside a highlighted `<section class="meeting-detail__summary">` with a heading "Plain Language Summary" and the summary text inside `<div class="prose">`.
   - If not available, do not render this section at all (do not show an error).

10. **Agenda section:**
    - If the meeting response has an `agenda` or `agenda_text` field, render it in a `<section class="meeting-detail__agenda">` with heading "Agenda" and the text inside `<div class="prose">`. Use `wp_kses_post()` for the agenda content since it may contain HTML.

11. **Attachments section:**
    - If the meeting response has an `attachments` array, render each as a link. Each attachment should have `name`/`filename` and `url`. Render as a list of download links with file type icons.

12. **Council info sidebar/section:**
    - Show a small section at the bottom: "About [Council Name]" with a link to view all meetings for this council (`/meetings/?council_id=X`).

13. **Accessibility:** Proper heading hierarchy (h1 for meeting title, h2 for sections). Use `<time>` element for dates with `datetime` attribute.

**Command:**
```
You are @coder building the civime-meetings WordPress plugin for civi.me.

Create two files for the meeting detail page (/meetings/{state_id} route):

1. `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-meeting-detail.php`

Class CiviMe_Meetings_Detail — controller for single meeting view. Requirements:
- ABSPATH guard.
- Constructor takes string $state_id, stores as property.
- get_meeting(): array|WP_Error — calls civime_api()->get_meeting($this->state_id).
- get_summary(): array|WP_Error — calls civime_api()->get_meeting_summary($this->state_id).
- get_ics_url(): string — returns civime_api()->get_meeting_ics_url($this->state_id).
- get_council(): array|WP_Error — if meeting data has council_id, call civime_api()->get_council(). Requires get_meeting() to have been called first; store meeting data as property.
- format_full_datetime(string $date, string $time): string — returns "Thursday, March 15, 2026 at 1:30 PM" using wp_date().
- get_back_url(): string — returns referrer URL if it contains '/meetings' on same domain, else home_url('/meetings/').

2. `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/templates/meeting-detail.php`

Full page template. Structure:
- Get state_id from get_query_var('civime_meeting_id'). If empty, wp_redirect to /meetings/ and exit.
- Instantiate controller, fetch meeting data.
- Before get_header(), add filter on 'document_title_parts' to set title to meeting name + council.
- get_header() / get_footer().
- Error handling: 404 errors get a "Meeting not found" page. Other errors get a friendly notice.
- Breadcrumb nav: Meetings > Council Name > Meeting Title. Use <nav aria-label="Breadcrumb"> with <ol>.
- Main article with container--narrow:
  - Header: council name tag, h1 meeting title, meta block (date/time with <time> element, location, zoom link if present).
  - Action buttons: "Add to Calendar" (ICS download link) and "Get Notified" (link to /meetings/subscribe).
  - AI Summary section (only render if available, heading "Plain Language Summary", content in .prose div).
  - Agenda section (if agenda_text exists, heading "Agenda", content via wp_kses_post in .prose div).
  - Attachments section (if attachments array exists, render as download links list).
  - "About this Council" section at bottom with link to filter meetings by this council.
- All output escaped. Semantic HTML. Proper heading hierarchy (h1, h2, h3). WCAG compliant.

Assume API meeting response has fields: state_id, title/notice, council_name, council_id, meeting_date (Y-m-d), meeting_time (H:i:s), location, zoom_url, agenda_text, summary_text, attachments (array of {name, url, file_type}), status. Use defensive coding for missing fields.

Theme classes to use: .container, .container--narrow, .section, .card, .btn, .btn--primary, .btn--secondary, .btn--sm, .notice, .notice--warning, .prose, .sr-only.
Text domain: 'civime-meetings'. WordPress coding standards.
```

---

## Phase 4: Councils List Controller + Template

**Agent:** `@coder`
**Goal:** Build the council browse page controller and template for the `/meetings/councils/` route.
**Depends On:** Phase 1
**Files to create:**
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-councils-list.php`
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/templates/councils-list.php`

### Requirements for `class-councils-list.php` (`CiviMe_Meetings_Councils_List`):

1. **Constructor** reads search filter from `$_GET`:
   - `q` (string, optional) - keyword search for council name

2. **`get_councils(): array|WP_Error`** — Calls `civime_api()->get_councils()` with search param if provided. Returns the API response.

3. **`get_current_search(): string`** — Returns the current search query (sanitized).

4. **`get_meetings_url_for_council(int $council_id): string`** — Returns `home_url('/meetings/?council_id=' . $council_id)`.

### Requirements for `templates/councils-list.php`:

1. Full page template with `get_header()` / `get_footer()`.
2. Instantiate controller, fetch councils.

3. **Page structure:**
   - Page header: h1 "Councils", subtitle about browsing all councils in Hawaii.
   - Search form: single text input for searching council names + submit button.
   - Error state: friendly notice when API is unreachable.
   - Empty state: "No councils found" notice.

4. **Council listing:**
   - Render as a grid of cards (`card-grid card-grid--3`).
   - Each council card shows:
     - Council name as heading
     - Parent council (if exists)
     - Upcoming meeting count (if available from API)
     - "View Meetings" link to `/meetings/?council_id=X`
     - "Get Notified" link to `/meetings/subscribe` (future)

5. Use theme design system classes. All output escaped. Accessible.

**Command:**
```
You are @coder building the civime-meetings WordPress plugin for civi.me.

Create two files for the councils browse page (/meetings/councils/ route):

1. `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-councils-list.php`

Class CiviMe_Meetings_Councils_List — controller for council browse view. Requirements:
- ABSPATH guard.
- Constructor reads 'q' from $_GET (sanitize_text_field).
- get_councils(): array|WP_Error — calls civime_api()->get_councils() with q param if provided.
- get_current_search(): string — returns sanitized search query.
- get_meetings_url_for_council(int $council_id): string — returns home_url('/meetings/?council_id=' . $council_id).

2. `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/templates/councils-list.php`

Full page template:
- get_header() / get_footer().
- Instantiate controller, fetch councils.
- Page header: h1 "Councils", subtitle "Browse all government councils and boards across Hawaii."
- Search form: GET to /meetings/councils/, single <input type="search"> for council name + submit button. Pre-fill from current search. "Clear search" link if search is active.
- Error state: friendly notice when API unreachable. Empty state: "No councils found" notice.
- Council cards in card-grid card-grid--3 layout. Each card: council name as <h3>, parent council name (if present), upcoming meeting count badge (if available), "View Meetings" link (btn--secondary btn--sm) to /meetings/?council_id=X, "Get Notified" link (btn--ghost btn--sm).
- All output escaped. Semantic HTML. Accessible. Text domain 'civime-meetings'. WordPress coding standards.

Assume API councils response: { data: [...councils], meta: {...} }. Each council: id, name, parent_name (nullable), upcoming_meeting_count (nullable). Defensive coding for missing fields.

Theme classes: .container, .section, .page-header, .page-header__title, .card, .card__title, .card__body, .card__footer, .card-grid, .card-grid--3, .btn, .btn--secondary, .btn--ghost, .btn--sm, .form-control, .notice, .notice--warning, .sr-only.
```

---

## Phase 5: Shortcodes

**Agent:** `@coder`
**Goal:** Register shortcodes for embedding meeting components on any WordPress page.
**Depends On:** Phase 2
**Files to create:**
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/shortcodes.php`

### Requirements:

1. Register two shortcodes:
   - `[civime_meetings]` — Renders a compact meetings list. Accepts attributes: `council_id`, `limit` (default 5), `show_filters` (default "no"). Useful for embedding a meetings preview on the homepage or other pages.
   - `[civime_councils]` — Renders a compact council list/grid. Accepts attribute: `limit` (default 12).

2. Each shortcode handler should:
   - Start output buffering.
   - Call the API via `civime_api()`.
   - Render a simplified version of the respective template (no page header, no pagination for the short version).
   - If API fails, return a friendly fallback message.
   - Return the buffered output.

3. Enqueue the meetings CSS when any shortcode is used on a page (use `wp_enqueue_style` inside the shortcode handler -- WordPress allows late enqueueing in the body).

**Command:**
```
You are @coder building the civime-meetings WordPress plugin for civi.me.

Create one file:

`/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/shortcodes.php`

This file registers shortcodes for embedding meeting content on any WordPress page. Requirements:
- ABSPATH guard.
- Register shortcode [civime_meetings] with handler civime_meetings_shortcode($atts).
  Attributes: council_id (int|null), limit (int, default 5), show_filters (string "yes"|"no", default "no").
  Handler: use shortcode_atts, call civime_api()->get_meetings() with limit and optional council_id. On error, return a <div class="notice notice--warning"> with friendly message. On success, render a compact card list (no page header, no pagination) inside a <div class="civime-meetings-embed">. Each meeting: title linked to detail, date/time, council name, location. Use theme classes. Enqueue civime-meetings-css if not already enqueued.

- Register shortcode [civime_councils] with handler civime_councils_shortcode($atts).
  Attributes: limit (int, default 12).
  Handler: call civime_api()->get_councils(['has_upcoming' => true]) with limit. Render compact card grid. Each card: council name, meeting count, link to /meetings/?council_id=X. Enqueue civime-meetings-css.

Both shortcodes use output buffering (ob_start/ob_get_clean). All output escaped. Text domain 'civime-meetings'. WordPress coding standards.
```

---

## Phase 6: Plugin CSS

**Agent:** `@coder`
**Goal:** Create the plugin-specific stylesheet with styles for filters, meeting cards, detail page, councils grid, pagination, and all responsive/dark-mode variants.
**Depends On:** Phases 2, 3, 4 (needs to know all BEM class names used in templates)
**Files to create:**
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/assets/css/meetings.css`

### Requirements:

The theme already provides base card styles, button styles, form styles, typography, and layout classes. The plugin CSS should ONLY define styles specific to meetings pages — never override or duplicate theme styles. Use CSS custom properties from the theme (e.g., `var(--color-primary)`, `var(--space-4)`, `var(--font-heading)`, etc.).

1. **Filter bar** (`.meetings-filters`):
   - CSS grid layout: on mobile, single column. At 640px, 2-column. At 768px, a row layout with filters in a flex row with gap.
   - Background: `var(--color-surface)`, border, padding, border-radius matching theme cards.
   - The filter form should feel like a cohesive toolbar.

2. **Date group headings** (`.meetings-date-heading`):
   - Sticky positioning so the date heading sticks to the top as users scroll through meetings for that date.
   - Use `var(--font-heading)`, `var(--font-size-lg)`, `var(--color-text-secondary)`.
   - Subtle bottom border.
   - Top margin for separation between groups.

3. **Meeting cards** (`.meetings-card`):
   - The `.meetings-card__meta` section: flex row with gap, `var(--font-size-sm)`.
   - `.meetings-card__time`: font-weight 600, `var(--color-primary)`.
   - `.meetings-card__council`: `var(--color-text-secondary)`. Separator between time and council (e.g., a middot or pipe).
   - `.meetings-card__location`: `var(--color-text-secondary)`, `var(--font-size-sm)`, icon prefix (use a small inline SVG or CSS-based pin icon).

4. **Meeting detail page** (`.meeting-detail__*`):
   - `.meeting-detail__header`: margin-bottom for spacing.
   - `.meeting-detail__council`: eyebrow text above the title — small, uppercase, `var(--color-secondary)`, letter-spacing.
   - `.meeting-detail__title`: the h1, uses `var(--font-size-3xl)`.
   - `.meeting-detail__meta`: list of metadata items (date, location, zoom). Each item has an icon + text. Flex column with gap.
   - `.meeting-detail__actions`: flex row with gap, margin-top.
   - `.meeting-detail__summary`: highlighted section with left border (like a blockquote) using `var(--color-secondary)`.
   - `.meeting-detail__agenda`: normal prose section.
   - `.meeting-detail__attachments`: list of download links with file-type indicators.

5. **Breadcrumb** (`.meetings-breadcrumb`):
   - Horizontal list with `/` or `>` separators via `::before` pseudo-elements.
   - `var(--font-size-sm)`, `var(--color-text-secondary)`.
   - Padding matching the container.

6. **Councils grid** — mostly uses theme `.card-grid--3`. Add:
   - `.council-card__count`: badge showing upcoming meeting count.
   - `.council-card__parent`: `var(--font-size-sm)`, `var(--color-text-secondary)`.

7. **Pagination** (`.meetings-pagination`):
   - Flex row, centered, gap between prev/next and page indicator.
   - Margin-top to separate from content.

8. **Responsive:** Use the theme breakpoints (640px, 768px, 1024px). Mobile-first.

9. **Dark mode:** All colors should already adapt via CSS custom properties from the theme. If any meetings-specific colors are needed (e.g., backgrounds for the summary highlight), define them to work in both modes.

10. **Print styles:** Hide filters and pagination when printing.

11. **Compact embed styles** (`.civime-meetings-embed`): Simpler card styles for when meetings are embedded via shortcode on other pages.

**Command:**
```
You are @coder building the civime-meetings WordPress plugin for civi.me.

Create one file:

`/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/assets/css/meetings.css`

Plugin-specific CSS for meetings pages. The civime theme already provides: .card, .btn, .form-control, .container, .section, .notice, .prose, .page-header, and all CSS custom properties (colors, spacing, typography, shadows, transitions, border-radius). DO NOT duplicate theme styles. Only add meetings-specific styles. All colors must use CSS custom properties from the theme so dark mode works automatically.

Sections to include:

1. .meetings-filters: CSS grid layout. Mobile: 1 column. 640px: 2 columns. 768px: flex row. Background var(--color-surface), border 1px solid var(--color-border), border-radius var(--border-radius-lg), padding var(--space-4). Gap var(--space-4).

2. .meetings-date-heading: font-family var(--font-heading), font-size var(--font-size-lg), color var(--color-text-secondary), font-weight 600, padding-block var(--space-3), margin-block-start var(--space-8), border-block-end 1px solid var(--color-border). Position sticky, top calc(var(--nav-height) + var(--space-2)), background var(--color-bg), z-index 10.

3. .meetings-card: extend .card (already applied). .meetings-card__meta: flex, gap var(--space-3), font-size var(--font-size-sm), margin-block-end var(--space-2). .meetings-card__time: font-weight 600, color var(--color-primary). .meetings-card__council: color var(--color-text-secondary). Add a separator (::before pseudo with middot). .meetings-card__location: font-size var(--font-size-sm), color var(--color-text-secondary).

4. .meetings-card + .meetings-card: margin-block-start var(--space-4) (spacing between cards).

5. .meeting-detail__council: display inline-block, font-size var(--font-size-sm), font-weight 600, text-transform uppercase, letter-spacing 0.08em, color var(--color-secondary), margin-block-end var(--space-2).

6. .meeting-detail__title: margin-block-end var(--space-6).

7. .meeting-detail__meta: display flex, flex-direction column, gap var(--space-3), margin-block-end var(--space-6). .meeting-detail__meta-item: display flex, align-items center, gap var(--space-2), font-size var(--font-size-base), color var(--color-text-secondary).

8. .meeting-detail__actions: display flex, flex-wrap wrap, gap var(--space-3), margin-block-end var(--space-8), padding-block-end var(--space-8), border-block-end 1px solid var(--color-border).

9. .meeting-detail__summary: border-inline-start 4px solid var(--color-secondary), padding var(--space-6), background rgba from var(--color-secondary) at 0.05 opacity, border-radius var(--border-radius), margin-block-end var(--space-8).

10. .meeting-detail__agenda, .meeting-detail__attachments: margin-block-end var(--space-8).

11. .meeting-detail__attachments-list: list-style none, padding 0, display flex, flex-direction column, gap var(--space-2). .attachment-link: display inline-flex, gap var(--space-2), align-items center, padding var(--space-2) var(--space-3), border-radius var(--border-radius-sm), text-decoration none, transition background var(--transition-fast). .attachment-link:hover: background var(--color-bg).

12. .meetings-breadcrumb: padding var(--space-4) 0, font-size var(--font-size-sm). .meetings-breadcrumb ol: list-style none, display flex, flex-wrap wrap, gap var(--space-1), padding 0, margin 0. .meetings-breadcrumb li + li::before: content ">" (or similar chevron), margin-inline-end var(--space-1), color var(--color-text-secondary). .meetings-breadcrumb a: color var(--color-text-secondary), text-decoration none. .meetings-breadcrumb a:hover: color var(--color-primary), text-decoration underline.

13. .council-card__parent: font-size var(--font-size-sm), color var(--color-text-secondary), margin-block-end var(--space-2). .council-card__count: display inline-flex, align-items center, gap var(--space-1), font-size var(--font-size-sm), font-weight 600, color var(--color-primary).

14. .meetings-pagination: display flex, justify-content center, align-items center, gap var(--space-4), padding-block-start var(--space-8), margin-block-start var(--space-8), border-block-start 1px solid var(--color-border). .meetings-pagination__info: font-size var(--font-size-sm), color var(--color-text-secondary).

15. .civime-meetings-embed: .civime-meetings-embed .meetings-card: box-shadow none, border none, padding var(--space-3), border-block-end 1px solid var(--color-border), border-radius 0.

16. Print styles: @media print { .meetings-filters, .meetings-pagination, .meeting-detail__actions { display: none; } }

Mobile-first approach. WordPress CSS conventions.
```

---

## Phase 7: Plugin JavaScript

**Agent:** `@coder`
**Goal:** Create the minimal JavaScript for filter form UX enhancements.
**Depends On:** Phase 2
**Files to create:**
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/assets/js/meetings.js`

### Requirements:

Keep it minimal -- this is a progressive enhancement. The page works fully without JavaScript.

1. **Auto-submit on select change:** When the council dropdown value changes, auto-submit the filter form. This avoids requiring users to click "Filter" after selecting a council.

2. **Clear individual filters:** If a "clear" button exists next to a filter field, clicking it clears that field and submits the form.

3. **Search debounce:** Do NOT auto-submit on keystroke in the search field. Users should press Enter or click Filter. This is intentional.

4. **Smooth scroll:** After page load, if there's a hash in the URL (e.g., from pagination), scroll smoothly to the content area.

5. **No framework dependencies.** Vanilla JS only. Use `DOMContentLoaded` event. Use `querySelector`/`querySelectorAll`. Modern JS (ES2020+) is fine since the script is loaded with `defer`.

6. **Wrapped in an IIFE** to avoid polluting the global scope.

**Command:**
```
You are @coder building the civime-meetings WordPress plugin for civi.me.

Create one file:

`/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/assets/js/meetings.js`

Minimal progressive enhancement JavaScript. No frameworks. Vanilla JS (ES2020+). Wrapped in an IIFE.

Requirements:
1. Auto-submit filter form when council <select> changes. Find the form by class .meetings-filters__form (or form inside .meetings-filters). On the select element's 'change' event, call form.submit().
2. "Clear filters" functionality: if buttons with [data-clear-filter] exist, clicking them clears the associated input and submits.
3. Do NOT auto-submit on search input keystrokes. Users must press Enter or click Filter.
4. On DOMContentLoaded, if URL has a hash, smooth scroll to it.
5. IIFE wrapper. No global pollution. Short, clean, well-commented.
```

---

## Phase 8: Router Refinement — Dynamic Detail Title

**Agent:** `@coder`
**Goal:** Update the router class to support dynamic page titles for meeting detail pages by deferring to the template's title filter, and ensure the router handles edge cases properly.
**Depends On:** Phases 1, 3
**Files to modify:**
- `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-meetings-router.php`

### Requirements:

1. The meeting detail template (Phase 3) adds its own `document_title_parts` filter to set the title to the actual meeting name. The router's default title filter (which sets "Meeting Detail") should use a low priority (e.g., 5) so the template filter (at default priority 10) overrides it.

2. Add a `redirect_trailing_slash` method on the `template_redirect` action: if someone visits `/meetings` (no trailing slash), redirect to `/meetings/` with a 301. Same for `/meetings/councils`. This prevents duplicate content.

3. Ensure the router sets `status_header(200)` before returning the template path in `template_include`. Without this, WordPress might return a 404 status code since there is no real post for these URLs.

4. Add `is_meetings_page(): bool` static helper method that returns true if the current request is any meetings route (useful for conditional logic elsewhere).

**Command:**
```
You are @coder working on the civime-meetings WordPress plugin for civi.me.

Modify the file `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/includes/class-meetings-router.php` (the CiviMe_Meetings_Router class created in Phase 1).

Add these refinements:

1. The document_title_parts filter callback should run at priority 5 (not default 10), so that the meeting detail template can override the title at priority 10 with the actual meeting name.

2. Add a template_redirect hook (in the constructor) that handles trailing slash normalization: if the request URI is exactly '/meetings' or '/meetings/councils' (no trailing slash), issue a 301 redirect to the version with trailing slash. Use wp_redirect and exit.

3. In the template_include callback, call status_header(200) before returning the plugin template path. This ensures WordPress sends a 200 OK status code instead of 404, since these are virtual pages with no real WP_Post.

4. Also in template_include, set global $wp_query->is_404 = false and $wp_query->is_page = true when a meetings route matches. This prevents theme 404 handling from kicking in.

5. Add a public static method is_meetings_page(): bool that returns true if get_query_var('civime_route') is non-empty.

Read the existing file first, then apply these changes. Keep all existing functionality intact. WordPress coding standards.
```

---

## Phase 9: QA Review

**Agent:** `@qa-security-reviewer`
**Goal:** Review all plugin files for security issues, accessibility compliance, and code quality.
**Depends On:** Phases 1-8

**Command:**
```
You are @qa-security-reviewer reviewing the civime-meetings WordPress plugin at `/home/patrickgartside/dev/civi.me/wp-content/plugins/civime-meetings/`.

Conduct a thorough review covering:

1. SECURITY:
   - All user input from $_GET is properly sanitized (absint, sanitize_text_field, etc.)
   - All output is properly escaped (esc_html, esc_attr, esc_url, wp_kses_post)
   - No direct database queries (all data comes via the civime_api() client)
   - No CSRF vulnerabilities (the filter forms use GET, which is correct for idempotent requests)
   - Rewrite rules cannot be exploited (state_id regex is safe)
   - No information disclosure in error states (raw API errors not shown to users)

2. ACCESSIBILITY (WCAG 2.1 AA):
   - All form inputs have associated labels
   - Interactive elements have 44px minimum touch targets
   - Focus states are visible (inherited from theme)
   - Semantic HTML structure (headings, landmarks, lists)
   - ARIA attributes used correctly (aria-label, aria-current, aria-hidden)
   - Screen reader considerations (sr-only text where needed)
   - Color contrast (verify CSS custom property usage)

3. CODE QUALITY:
   - WordPress coding standards compliance
   - Proper use of WordPress APIs (wp_date, esc_*, add_rewrite_rule, etc.)
   - Defensive coding for missing API response fields
   - No PHP warnings/notices for undefined array keys
   - Text domain consistency ('civime-meetings')
   - No hardcoded URLs (use home_url())

4. GRACEFUL DEGRADATION:
   - Templates render usefully when API returns WP_Error
   - Templates handle empty data arrays
   - Missing optional fields (zoom_url, agenda_text, summary, attachments) do not cause errors
   - Plugin works when civime-core is deactivated (admin notice, no fatal)

Report findings with file paths and line numbers. Categorize as: Critical, Warning, or Suggestion.
```

---

## Execution Summary

**Critical path:** Phase 1 (router) => Phase 2 (list) + Phase 3 (detail) + Phase 4 (councils) [parallel] => Phase 6 (CSS) => Phase 9 (QA)

**Parallel tracks:**
- Phases 2, 3, and 4 can all be built in parallel once Phase 1 is complete, since they are independent controllers/templates that all depend only on the router.
- Phase 5 (shortcodes) depends only on Phase 2 and can run in parallel with Phases 3 and 4.
- Phase 7 (JS) can be built any time after Phase 2.
- Phase 8 (router refinement) should run after both Phase 1 and Phase 3 are complete.

**Estimated complexity:** Medium. The core architecture pattern (custom rewrite rules + template hijacking) is the most technically involved piece and is addressed in Phase 1. The remaining phases are primarily template rendering with proper escaping and error handling.

**Key risks:**
1. Rewrite rules require a flush after activation. The activation hook handles this, but if the plugin is already active during development, the developer will need to manually visit Settings > Permalinks (or run `flush_rewrite_rules()` from WP-CLI) to pick up new rules.
2. The API is not yet built, so all development will be against WP_Error responses. The templates should still render their full structure (headers, filters, pagination) even when the data sections show fallback messages — this makes it easy to verify layout before the API exists.
3. The autoloader prefix is `CiviMe_Meetings_` (not `CiviMe_` like core). This avoids class name collisions between plugins but means the file naming strips a different prefix. The plan is explicit about this.
