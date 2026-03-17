# Roadmap: civi.me v1.1 — Fix What's Broken

## Overview

This milestone fixes confirmed live failures in the WordPress frontend that actively undermine the civic accessibility mission. The language switcher is blocked by CSP, URLs don't carry language state across navigation, and dead code from the disabled dark mode remains. These ship before any new feature work.

## Phases

- [ ] **Phase 1: Fix i18n System** - Fix the language switcher CSP block and add URL-based language persistence across all navigation and plugin links
- [ ] **Phase 2: Cleanup** - Remove dead dark mode code and fix SCHEMA.md documentation error

## Phase Details

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
- [ ] 01-01-PLAN.md — Fix CSP-blocked switcher (move onchange to JS file), add home_url filter + nav menu URL filter for language persistence, expand switcher allowed params

### Phase 2: Cleanup
**Goal**: Dead code from the disabled dark mode feature is removed and the SCHEMA.md confirm_token documentation is corrected.
**Depends on**: Phase 1
**Requirements**: CLN-01, CLN-02
**Success Criteria** (what must be TRUE):
  1. The `civime_inline_theme_script()` function and its `wp_head` hook are removed from `functions.php` — no inline script is injected into `<head>` for dark mode flash prevention
  2. SCHEMA.md `confirm_token` note no longer states "cleared after use" — reflects that `confirmed_email=1` is the authoritative confirmation state and the token is retained
**Plans**: 1 plan

Plans:
- [ ] 02-01-PLAN.md — Remove dead dark mode inline script from functions.php, fix SCHEMA.md confirm_token note

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Fix i18n System | 0/1 | Not Started | |
| 2. Cleanup | 0/1 | Not Started | |
