# Tech Debt Log

This log inventories issues surfaced during documentation phases 1–6. Each item has been assessed for impact (the observable effect if left unaddressed) and priority (when to address it). Priority scale:

- **Fix Now** — address in the next immediate work session; blocking or high-impact
- **Next Phase** — address in an upcoming planned phase; significant but not immediately blocking
- **Backlog** — track but no urgency; low impact or low confidence in scope

---

## Live Bugs

Active failures affecting users on the live site. These items also appear as Tier 0 in the Feature Roadmap.

| ID | Description | File / Location | Impact | Priority |
|----|-------------|-----------------|--------|----------|
| B-1 | Translation dropdown does not persist language across navigation | `wp-content/plugins/civime-i18n/` | CRITICAL — 15 OLA languages effectively inaccessible; language is lost on every page navigation | Fix Now |
| B-2 | Meeting dates scraped incorrectly for some sources | `Access100/app website/public_html/api/cron/scrape.php` | HIGH — users see wrong meeting dates and may miss actual government meetings | Fix Now |

---

## Documentation Accuracy

Inaccuracies and gaps in the documentation itself, surfaced during Phases 3–6 verification.

| ID | Description | File / Location | Impact | Priority |
|----|-------------|-----------------|--------|----------|
| A-1 | `SCHEMA.md` `confirm_token` note states "cleared after use" but token is NOT cleared | `docs/data-model/SCHEMA.md` line 273 | HIGH — misleads contributors implementing the subscription flow; a developer following this note would add incorrect cleanup logic | Fix Now |
| A-2 | `PLUGINS.md` missing cross-reference link to `SCHEMA.md` | `docs/plugins/PLUGINS.md` | LOW — cross-reference quality issue; contributor readiness is not impacted | Backlog |
| A-3 | Three broken anchors in `PLUGINS.md` cross-links (`#meetings-1`, `#reminders-1`, `#councils-1`) | `docs/plugins/PLUGINS.md` | LOW — users navigating from PLUGINS.md to ENDPOINTS.md sections land on the page but not the anchor | Next Phase |

---

## Missing Infrastructure

Structural gaps that increase risk or reduce developer confidence.

| ID | Description | File / Location | Impact | Priority |
|----|-------------|-----------------|--------|----------|
| C-1 | No automated tests anywhere in the codebase | Both WordPress plugins and Access100 API | HIGH — every change requires manual verification; regressions are caught only in production | Next Phase |
| C-2 | No CI/CD pipeline | Both repositories | MEDIUM — deployment is fully manual; no automated quality gates on pull requests | Backlog |
| C-3 | No monitoring or alerting for cron system | Access100 API cron jobs (scraper, notify, digest, reminders, classify, cleanup) | HIGH — silent cron failures stop core value delivery with no signal to operators | Next Phase |

---

## Unfinished Features

Code that exists in the repository but is not connected to any user-facing capability.

| ID | Description | File / Location | Impact | Priority |
|----|-------------|-----------------|--------|----------|
| D-1 | `topics` table exists with no API endpoints | Access100 database `topics` table | LOW current — classification cron runs but results go nowhere; no user-facing topic subscriptions | Backlog |
| D-2 | `civime-events` plugin is a stub | `wp-content/plugins/civime-events/` | LOW — CPT registered, no content strategy, no API connection; represents unused registered post type | Backlog |
| D-3 | C4 Container diagram scoped out of Phase 2 | `docs/architecture/OVERVIEW.md` | LOW — Context diagram covers the high-level system boundary; Container diagram would add component detail for contributors | Backlog |

---

## OpenAPI Spec Quality

Issues with the OpenAPI specification that affect spec standards compliance but not runtime behavior.

| ID | Description | File / Location | Impact | Priority |
|----|-------------|-----------------|--------|----------|
| E-1 | 59 `nullable: true` occurrences use OAS 3.0 pattern in an OAS 3.1 spec | `docs/api/openapi.yaml`, `docs/api/.redocly.yaml` | LOW — spec validates but uses the older OAS 3.0 nullable pattern; Redocly struct rule disabled to suppress lint warnings | Backlog |
