---
phase: 06-contributor-artifacts
plan: 01
subsystem: docs
tags: [mkdocs, mkdocs-material, mermaid, civic-json, documentation-site]

# Dependency graph
requires:
  - phase: 02-architecture-overview
    provides: architecture docs (OVERVIEW.md, ROUTING.md, DATA-FLOW.md, CACHING.md)
  - phase: 03-api-and-data-model
    provides: API docs (ENDPOINTS.md, SUBSCRIPTION-LIFECYCLE.md, openapi.yaml, redoc.html, SCHEMA.md)
  - phase: 04-wordpress-plugin-documentation
    provides: plugin reference (PLUGINS.md)
  - phase: 05-infrastructure-documentation
    provides: infrastructure guide (INFRASTRUCTURE.md)
provides:
  - MkDocs Material site config (mkdocs.yml) consolidating all phases 2-5 docs
  - docs/index.md site homepage with section links and getting started paths
  - docs/api/api-reference.md Redoc iframe wrapper page
  - civic.json civic tech metadata at repo root
affects: [06-02-contributor-guide, github-pages-deploy]

# Tech tracking
tech-stack:
  added: [mkdocs-material 9.7.5, pymdownx.superfences, ghp-import]
  patterns: [mkdocs-material Material theme, Mermaid via pymdownx custom_fences, Redoc iframe embed]

key-files:
  created:
    - mkdocs.yml
    - docs/index.md
    - docs/api/api-reference.md
    - civic.json
  modified: []

key-decisions:
  - "mkdocs-material 9.x installed via pip — MkDocs 2.0 not used (plugin system removed, breaking changes)"
  - "Redoc iframe uses relative src=redoc.html (same directory) — api-reference.md and redoc.html both in docs/api/"
  - "civic.json validation = valid JSON + recognized status/type values — no formal CLI validator exists for BetaNYC spec"
  - "CONTRIBUTING.md not added to MkDocs nav — lives at repo root outside docs_dir, link from index.md instead"

patterns-established:
  - "MkDocs nav mirrors docs/ directory structure exactly — no restructuring of existing Phase 2-5 files"
  - "Search plugin explicitly listed in plugins: section to prevent silent disable when other plugins present"

requirements-completed: [CONTRIB-04, CONTRIB-03]

# Metrics
duration: 1min
completed: 2026-03-16
---

# Phase 6 Plan 01: MkDocs Material Documentation Site and civic.json Summary

**MkDocs Material 9.7.5 site config with dark/light toggle, Mermaid, search, Redoc iframe, and civic.json Beta/Hawaii metadata**

## Performance

- **Duration:** ~1 min
- **Started:** 2026-03-16T03:31:03Z
- **Completed:** 2026-03-16T03:32:27Z
- **Tasks:** 2
- **Files modified:** 4 created

## Accomplishments

- mkdocs.yml: Material theme with dark/light palette, navigation.tabs, navigation.instant, search, Mermaid via pymdownx.superfences custom_fences, all 12 Phase 2-5 doc files in nav
- docs/index.md: Site homepage with section index, getting started paths for new contributors and code contributors
- docs/api/api-reference.md: Redoc iframe wrapper (900px height, full width) with OpenAPI YAML download button
- civic.json: Valid JSON with Beta status, Hawaii geography, Web App type, GPL-2.0 license, Code for Hawaii birthplace

## Task Commits

1. **Task 1: MkDocs config, homepage, Redoc iframe** - `1f1e418` (feat)
2. **Task 2: civic.json metadata** - `a493b9d` (chore)

## Files Created/Modified

- `/home/patrickgartside/dev/civi.me/mkdocs.yml` — MkDocs Material configuration with full nav and Mermaid support
- `/home/patrickgartside/dev/civi.me/docs/index.md` — Docs site homepage
- `/home/patrickgartside/dev/civi.me/docs/api/api-reference.md` — Redoc iframe wrapper page
- `/home/patrickgartside/dev/civi.me/civic.json` — Civic tech metadata (BetaNYC/Code for America standard)

## Decisions Made

- mkdocs-material 9.x installed (9.7.5 specifically) — avoided MkDocs 2.0 which has removed plugin system
- Redoc iframe uses `src="redoc.html"` (same-directory relative path) since both files are in docs/api/
- civic.json "validation" = valid JSON + recognized enum values; no BetaNYC CLI validator exists
- CONTRIBUTING.md linked from docs/index.md rather than added to nav — MkDocs cannot include files outside docs_dir

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- `mkdocs build --strict` emits INFO lines about 3 broken anchors in PLUGINS.md cross-links to ENDPOINTS.md (e.g. `#meetings-1`, `#reminders-1`, `#councils-1`) — these are pre-existing Phase 4 issues, not caused by this plan. Exit code remains 0 (INFO not treated as errors by strict mode). Logged to deferred-items.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- MkDocs site builds cleanly and is ready for `mkdocs gh-deploy` to GitHub Pages
- civic.json in place for civic tech platform discoverability
- Ready for Plan 06-02: README.md and CONTRIBUTING.md contributor guide files

---
*Phase: 06-contributor-artifacts*
*Completed: 2026-03-16*

## Self-Check: PASSED

- mkdocs.yml: FOUND
- docs/index.md: FOUND
- docs/api/api-reference.md: FOUND
- civic.json: FOUND
- 06-01-SUMMARY.md: FOUND
- Commit 1f1e418 (Task 1): FOUND
- Commit a493b9d (Task 2): FOUND
