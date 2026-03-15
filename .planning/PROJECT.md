# civi.me Documentation & Roadmap

## What This Is

A comprehensive review and documentation project for the civi.me civic engagement platform and its Access100 API backend. The goal is to produce contributor-ready documentation covering both systems end-to-end — API endpoints, WordPress frontend architecture, infrastructure, and data model — and then use that documentation as a baseline to identify and plan the next set of features and improvements.

## Core Value

A new contributor (or future-you after time away) can read the docs and fully understand both systems — what exists, how it works, and what's planned next.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Full API endpoint documentation (Access100 API — all routes, request/response formats, auth flows)
- [ ] WordPress frontend documentation (theme, plugins, templates, routing, CSS/JS architecture)
- [ ] Infrastructure documentation (Docker setup, networking, deployment, environment config)
- [ ] Data model documentation (database schema, relationships, subscription/notification flows)
- [ ] Contributor-facing docs (README, docs/ folder — visible on GitHub)
- [ ] Planning-facing docs (.planning/ structure — feeds into future phases)
- [ ] Commit all uncommitted work to establish a clean baseline before documentation begins
- [ ] Feature roadmap identifying what to build next based on documentation findings
- [ ] Detailed phase plans ready for execution on identified next steps

### Out of Scope

- Building new features — this project is about documenting what exists and planning what's next
- Refactoring existing code — document current state, flag issues for future phases
- API changes — the API is documented as-is; changes come after

## Context

civi.me is a civic engagement platform for Hawaii that makes government information functionally accessible. It consists of two systems:

**WordPress Frontend (civi.me):**
- Custom theme (Lexend + Source Sans 3, CSS custom properties, light/dark mode)
- civime-core plugin: API client (~30 methods), settings, admin dashboard (5 controllers)
- civime-meetings plugin: router, list/detail/council views
- civime-notifications plugin: subscribe/manage flows, 4 routes
- civime-guides plugin: guide content type
- civime-i18n plugin: internationalization
- Plugin-per-feature architecture, WCAG 2.1 AA, mobile-first

**Access100 API (access100.app):**
- Separate codebase at ~/dev/Access100/
- 20+ admin handler functions for councils/meetings/reminders/scraper CRUD
- Subscription flow: subscribe → email confirm → manage
- Cron system: scraper, notifications, digests, reminders, topic classification
- AI summaries via Claude API
- Gmail API for email (OAuth2 refresh token)

**Infrastructure:**
- Docker Compose: wordpress:latest + mariadb:10.11
- Nginx Proxy Manager for SSL/routing
- wp-content bind-mounted from dev project into container
- API container: appwebsite-app-1

**Current state:** Parts 1-3 of the original build are complete. There is significant uncommitted work in the repo that needs to be committed before documentation begins.

## Constraints

- **Two codebases**: WordPress at ~/dev/civi.me/, API at ~/dev/Access100/ — documentation must cover both
- **Open source**: Documentation must be appropriate for public GitHub repos
- **Existing users**: The platform is live — documentation reflects production state

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Commit uncommitted work first | Need a clean, stable baseline before documenting | — Pending |
| Dual documentation output | .planning/ for internal planning + docs/ for contributors | — Pending |
| Document both codebases | Full system understanding requires seeing both sides | — Pending |
| Documentation before features | Can't plan well without knowing what exists | — Pending |

---
*Last updated: 2026-03-15 after initialization*
