# civi.me

## What This Is

A civic engagement platform for Hawaii that makes government information functionally accessible — surfacing council meetings, enabling reminders, and presenting information in multiple languages so all residents can participate. Two-system architecture: WordPress frontend (civi.me) + Access100 API backend (access100.app).

## Core Value

Hawaii residents can find, follow, and participate in their government's public meetings regardless of language or technical skill.

## Current Milestone: v1.1 Fix What's Broken

**Goal:** Fix confirmed live failures in the WordPress frontend that actively undermine the civic accessibility mission.

**Target features:**
- Language persistence across page navigation (i18n system)
- Language switcher preserving active filters when switching languages
- SCHEMA.md confirm_token documentation accuracy fix

## Requirements

### Validated

<!-- Shipped and confirmed valuable — v1.0 Documentation Milestone -->

- ✓ Full API endpoint documentation — v1.0 Phase 3
- ✓ WordPress frontend documentation — v1.0 Phase 4
- ✓ Infrastructure documentation — v1.0 Phase 5
- ✓ Data model documentation — v1.0 Phase 3
- ✓ Contributor-facing docs — v1.0 Phase 6
- ✓ Feature roadmap — v1.0 Phase 7
- ✓ Clean git baseline — v1.0 Phase 1
- ✓ Architecture decision records — v1.0 Phase 2

### Active

<!-- v1.1 scope — Fix What's Broken -->

- [ ] Language selection persists across page navigation for all 15 OLA languages
- [ ] Language switcher preserves meetings filter params when switching languages
- [ ] SCHEMA.md confirm_token documentation is factually correct

### Out of Scope

- Access100 API changes (scraper date fix is a separate milestone in that repo)
- New features (Tier 1/2 roadmap items)
- Dark mode (disabled in pre-milestone commit)
- Mobile layout improvements beyond what was committed pre-milestone

## Context

**WordPress Frontend (civi.me):**
- Custom theme (Lexend + Source Sans 3, CSS custom properties, light-only)
- civime-core plugin: API client (~30 methods), settings, admin dashboard (5 controllers)
- civime-meetings plugin: router, list/detail/council views
- civime-notifications plugin: subscribe/manage flows, 4 routes
- civime-guides plugin: guide content type
- civime-i18n plugin: locale detection, switcher widget, URL helper, hreflang, page content translations
- Plugin-per-feature architecture, WCAG 2.1 AA, mobile-first

**i18n System (current state):**
- `class-locale.php`: detects `?lang=` param or `civime_lang` cookie, switches locale, translates menu titles/tagline
- `class-switcher.php`: renders `<form method="get">` with language dropdown, preserves limited query params
- `class-url-helper.php`: `civime_i18n_url()` helper exists but is UNUSED anywhere — adds `?lang=` to URLs
- Three nav walkers in `functions.php`: output raw `$item->url` without `?lang=`
- All plugin templates use `home_url()` without adding `?lang=`
- Cookie set with path=/, secure, httponly, samesite=Lax — but only on `?lang=` param presence
- **Result:** Language is lost on every navigation click

**Access100 API (access100.app):**
- Separate codebase at ~/dev/Access100/
- Not in scope for this milestone

## Constraints

- **Live platform**: Changes affect real users immediately via bind-mounted wp-content
- **WordPress patterns**: Must use WP filter/hook system, not monkey-patching templates
- **Cookie + URL approach**: Language persistence via both cookie (fallback) and URL params (primary)
- **No admin impact**: `?lang=` must not appear in WP admin URLs or REST API calls

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Plugin-per-feature with civime-core | Activation independence, clean dependency direction | ✓ Good |
| Token-based subscription auth | No WP account required, stateless WordPress | ✓ Good |
| Documentation before features | Can't plan well without knowing what exists | ✓ Good |
| Disable dark mode before v1.1 | Not production-ready, simplifies CSS maintenance | ✓ Good |
| home_url filter for language URLs | Single-point fix covers all plugin templates without modifying each | — Pending |

---
*Last updated: 2026-03-16 after v1.1 milestone initialization*
