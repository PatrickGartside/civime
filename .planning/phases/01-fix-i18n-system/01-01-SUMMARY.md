---
phase: 01-fix-i18n-system
plan: 01
subsystem: i18n
tags: [csp, language-switcher, url-persistence, home_url, wp_nav_menu]

# Dependency graph
requires: []
provides:
  - CSP-compliant language switcher auto-submit via external JS
  - URL-based language persistence across all nav and plugin links
  - Expanded switcher param whitelist for meetings filters
affects: [02-fix-meetings-display]

# Tech tracking
tech-stack:
  added: []
  patterns: [external JS event listener for CSP compliance, home_url filter with context guards]

key-files:
  created:
    - wp-content/plugins/civime-i18n/assets/js/i18n.js
  modified:
    - wp-content/plugins/civime-i18n/civime-i18n.php
    - wp-content/plugins/civime-i18n/includes/class-switcher.php
    - wp-content/plugins/civime-i18n/includes/class-locale.php

key-decisions:
  - "External JS file with addEventListener instead of inline onchange for CSP compliance"
  - "home_url filter with admin/REST/cron/XMLRPC guards to avoid breaking backend URLs"
  - "Nav menu URL rewriting in translate_menu_items alongside title translation"

patterns-established:
  - "CSP compliance: Use external JS files with addEventListener, never inline handlers"
  - "URL filter guards: Always check is_admin, REST_REQUEST, DOING_CRON, XMLRPC_REQUEST before modifying URLs"

requirements-completed: [I18N-01, I18N-02, I18N-03, I18N-04, I18N-05, I18N-06, I18N-07]

# Metrics
duration: 2min
completed: 2026-03-17
---

# Phase 01 Plan 01: Fix I18n System Summary

**CSP-compliant language switcher with URL-based persistence via home_url filter and nav menu URL rewriting**

## Performance

- **Duration:** 2 min
- **Started:** 2026-03-17T05:07:14Z
- **Completed:** 2026-03-17T05:09:11Z
- **Tasks:** 2
- **Files modified:** 4

## Accomplishments
- Replaced CSP-blocked inline onchange handler with external JS event listener
- Added home_url filter to persist language choice across all plugin-generated URLs
- Extended nav menu item translation to also rewrite URLs with ?lang= parameter
- Expanded switcher param whitelist with 7 meetings filter params

## Task Commits

Each task was committed atomically:

1. **Task 1: Fix CSP-blocked language switcher auto-submit** - `9fefc7c` (feat)
2. **Task 2: Add URL language persistence and expand switcher params** - `2821625` (feat)

**Plan metadata:** `8a1dad4` (docs: complete plan)

## Files Created/Modified
- `wp-content/plugins/civime-i18n/assets/js/i18n.js` - External JS for language switcher auto-submit (IIFE, DOMContentLoaded, addEventListener)
- `wp-content/plugins/civime-i18n/civime-i18n.php` - Added wp_enqueue_script for i18n.js
- `wp-content/plugins/civime-i18n/includes/class-switcher.php` - Removed inline onchange, expanded allowed_params whitelist
- `wp-content/plugins/civime-i18n/includes/class-locale.php` - Added localize_home_url method with context guards, extended translate_menu_items for URL rewriting

## Decisions Made
- Used external JS file with addEventListener instead of CSP nonce approach -- simpler, no server-side nonce generation needed
- Placed home_url filter inside the existing non-English conditional block to avoid unnecessary processing for English users
- Removed early return for empty translations in translate_menu_items because URL rewriting must happen even when no title translations exist

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Language switching system is fully functional for all 15 OLA languages
- Cookie persistence confirmed working (no changes needed)
- Ready for meetings display fixes in phase 02

## Self-Check: PASSED

- [x] i18n.js created
- [x] SUMMARY.md created
- [x] Commit 9fefc7c exists (Task 1)
- [x] Commit 2821625 exists (Task 2)

---
*Phase: 01-fix-i18n-system*
*Completed: 2026-03-17*
