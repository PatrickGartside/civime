# Roadmap: civi.me Documentation & Roadmap

## Overview

This milestone documents a complete, live civic engagement platform so that a new contributor can understand, extend, and maintain both systems without the original developer present. The work proceeds from a clean git baseline through architecture, API, WordPress, infrastructure, and contributor-facing documentation — culminating in a feature roadmap for what to build next. Each phase depends on the prior one having established the vocabulary, contracts, and patterns that subsequent documentation references.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Baseline Commit** - Commit all uncommitted work and assess plugin inventory to establish a stable, clonable baseline (completed 2026-03-15)
- [ ] **Phase 2: Architecture Overview** - Document the two-system boundary, routing map, data flows, and ADRs for key past decisions
- [ ] **Phase 3: API and Data Model** - Document all Access100 API endpoints, author the OpenAPI 3.1 spec, and produce the full data model with ER diagrams
- [ ] **Phase 4: WordPress Plugin Documentation** - Document all plugins and the theme with per-plugin reference covering routes, controllers, templates, and patterns
- [ ] **Phase 5: Infrastructure Documentation** - Document Docker setup, environment configuration, and local development workflow
- [ ] **Phase 6: Contributor Artifacts** - Produce the public-facing README, CONTRIBUTING.md, civic.json, MkDocs site, and tech debt log
- [ ] **Phase 7: Feature Roadmap and Phase Plans** - Identify next features, produce the feature roadmap, and write detailed phase plans for execution

## Phase Details

### Phase 1: Baseline Commit
**Goal**: The codebase is in a clean, committed state that contributors can clone and get the system that documentation describes
**Depends on**: Nothing (first phase)
**Requirements**: BASE-01, BASE-02
**Success Criteria** (what must be TRUE):
  1. `git status` returns clean with no modified or untracked files (or all remaining untracked files are intentionally gitignored)
  2. A contributor can clone the repo and find code that matches what the documentation will describe — no undocumented uncommitted changes exist
  3. Every plugin in the wp-content/plugins directory has a documented status: active/complete, active/stub, or out-of-scope
**Plans**: 1 plan

Plans:
- [ ] 01-01-PLAN.md — Update .gitignore, commit all uncommitted code in 13 logical groups, and create PLUGIN-STATUS.md

### Phase 2: Architecture Overview
**Goal**: The mental model for both systems is written down — a new contributor understands the two-system boundary, how data flows, and why key decisions were made, before reading any plugin-level docs
**Depends on**: Phase 1
**Requirements**: ARCH-01, ARCH-02, ARCH-03, ARCH-04, ARCH-05, ARCH-06
**Success Criteria** (what must be TRUE):
  1. A contributor can read one document and understand that WordPress never holds canonical data — all data lives in Access100 API and is fetched server-to-server via X-API-Key
  2. A contributor can look up any URL pattern and identify which plugin handles it, in what priority order, without reading source code
  3. Mermaid diagrams in the docs show how a public page request moves from browser through WordPress to the API and back, including the transient cache layer
  4. C4 Context and Container diagrams exist that place both systems in the full infrastructure picture
  5. Architecture Decision Records exist for at least the key past decisions (plugin-per-feature, token auth, server-side-only API calls, honeypot anti-spam)
**Plans**: 3 plans

Plans:
- [ ] 02-01-PLAN.md — Write OVERVIEW.md (two-system boundary + C4 context diagram) and ROUTING.md (URL-to-plugin routing table + priority explanation)
- [ ] 02-02-PLAN.md — Write DATA-FLOW.md (three Mermaid sequence diagrams) and CACHING.md (caching behavior reference)
- [ ] 02-03-PLAN.md — Write ADR-001-plugin-per-feature.md and ADR-002-token-based-auth.md in MADR format

### Phase 3: API and Data Model
**Goal**: The Access100 API is fully documented with a machine-readable OpenAPI 3.1 spec, a rendered reference, and a complete data model — so WordPress plugin docs can reference specific endpoints and schema fields without ambiguity
**Depends on**: Phase 2
**Requirements**: API-01, API-02, API-03, API-04, API-05, DATA-01, DATA-02, DATA-03
**Success Criteria** (what must be TRUE):
  1. Every Access100 API route has documented request parameters, response shape, auth requirements, and a real example response captured from the live system
  2. A contributor can import the OpenAPI 3.1 YAML into Postman and make authenticated calls to all endpoints without additional documentation
  3. The subscription lifecycle (subscribe → email confirm → manage → unsubscribe) is documented as an end-to-end flow with token behavior at each step
  4. A Mermaid ER diagram exists for all database tables with relationships, and token auth fields (confirm_token, manage_token) are explained in context
  5. A rendered Redoc API reference is accessible from the docs site with three-panel layout showing all endpoints
