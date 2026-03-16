# Requirements: civi.me Documentation & Roadmap

**Defined:** 2026-03-15
**Core Value:** A new contributor can read the docs and fully understand both systems — what exists, how it works, and what's planned next.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Baseline

- [x] **BASE-01**: All uncommitted work is committed to establish a clean, stable baseline
- [x] **BASE-02**: Status of all plugins assessed and documented (civime-core, civime-meetings, civime-notifications, civime-guides, civime-i18n, civime-events, civime-topics)

### Architecture

- [x] **ARCH-01**: Two-system architecture overview documenting WordPress frontend ↔ Access100 API boundary
- [x] **ARCH-02**: Cross-plugin routing map with priority system and namespace ownership
- [x] **ARCH-03**: Data flow diagrams showing how information moves between systems (Mermaid)
- [x] **ARCH-04**: Caching layer documentation (transient cache for public, bypass for admin)
- [x] **ARCH-05**: C4 Context and Container diagrams for the full system
- [x] **ARCH-06**: Architecture Decision Records (MADR) capturing rationale for key past decisions

### API Documentation

- [x] **API-01**: All Access100 API endpoint routes documented with request/response formats
- [x] **API-02**: Authentication flow documentation (X-API-Key server-to-server, token-based subscription auth)
- [x] **API-03**: Subscription lifecycle documented end-to-end (subscribe → email confirm → manage → unsubscribe)
- [x] **API-04**: OpenAPI 3.1 specification authored for all endpoints
- [x] **API-05**: Redoc-rendered API reference from the OpenAPI spec

### Data Model

- [x] **DATA-01**: Database schema documentation for all tables (users, subscriptions, meetings, councils, etc.)
- [x] **DATA-02**: Entity-relationship diagrams rendered in Mermaid
- [x] **DATA-03**: Token auth model documented (confirm_token, manage_token, session handling)

### WordPress Frontend

- [x] **WP-01**: Per-plugin documentation for all plugins (purpose, routes, classes, templates)
- [ ] **WP-02**: Theme documentation (Lexend + Source Sans 3, CSS custom properties, light/dark mode, responsive approach)
- [ ] **WP-03**: Plugin architecture pattern guide (Router → Controller → Template, autoloader, naming conventions)
- [x] **WP-04**: Admin dashboard documentation (5 controllers: Sync, Meetings, Reminders, Councils, Subscribers)
- [ ] **WP-05**: CSS/JS architecture guide (BEM naming, custom properties, IIFE pattern, progressive enhancement)

### Infrastructure

- [ ] **INFRA-01**: Docker Compose setup documented (wordpress:latest, mariadb:10.11, networking)
- [ ] **INFRA-02**: Local development setup guide (clone → running site)
- [ ] **INFRA-03**: Environment configuration documented (env vars, NPM proxy, SSL, bind mounts)

### Contributor Artifacts

- [ ] **CONTRIB-01**: Root README with project overview, architecture summary, and getting started
- [ ] **CONTRIB-02**: CONTRIBUTING.md with development workflow, coding standards, PR process
- [ ] **CONTRIB-03**: civic.json metadata file for civic tech discoverability
- [ ] **CONTRIB-04**: MkDocs Material docs site with all documentation consolidated

### Planning

- [ ] **PLAN-01**: Feature roadmap identifying and prioritizing what to build next
- [ ] **PLAN-02**: Detailed phase plans ready for execution on identified next features
- [ ] **PLAN-03**: Tech debt log of known issues surfaced during documentation

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Documentation Enhancements

- **DOC-01**: Error response catalog for all API error codes
- **DOC-02**: Changelog with version history
- **DOC-03**: Automated API spec validation in CI

## Out of Scope

| Feature | Reason |
|---------|--------|
| Building new features | This milestone is documentation and planning only |
| Code refactoring | Document current state; flag issues for future phases |
| API changes | Document as-is; changes come after planning |
| Automated testing | Separate concern; may emerge as a planning output |
| Deployment automation | Current manual process is documented, not changed |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| BASE-01 | Phase 1 | Complete |
| BASE-02 | Phase 1 | Complete |
| ARCH-01 | Phase 2 | Complete |
| ARCH-02 | Phase 2 | Complete |
| ARCH-03 | Phase 2 | Complete |
| ARCH-04 | Phase 2 | Complete |
| ARCH-05 | Phase 2 | Complete |
| ARCH-06 | Phase 2 | Complete |
| API-01 | Phase 3 | Complete |
| API-02 | Phase 3 | Complete |
| API-03 | Phase 3 | Complete |
| API-04 | Phase 3 | Complete |
| API-05 | Phase 3 | Complete |
| DATA-01 | Phase 3 | Complete |
| DATA-02 | Phase 3 | Complete |
| DATA-03 | Phase 3 | Complete |
| WP-01 | Phase 4 | Complete |
| WP-02 | Phase 4 | Pending |
| WP-03 | Phase 4 | Pending |
| WP-04 | Phase 4 | Complete |
| WP-05 | Phase 4 | Pending |
| INFRA-01 | Phase 5 | Pending |
| INFRA-02 | Phase 5 | Pending |
| INFRA-03 | Phase 5 | Pending |
| CONTRIB-01 | Phase 6 | Pending |
| CONTRIB-02 | Phase 6 | Pending |
| CONTRIB-03 | Phase 6 | Pending |
| CONTRIB-04 | Phase 6 | Pending |
| PLAN-01 | Phase 7 | Pending |
| PLAN-02 | Phase 7 | Pending |
| PLAN-03 | Phase 7 | Pending |

**Coverage:**
- v1 requirements: 31 total
- Mapped to phases: 31
- Unmapped: 0 ✓

---
*Requirements defined: 2026-03-15*
*Last updated: 2026-03-15 after roadmap creation — all requirements mapped*
