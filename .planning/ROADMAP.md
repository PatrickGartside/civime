# Roadmap: civi.me

## Milestones

- ✅ **v1.1 Fix What's Broken** - Phases 1-2 (shipped 2026-03-17)
- 🚧 **v1.2 Fix Search Indexing** - Phases 3-5 (in progress)

## Phases

<details>
<summary>✅ v1.1 Fix What's Broken (Phases 1-2) - SHIPPED 2026-03-17</summary>

### Phase 1: Fix i18n System
**Goal**: A user can select any of the 15 OLA languages and navigate the entire site without losing their language choice — the switcher works, nav links carry `?lang=`, plugin URLs carry `?lang=`, and the cookie persists the choice across sessions.
**Depends on**: Nothing (first phase)
**Requirements**: I18N-01, I18N-02, I18N-03, I18N-04, I18N-05, I18N-06, I18N-07
**Success Criteria** (what must be TRUE):
  1. Selecting a language from the dropdown immediately submits the form and the page reloads in the selected language (CSP no longer blocks the auto-submit)
  2. Every nav menu link (desktop, mobile, footer) includes `?lang={slug}` when a non-English language is active
  3. Every plugin-generated URL (meetings filters, pagination, notification links, subscribe forms) includes `?lang={slug}` when a non-English language is active
  4. Switching languages on the meetings page with active filters preserves those filters
  5. The `civime_lang` cookie is set on language selection and restores the language on return visits without `?lang=` in the URL
  6. WP admin pages and REST API requests do not have `?lang=` injected into their URLs
**Plans**: 1 plan

Plans:
- [x] 01-01-PLAN.md — Fix CSP-blocked switcher (move onchange to JS file), add home_url filter + nav menu URL filter for language persistence, expand switcher allowed params

### Phase 2: Cleanup
**Goal**: Dead code from the disabled dark mode feature is removed and the SCHEMA.md confirm_token documentation is corrected.
**Depends on**: Phase 1
**Requirements**: CLN-01, CLN-02
**Success Criteria** (what must be TRUE):
  1. The `civime_inline_theme_script()` function and its `wp_head` hook are removed from `functions.php` — no inline script is injected into `<head>` for dark mode flash prevention
  2. SCHEMA.md `confirm_token` note no longer states "cleared after use" — reflects that `confirmed_email=1` is the authoritative confirmation state and the token is retained
**Plans**: 1 plan

Plans:
- [x] 02-01-PLAN.md — Remove dead dark mode inline script from functions.php, fix SCHEMA.md confirm_token note

</details>

### 🚧 v1.2 Fix Search Indexing (In Progress)

**Milestone Goal:** Stop Google from wasting crawl budget on parameterized filter URLs and get real content pages indexed properly — via robots.txt rules, canonical + noindex meta tags, and a clean XML sitemap.

#### Phase 3: Crawl Control
**Goal**: Googlebot and other crawlers are blocked from indexing parameterized meeting filter URLs and subscribe pages via robots.txt rules.
**Depends on**: Phase 2
**Requirements**: CRAWL-01, CRAWL-02
**Success Criteria** (what must be TRUE):
  1. `GET /robots.txt` returns rules that disallow crawling of `/meetings/` URLs with any query parameters
  2. `GET /robots.txt` returns a rule that disallows crawling of `/meetings/subscribe/` and any URL under it
  3. The robots.txt rules do not block the base `/meetings/` or individual meeting detail pages
**Plans**: 1 plan

Plans:
- [x] 03-01-PLAN.md — Add Disallow rules to robots.txt for parameterized /meetings/ and /meetings/subscribe/ URLs

#### Phase 4: Meta Tags
**Goal**: Search engines receive correct canonical and noindex signals on every meetings-related page so filtered views are consolidated under the base URL and subscribe pages are excluded from indexing entirely.
**Depends on**: Phase 3
**Requirements**: META-01, META-02, META-03, META-04
**Success Criteria** (what must be TRUE):
  1. Viewing `/meetings/?council=1` (or any filtered URL) shows a `<link rel="canonical" href="https://civi.me/meetings/">` tag in `<head>`
  2. Viewing `/meetings/?council=1` shows a `<meta name="robots" content="noindex,follow">` tag in `<head>`
  3. Viewing a meeting detail page (e.g., `/meetings/12345/`) shows a self-referencing canonical tag pointing to that exact URL
  4. Viewing any `/meetings/subscribe/` page shows a `<meta name="robots" content="noindex,nofollow">` tag in `<head>`
**Plans**: 1 plan

Plans:
- [x] 04-01-PLAN.md — Add canonical/noindex meta tags to functions.php and suppress hreflang on noindex pages in class-hreflang.php

#### Phase 5: XML Sitemap
**Goal**: An XML sitemap exists that contains only real, indexable content pages — homepage, base meetings listing, individual meeting detail pages, and static pages — with no parameterized or subscribe URLs.
**Depends on**: Phase 4
**Requirements**: SMAP-01, SMAP-02
**Success Criteria** (what must be TRUE):
  1. `GET /sitemap.xml` returns a valid XML sitemap document
  2. The sitemap includes the homepage, `/meetings/`, and individual meeting detail page URLs
  3. The sitemap contains no URLs with query parameters
  4. The sitemap contains no `/meetings/subscribe/` URLs
**Plans**: TBD

Plans:
- [ ] 05-01-PLAN.md — Generate XML sitemap via WordPress hook, include only canonical content pages

## Progress

**Execution Order:**
Phases execute in numeric order: 3 → 4 → 5

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Fix i18n System | v1.1 | 1/1 | Complete | 2026-03-17 |
| 2. Cleanup | v1.1 | 1/1 | Complete | 2026-03-17 |
| 3. Crawl Control | v1.2 | 1/1 | Complete | 2026-03-18 |
| 4. Meta Tags | v1.2 | 1/1 | Complete | 2026-03-18 |
| 5. XML Sitemap | v1.2 | 0/1 | Not started | - |
