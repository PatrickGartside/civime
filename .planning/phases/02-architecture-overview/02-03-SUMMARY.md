---
phase: 02-architecture-overview
plan: 03
subsystem: docs
tags: [adr, madr, architecture, decisions, plugin-per-feature, token-auth]

# Dependency graph
requires:
  - phase: 02-architecture-overview
    provides: Context and research content for ADR rationale (02-CONTEXT.md, 02-RESEARCH.md)
provides:
  - MADR-format ADR documenting plugin-per-feature architecture decision with trade-offs
  - MADR-format ADR documenting token-based subscription auth decision with security trade-offs
affects: [future contributors, Phase 3 API docs, Phase 4 plugin development]

# Tech tracking
tech-stack:
  added: []
  patterns: [MADR plain markdown ADR format for architecture decisions]

key-files:
  created:
    - docs/decisions/ADR-001-plugin-per-feature.md
    - docs/decisions/ADR-002-token-based-auth.md
  modified: []

key-decisions:
  - "ADR format: used MADR (Markdown Architectural Decision Records) with exact section headings — Status, Context and Problem Statement, Decision Drivers, Considered Options, Decision Outcome, Positive Consequences, Negative Consequences"
  - "No date or author fields added to ADRs — MADR format does not include these"
  - "Both ADRs acknowledge trade-offs explicitly: inter-plugin coordination (ADR-001) and token compromise = subscription access (ADR-002)"

patterns-established:
  - "ADR pattern: MADR format with Positive/Negative Consequences subsections under Decision Outcome"

requirements-completed: [ARCH-06]

# Metrics
duration: 1min
completed: 2026-03-16
---

# Phase 2 Plan 03: Architecture Decision Records Summary

**Two MADR-format ADRs preserving the plugin-per-feature and token-based subscription auth decisions with full rationale and accepted trade-offs**

## Performance

- **Duration:** 1 min
- **Started:** 2026-03-16T00:40:45Z
- **Completed:** 2026-03-16T00:41:56Z
- **Tasks:** 2
- **Files modified:** 2 created

## Accomplishments

- ADR-001 documents the plugin-per-feature architecture choice: activation independence, clean dependency direction (all plugins -> civime-core, no cross-feature deps), incremental deployment — with inter-plugin coordination acknowledged as the accepted trade-off
- ADR-002 documents the token-based subscription auth choice: no WP account required, email-verified identity via confirm_token, WordPress deliberately stateless — with token-compromise-equals-access as the acknowledged security trade-off
- Both ADRs use exact MADR section headings, written for experienced developer audience

## Task Commits

Each task was committed atomically:

1. **Task 1: Write ADR-001-plugin-per-feature.md** - `95cb55f` (docs)
2. **Task 2: Write ADR-002-token-based-auth.md** - `d55d4ac` (docs)

## Files Created/Modified

- `docs/decisions/ADR-001-plugin-per-feature.md` - Plugin-per-feature architecture ADR (MADR format)
- `docs/decisions/ADR-002-token-based-auth.md` - Token-based subscription auth ADR (MADR format)

## Decisions Made

- Used MADR format exactly as specified — no custom section additions, no date/author fields
- Both ADRs lead with context (what problem was being solved) before stating the decision, making the reasoning legible without project background

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Architecture decision records for the two key structural decisions are now in `docs/decisions/`
- Ready for Phase 3 (API documentation) and remaining Phase 2 plans
- No blockers

---
*Phase: 02-architecture-overview*
*Completed: 2026-03-16*