**Plans**: TBD

### Phase 4: WordPress Plugin Documentation
**Goal**: Every plugin and the theme has reference documentation that a contributor can read to understand what it does, how to extend it, and how it connects to the API — without reading source code first
**Depends on**: Phase 3
**Requirements**: WP-01, WP-02, WP-03, WP-04, WP-05
**Success Criteria** (what must be TRUE):
  1. For each plugin, a contributor can find: the plugin's purpose, its URL routes, its controller classes, its templates, and which API endpoints it calls
  2. The theme documentation covers the design system (CSS custom properties, Lexend/Source Sans 3, light/dark mode) and explains how to add a new page without breaking the layout
  3. The plugin architecture pattern guide (Router → Controller → Template, autoloader, naming conventions) is documented with enough detail that a contributor could scaffold a new plugin correctly
  4. The admin dashboard documentation covers all 5 controllers (Sync, Meetings, Reminders, Councils, Subscribers) and explains what each admin page does and how to navigate it
  5. The transient cache behavior is documented in civime-core with clear callouts in every plugin that uses cached API responses
**Plans**: TBD

### Phase 5: Infrastructure Documentation
**Goal**: A contributor can set up a local development environment and understand the production infrastructure from documentation alone — without asking the original developer
**Depends on**: Phase 2
**Requirements**: INFRA-01, INFRA-02, INFRA-03
**Success Criteria** (what must be TRUE):
  1. Following the local dev setup guide step-by-step produces a running WordPress site with both the civi.me theme and all plugins active
  2. Every environment variable required by both systems is documented with its purpose, where to find the value, and what breaks if it is missing
  3. The Docker Compose architecture (bind mounts, network, container roles) is documented clearly enough that a contributor can diagnose a container networking issue without asking for help
**Plans**: TBD

### Phase 6: Contributor Artifacts
**Goal**: The project is publicly contributor-ready — a developer who finds the repo on GitHub can orient themselves, understand how to contribute, and discover the project through civic tech channels
**Depends on**: Phase 4, Phase 5
**Requirements**: CONTRIB-01, CONTRIB-02, CONTRIB-03, CONTRIB-04
**Success Criteria** (what must be TRUE):
  1. The root README gives a new visitor a complete mental model in one read: what the project does, how the two systems relate, and where to go next (docs site, local setup, contributing)
  2. A developer who wants to contribute can follow CONTRIBUTING.md to set up their environment, understand the coding standards, and submit a pull request without additional guidance
  3. The MkDocs Material docs site builds and deploys with a single command, contains all documentation from phases 2-5, and has working search
  4. The civic.json file exists at the repo root and passes validation against the Code for America standard
**Plans**: TBD

### Phase 7: Feature Roadmap and Phase Plans
**Goal**: The next set of features and improvements are identified, prioritized, and planned with enough detail to execute — so the project can move from documentation into the next build phase
**Depends on**: Phase 6
**Requirements**: PLAN-01, PLAN-02, PLAN-03
**Success Criteria** (what must be TRUE):
  1. A feature roadmap exists identifying what to build next, with items grouped into coherent phases and prioritized against the project's core value
  2. Detailed phase plans exist for the highest-priority next items, with success criteria clear enough to verify completion
  3. A tech debt log exists capturing known issues surfaced during documentation phases, with each item assessed for impact and rough priority
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5 → 6 → 7
Note: Phase 5 depends only on Phase 2 and can run in parallel with Phases 3 and 4 if desired, but is sequenced here for simplicity.

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Baseline Commit | 1/1 | Complete    | 2026-03-15 |
| 2. Architecture Overview | 2/3 | In Progress|  |
| 3. API and Data Model | 0/TBD | Not started | - |
| 4. WordPress Plugin Documentation | 0/TBD | Not started | - |
| 5. Infrastructure Documentation | 0/TBD | Not started | - |
| 6. Contributor Artifacts | 0/TBD | Not started | - |
| 7. Feature Roadmap and Phase Plans | 0/TBD | Not started | - |
