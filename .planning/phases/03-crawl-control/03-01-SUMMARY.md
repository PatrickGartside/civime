---
phase: 03-crawl-control
plan: 01
subsystem: infra
tags: [robots-txt, seo, crawl-budget, wordpress]

# Dependency graph
requires: []
provides:
  - robots_txt filter blocking parameterized /meetings/ URLs and functional notification pages
  - Sitemap directive pointing to https://civi.me/sitemap.xml
affects: [04-canonical-tags, 05-sitemap]

# Tech tracking
tech-stack:
  added: []
  patterns: [WordPress robots_txt filter for crawl rule injection from theme functions.php]

key-files:
  created: []
  modified: [wp-content/themes/civime/functions.php]

key-decisions:
  - "Used robots_txt filter (not do_robots action) to receive and return the output string cleanly"
  - "Used /meetings/*? wildcard syntax for query-param blocking — Googlebot-supported, correctly skips base /meetings/ and detail pages"
  - "Sitemap directive added now pointing to future Phase 5 sitemap — crawlers will 404 until created"

patterns-established:
  - "SEO/crawl rules live in theme functions.php, not plugin files"
  - "robots_txt filter pattern: append rules to $output string, return modified string"

requirements-completed: [CRAWL-01, CRAWL-02]

# Metrics
duration: 5min
completed: 2026-03-17
---

# Phase 03 Plan 01: Crawl Control — robots.txt Summary

**WordPress robots_txt filter blocking 304 parameterized meeting filter permutations and functional notification pages while preserving real content crawlability**

## Performance

- **Duration:** ~5 min
- **Started:** 2026-03-17T~T
- **Completed:** 2026-03-17
- **Tasks:** 1
- **Files modified:** 1

## Accomplishments
- Added `civime_robots_txt()` function to `functions.php` via the `robots_txt` filter
- Blocks `/meetings/*?` (parameterized filter URLs — the crawl budget problem)
- Blocks `/meetings/subscribe/`, `/notifications/manage/`, `/notifications/confirmed/`, `/notifications/unsubscribed/`
- Preserves crawlability of base `/meetings/`, individual meeting detail pages, and `/councils/`
- Adds `Sitemap: https://civi.me/sitemap.xml` directive ahead of Phase 5 sitemap creation

## Task Commits

Each task was committed atomically:

1. **Task 1: Add robots.txt Disallow rules via robots_txt filter** - `b007c6a` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `wp-content/themes/civime/functions.php` - Added `civime_robots_txt()` function and `add_filter('robots_txt', ...)` hook at end of file

## Decisions Made
- Used `robots_txt` filter instead of `do_robots` action — filter receives and returns the string, cleaner than echoing
- Used `/meetings/*?` wildcard for query param blocking (Googlebot supports wildcard; the `?` matches the literal query string delimiter, so `/meetings/` and `/meetings/12345/` are NOT blocked)
- Added Sitemap directive now even though Phase 5 creates the actual sitemap — standard practice, crawlers retry on 404

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- robots.txt crawl control is live — parameterized meeting URLs and functional notification pages are blocked
- Phase 4 (canonical tags) can proceed — the crawl budget problem is now addressed at the robots.txt layer
- Phase 5 (sitemap) should target `https://civi.me/sitemap.xml` to match the Sitemap directive added here

---
*Phase: 03-crawl-control*
*Completed: 2026-03-17*

## Self-Check: PASSED
- functions.php: FOUND
- 03-01-SUMMARY.md: FOUND
- commit b007c6a: FOUND
