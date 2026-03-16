---
phase: 07-feature-roadmap-and-phase-plans
plan: 01
subsystem: documentation
tags: [roadmap, tech-debt, mkdocs, planning]

# Dependency graph
requires:
  - phase: 06-contributor-artifacts
    provides: MkDocs site foundation with navigation structure
  - phase: 03-api-and-data-model
    provides: OpenAPI spec and schema documentation (source of tech debt items)
  - phase: 04-wordpress-plugin-documentation
    provides: Plugin reference (source of tech debt items A-2, A-3)
provides:
  - Prioritized feature roadmap at docs/planning/ROADMAP.md (Tier 0/1/2)
  - Tech debt log at docs/planning/TECH-DEBT.md (5 categories, 12 items)
  - MkDocs Planning section in nav (ROADMAP.md, TECH-DEBT.md)
affects: [next-milestone, gsd-new-project, phase-08-if-exists]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Roadmap uses Tier 0/1/2 structure: bugs first, then features, then horizon"
    - "Tech debt items catalogued with ID, Description, File/Location, Impact, Priority"
    - "Priority scale: Fix Now / Next Phase / Backlog"

key-files:
  created:
    - docs/planning/ROADMAP.md
    - docs/planning/TECH-DEBT.md
  modified:
    - mkdocs.yml

key-decisions:
  - "Roadmap structured as Tier 0 (bugs), Tier 1 (next features), Tier 2 (horizon) — prioritized against civic accessibility mission"
  - "TECH-DEBT.md uses category tables (not freeform prose) with ID, Impact, Priority columns for machine-readability"
  - "No timeline or dates in roadmap — keeps items timeboxless, ordered by priority within each tier"
  - "Fix SCHEMA.md confirm_token note added as Tier 0 item since it's a documentation accuracy issue with Fix Now priority"

patterns-established:
  - "Planning docs live in docs/planning/ (visible to contributors on MkDocs site)"
  - "Roadmap entries include: System, Goal, Priority rationale, Success Criteria (observable truths)"
  - "Tech debt items are cross-referenced by ID (B-1, A-1, etc.) between TECH-DEBT.md and ROADMAP.md Tier 0"

requirements-completed: [PLAN-01, PLAN-02, PLAN-03]

# Metrics
duration: 3min
completed: 2026-03-16
---

# Phase 7 Plan 01: Feature Roadmap and Phase Plans Summary

**Prioritized feature roadmap and tech debt log concluding the documentation milestone — two live bugs (translation dropdown, meeting dates) as highest-priority Tier 0, four Tier 1 next-feature phases, six Tier 2 horizon items, and 12 categorized tech debt items with impact and priority assessment**

## Performance

- **Duration:** ~3 min
- **Started:** 2026-03-16T04:22:12Z
- **Completed:** 2026-03-16T04:25:32Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments

- Feature roadmap at `docs/planning/ROADMAP.md` with Tier 0/1/2 structure, phase-level entries for all Tier 0 and Tier 1 items
- Tech debt log at `docs/planning/TECH-DEBT.md` with 5 categories, 12 items, each with Impact and Priority
- MkDocs site updated to include Planning section — both docs visible to contributors

## Task Commits

Each task was committed atomically:

1. **Task 1: Write feature roadmap** - `0b88980` (feat)
2. **Task 2: Write tech debt log and update MkDocs nav** - `c0bca6f` (feat)

**Plan metadata:** (docs commit — pending)

## Files Created/Modified

- `docs/planning/ROADMAP.md` — Prioritized feature roadmap: Tier 0 (3 bug/doc fixes), Tier 1 (4 next features), Tier 2 (6 horizon items)
- `docs/planning/TECH-DEBT.md` — Categorized tech debt: Live Bugs, Documentation Accuracy, Missing Infrastructure, Unfinished Features, OpenAPI Spec Quality
- `mkdocs.yml` — Planning section added to nav after Plugins

## Decisions Made

- Roadmap uses Tier 0/1/2 structure (bugs → next features → horizon), ordered by alignment with civic accessibility mission
- TECH-DEBT.md uses category tables with explicit ID, Impact, Priority columns — enables downstream triage and cross-referencing
- No timeline or dates in roadmap — kept timeboxless per CONTEXT.md guidance, items ordered by priority within tier
- Fix SCHEMA.md confirm_token note included as Tier 0 item (documentation accuracy with Fix Now priority; small fix, high correctness value)

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Planning milestone complete — all 7 phases documented
- ROADMAP.md and TECH-DEBT.md feed directly into `/gsd:new-project` for the next development milestone
- Highest-priority work: Tier 0 bugs (translation dropdown persistence, meeting date scraper) — both have root cause analysis in 07-RESEARCH.md ready for implementation planning
- MkDocs site builds cleanly with new Planning section

---
*Phase: 07-feature-roadmap-and-phase-plans*
*Completed: 2026-03-16*
