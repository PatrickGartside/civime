---
phase: 04-meta-tags
plan: 01
subsystem: seo
tags: [canonical, noindex, hreflang, robots, wp_head, meta-tags]

# Dependency graph
requires:
  - phase: 03-crawl-control
    provides: robots.txt crawl rules blocking parameterized meeting URLs and notification pages
  - phase: 02-i18n
    provides: civime-i18n plugin with CiviMe_I18n_Hreflang class outputting hreflang tags at priority 1
provides:
  - civime_is_noindex_page() helper in functions.php — shared noindex detection for meetings/notification routes
  - civime_meta_tags() hooked at wp_head priority 5 — canonical and robots meta for virtual pages
  - civime_remove_default_canonical() hooked at wp — unhooks WP core rel_canonical on virtual routes
  - Hreflang suppression on noindex pages via early return in CiviMe_I18n_Hreflang::output_tags()
affects:
  - 05-sitemap (if future phase adds sitemap — canonical URLs established here)
  - Any future phase modifying wp_head or hreflang output

# Tech tracking
tech-stack:
  added: []
  patterns:
    - WordPress wp_head priority ordering: hreflang(1) → preconnect(2) → civime_meta_tags(5) → WP core rel_canonical(10, removed on virtual routes)
    - function_exists() guard pattern when plugin calls theme function (dependency direction: plugin → theme helper)
    - Use $_SERVER['QUERY_STRING'] (not $_GET) to detect any query params on virtual routes

key-files:
  created: []
  modified:
    - wp-content/themes/civime/functions.php
    - wp-content/plugins/civime-i18n/includes/class-hreflang.php

key-decisions:
  - "Canonical URL hardcoded to https://civi.me (production domain) — consistent with robots.txt Sitemap directive pattern from Phase 3"
  - "civime_remove_default_canonical() hooks on wp not wp_head — must run before wp_head fires to unhook rel_canonical successfully"
  - "function_exists() guard in class-hreflang.php — if theme deactivated, hreflang still works normally rather than crashing"
  - "Council routes excluded from canonical/noindex — per CONTEXT.md, councils are out of scope for this phase"

patterns-established:
  - "Noindex helper in functions.php: centralizes noindex detection, consumed by both theme and plugin"
  - "wp hook for removing wp_head actions: correct hook point to modify wp_head pipeline before it runs"

requirements-completed: [META-01, META-02, META-03, META-04]

# Metrics
duration: 10min
completed: 2026-03-17
---

# Phase 4 Plan 1: Meta Tags Summary

**Canonical + noindex robots meta for virtual meeting/notification pages, with hreflang suppression guard preventing contradictory SEO signals**

## Performance

- **Duration:** ~10 min
- **Started:** 2026-03-17T00:00:00Z
- **Completed:** 2026-03-17T00:10:00Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- `civime_is_noindex_page()` helper centralizes noindex detection for filtered meetings and all notification routes
- `civime_meta_tags()` at wp_head priority 5 outputs canonical to `/meetings/` for all meetings-list views, self-referencing canonical for detail pages, noindex,follow for filtered meetings, noindex,nofollow for subscribe/manage/confirmed/unsubscribed
- WP core `rel_canonical()` removed on all virtual routes (meetings + notifications) — prevents duplicate/incorrect canonical
- `CiviMe_I18n_Hreflang::output_tags()` suppresses hreflang on noindex pages with `function_exists()` safety guard

## Task Commits

Each task was committed atomically:

1. **Task 1: Add civime_meta_tags and noindex helper to functions.php** - `ec8f8f2` (feat)
2. **Task 2: Suppress hreflang output on noindex pages in class-hreflang.php** - `dc83bdf` (feat)

## Files Created/Modified
- `wp-content/themes/civime/functions.php` - Added civime_is_noindex_page(), civime_meta_tags() at priority 5, civime_remove_default_canonical() on wp hook
- `wp-content/plugins/civime-i18n/includes/class-hreflang.php` - Added early return guard in output_tags() suppressing hreflang on noindex pages

## Decisions Made
- Canonical URL hardcoded to `https://civi.me` — matches the robots.txt Sitemap directive pattern established in Phase 3 (no `home_url()` which returns the current domain)
- `civime_remove_default_canonical()` hooks on `wp` not `wp_head` — must run before wp_head fires to successfully unhook rel_canonical at priority 10
- `function_exists('civime_is_noindex_page')` guard in class-hreflang.php — plugin must not crash if theme is deactivated
- Council routes (councils-list, council-profile) explicitly excluded from canonical/noindex per CONTEXT.md scope

## Deviations from Plan

None — plan executed exactly as written.

## Issues Encountered
- Meeting detail URL curl verification required testing with a dummy meeting ID (`/meetings/test-meeting-123/`) because the API requires auth and the meetings page loads data via server-side call — the self-referencing canonical verified correctly

## User Setup Required
None — no external service configuration required.

## Next Phase Readiness
- All canonical/noindex/hreflang SEO meta tags complete for v1.2 milestone
- Phase 4 is the final feature phase — Phase 5 (if any) would be deployment/verification

---
*Phase: 04-meta-tags*
*Completed: 2026-03-17*
