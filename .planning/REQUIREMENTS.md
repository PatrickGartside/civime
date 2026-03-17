# Requirements: civi.me v1.1 — Fix What's Broken

**Defined:** 2026-03-16
**Core Value:** Hawaii residents can find, follow, and participate in their government's public meetings regardless of language or technical skill.

## v1.1 Requirements

Requirements for this bug-fix milestone. Each maps to roadmap phases.

### i18n Language Switching

- [x] **I18N-01**: User can select a language from the dropdown and the page content changes to that language
- [x] **I18N-02**: Language switcher auto-submits without being blocked by Content Security Policy
- [x] **I18N-03**: Nav menu links carry `?lang=` parameter for non-English locales so language persists across navigation
- [x] **I18N-04**: Plugin-generated URLs (meetings filters, pagination, notification links) carry `?lang=` parameter
- [x] **I18N-05**: Language choice persists via cookie so returning visitors see their preferred language
- [x] **I18N-06**: WP admin URLs and REST API calls are not affected by language URL filtering
- [x] **I18N-07**: Language switcher preserves active meetings filter params (q, council_id, date_from, date_to, county, topics, source) when switching languages

### Cleanup

- [x] **CLN-01**: Dark mode flash prevention inline script removed from wp_head (disabled feature, dead code)
- [x] **CLN-02**: SCHEMA.md confirm_token note corrected to reflect that token is retained after confirmation (not cleared)

## v2 Requirements

Deferred to future milestones. Tracked but not in current roadmap.

### i18n Enhancements

- **I18N-F1**: Language selection persists via URL path prefix (/ja/meetings/) instead of query param
- **I18N-F2**: hreflang tags reflect all available translations for SEO
- **I18N-F3**: Translation completeness indicator showing which content is available in each language

## Out of Scope

| Feature | Reason |
|---------|--------|
| Access100 API scraper date fix | Separate repo, separate milestone |
| New feature development (Tier 1/2) | Fix What's Broken first |
| Dark mode re-implementation | Disabled intentionally, not a bug |
| Mobile layout beyond committed fixes | Already fixed in pre-milestone commit |
| URL path-based language routing (/ja/) | Larger architectural change, defer to v2 |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| I18N-01 | Phase 1 | Complete |
| I18N-02 | Phase 1 | Complete |
| I18N-03 | Phase 1 | Complete |
| I18N-04 | Phase 1 | Complete |
| I18N-05 | Phase 1 | Complete |
| I18N-06 | Phase 1 | Complete |
| I18N-07 | Phase 1 | Complete |
| CLN-01 | Phase 2 | Complete |
| CLN-02 | Phase 2 | Complete |

**Coverage:**
- v1.1 requirements: 9 total
- Mapped to phases: 9
- Unmapped: 0

---
*Requirements defined: 2026-03-16*
*Last updated: 2026-03-16 after initial definition*
