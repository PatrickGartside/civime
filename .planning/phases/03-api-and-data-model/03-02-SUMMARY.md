---
phase: 03-api-and-data-model
plan: 02
subsystem: documentation
tags: [api, endpoints, subscriptions, tokens, lifecycle]
dependency_graph:
  requires:
    - 03-01 (CONTEXT.md and RESEARCH.md — source of API surface knowledge)
    - docs/architecture/DATA-FLOW.md (Phase 2 output — cross-referenced, not modified)
  provides:
    - docs/api/ENDPOINTS.md (human-readable endpoint reference for all 52 routes)
    - docs/api/SUBSCRIPTION-LIFECYCLE.md (end-to-end subscription flow with token detail)
  affects:
    - 03-03 (openapi.yaml copy and validation — ENDPOINTS.md provides route inventory)
    - 03-04 (data model docs — token columns referenced in SUBSCRIPTION-LIFECYCLE.md)
tech_stack:
  added: []
  patterns:
    - Human-readable endpoint reference grouped by resource domain
    - Token model documentation (confirm_token vs manage_token separation)
    - Cross-reference to existing sequence diagram rather than duplicating it
key_files:
  created:
    - docs/api/ENDPOINTS.md
    - docs/api/SUBSCRIPTION-LIFECYCLE.md
  modified: []
decisions:
  - Admin routes documented in ENDPOINTS.md only — excluded from public OpenAPI spec (internal-only, API Key required, no third-party use case)
  - reminders.confirm_token documented as separate from users.confirm_token — important distinction for plugin developers
  - manage_token storage policy explicitly noted — WordPress never stores it; subscribers carry it in email
metrics:
  duration: ~8 minutes
  completed: 2026-03-15
  tasks_completed: 2
  files_created: 2
---

# Phase 3 Plan 02: API Endpoint Reference and Subscription Lifecycle Summary

**One-liner:** Human-readable endpoint reference for all 52 Access100 routes (public + admin) and end-to-end subscription lifecycle with confirm_token/manage_token token behavior at each step.

---

## What Was Built

### docs/api/ENDPOINTS.md

Full endpoint reference covering all 52 routes across 8 groups. Each route has: HTTP method, full path with `/api/v1/` prefix, auth requirement (Public / API Key / Token), and a one-sentence description.

- **29 public routes** — System (3), Meetings (4), Councils (8), Topics (3), Subscriptions (7), Reminders (2), Webhooks (2)
- **23 admin routes** — Subscribers (4), Reminders (2), Meetings (3), Scraper (5), Councils (9)
- Authentication section explains all three auth patterns (X-API-Key, manage_token, confirm_token) with behavioral detail
- Admin section includes internal-use callout explaining why these routes are not in the OpenAPI spec

### docs/api/SUBSCRIPTION-LIFECYCLE.md

End-to-end subscription flow document that extends the DATA-FLOW.md sequence diagram with API-level specifics.

- **Token Model section** — confirm_token vs manage_token: location in DB, format, generation timing, validation, lifecycle, and expiry (none for both)
- **7 lifecycle steps** — each with exact endpoint, auth, request body, response shape, DB state change, and token role
- **Reminder flow** — separate section for POST /reminders → GET /reminders/confirm, noting that reminders.confirm_token is independent of users.confirm_token
- **Plugin developer notes** — WordPress never stores manage_token; confirm/unsubscribe are public browser-callable endpoints; all other calls are server-to-server

---

## Route Count

Total routes documented in ENDPOINTS.md: **52**

| Group | Count |
|-------|-------|
| System | 3 |
| Meetings (public) | 4 |
| Councils (public) | 8 |
| Topics | 3 |
| Subscriptions | 7 |
| Reminders (public) | 2 |
| Webhooks | 2 |
| Admin — Subscribers | 4 |
| Admin — Reminders | 2 |
| Admin — Meetings | 3 |
| Admin — Scraper | 5 |
| Admin — Councils | 9 |

The RESEARCH.md listed 47+ routes. The actual count is 52 after accounting for all admin subgroups correctly.

---

## Routes Discovered Beyond the Interfaces Block

The plan's interfaces block listed 47+ routes. Actual count is 52. The extra 5 routes come from:
- `DELETE /admin/subscribers/{id}` supports both soft-delete and hard-delete via `?hard=true` — documented as one route with a note
- Admin Councils subgroup was listed with 9 routes in RESEARCH.md (matches the class-api-client.php implementation) vs the interfaces block which listed 8. The 9th route (`GET /admin/councils`) is the list endpoint that was in RESEARCH.md but not explicitly enumerated in the plan interfaces.

---

## Discrepancies Found

**meetings.status enum value:** RESEARCH.md flags that `API_GUIDE.md` examples show `"status": "scheduled"` but the live DB enum is `ENUM('active','cancelled','updated')`. ENDPOINTS.md avoids reproducing the example JSON for meeting status — descriptions reference "meeting status" generically. This discrepancy is noted in RESEARCH.md and should be resolved when the OpenAPI spec is reviewed in Plan 03-03.

**No other discrepancies** were found between API_GUIDE.md route descriptions and the endpoint handler source code reviewed during RESEARCH.md.

---

## Deviations from Plan

None — plan executed exactly as written.

---

## Self-Check

Checking created files and commits.

```bash
test -f docs/api/ENDPOINTS.md      → FOUND
test -f docs/api/SUBSCRIPTION-LIFECYCLE.md → FOUND
git log shows: 5de488e, b806079
DATA-FLOW.md unchanged: git diff docs/architecture/DATA-FLOW.md → 0 lines
```

## Self-Check: PASSED
