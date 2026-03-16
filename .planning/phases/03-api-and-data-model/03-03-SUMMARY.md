---
phase: 03-api-and-data-model
plan: "03"
subsystem: database
tags: [mysql, mermaid, erdiagram, schema, access100]

requires:
  - phase: 03-api-and-data-model
    provides: RESEARCH.md with live DB schema queried from appwebsite-db-1 (all 18 tables)
  - phase: 02-architecture-overview
    provides: Mermaid diagram conventions and DATA-FLOW.md cross-reference patterns

provides:
  - "docs/data-model/SCHEMA.md — full column-level documentation for all 18 live database tables"
  - "Two embedded Mermaid erDiagram blocks (core domain + support/operations)"
  - "Token auth model section (users.confirm_token, users.manage_token, reminders.confirm_token)"
  - "Schema Notes documenting known divergences from migration files"

affects:
  - 03-api-and-data-model (SUBSCRIPTION-LIFECYCLE.md cross-reference target)
  - future contributors needing DB context without direct database access

tech-stack:
  added: []
  patterns:
    - "Mermaid erDiagram with simple type identifiers (varchar not varchar(255)) for GitHub compatibility"
    - "Two-diagram split: core domain tables separate from support/operations tables"
    - "Legacy column documentation: noted in prose, excluded from ER diagrams to reduce noise"

key-files:
  created:
    - docs/data-model/SCHEMA.md
  modified: []

key-decisions:
  - "Two-diagram approach: core domain (9 tables) and support/operations (9 tables) — avoids unreadably large single diagram"
  - "Legacy users columns documented in prose only, excluded from ER diagrams (6 columns: name, is_verified, verification_token, notification_email, notification_sms, notification_frequency)"
  - "Schema Notes section documents four known migration divergences (notification_queue, users legacy, meetings.status, three off-migration tables)"

patterns-established:
  - "Schema docs sourced from live DB (RESEARCH.md), not migration files — migrations diverge from reality"
  - "Mermaid erDiagram: omit parenthesized type sizes; use simple identifiers only"

requirements-completed:
  - DATA-01
  - DATA-02
  - DATA-03

duration: 10min
completed: 2026-03-16
---

# Phase 3 Plan 03: Database Schema Documentation Summary

**Full column-level documentation for all 18 Access100 MySQL tables with two Mermaid ER diagrams and token auth lifecycle detail**

## Performance

- **Duration:** ~10 min
- **Started:** 2026-03-16T01:13:00Z
- **Completed:** 2026-03-16T01:23:00Z
- **Tasks:** 1
- **Files modified:** 1

## Accomplishments

- Created `docs/data-model/SCHEMA.md` with complete documentation for all 18 live database tables
- Embedded two Mermaid `erDiagram` blocks: ER-CORE (users, subscriptions, councils, council_profiles, meetings, attachments, topics, topic_council_map, meeting_topics) and ER-SUPPORT (council_members, council_vacancies, council_legal_authority, reminders, notifications_log, scraper_state, poll_state, google_calendar_sync, keyword_subscriptions)
- Documented the three-token auth model with generation, validation, lifecycle, and scope for each token type
- Captured schema divergences from migration files in a dedicated Schema Notes section

## Task Commits

Each task was committed atomically:

1. **Task 1: Write SCHEMA.md — full table documentation with embedded ER diagrams** - `d0246b3` (feat)

## Files Created/Modified

- `docs/data-model/SCHEMA.md` — 18-table database schema documentation with two Mermaid ER diagrams, token auth model, and schema notes

## Decisions Made

- Used the two-diagram split (core domain + support/operations) recommended in RESEARCH.md — keeps diagrams readable while covering all 18 tables
- Legacy users columns (name, is_verified, verification_token, notification_email, notification_sms, notification_frequency) documented in prose only; excluded from ER diagrams to reduce diagram noise per plan spec
- The `updated_at` column on users is a legacy column documented in the table reference — distinct from the non-legacy `created_at`

## Deviations from Plan

None — plan executed exactly as written. All schema data was pre-queried in RESEARCH.md; no live DB access needed during execution.

## Issues Encountered

None. The RESEARCH.md provided complete and accurate schema data for all 18 tables. Mermaid type identifiers were simplified per the plan spec (e.g., `varchar` not `varchar(255)`).

## User Setup Required

None — no external service configuration required.

## Next Phase Readiness

- `docs/data-model/SCHEMA.md` is ready for cross-referencing from `docs/api/SUBSCRIPTION-LIFECYCLE.md` (03-04 or later plan)
- The token auth model section provides DATA-03 documentation that SUBSCRIPTION-LIFECYCLE.md can extend with the endpoint walkthrough
- DATA-01, DATA-02, and DATA-03 are all satisfied by this single document

---
*Phase: 03-api-and-data-model*
*Completed: 2026-03-16*
