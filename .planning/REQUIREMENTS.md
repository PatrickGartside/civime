# Requirements: civi.me v1.2 — Fix Search Indexing

**Defined:** 2026-03-17
**Core Value:** Hawaii residents can find, follow, and participate in their government's public meetings regardless of language or technical skill.

## v1.2 Requirements

Requirements for Fix Search Indexing milestone. Each maps to roadmap phases.

### Crawl Control

- [x] **CRAWL-01**: robots.txt blocks crawling of parameterized /meetings/ URLs (any URL with query params)
- [x] **CRAWL-02**: robots.txt blocks crawling of /meetings/subscribe/ URLs

### Meta Tags

- [x] **META-01**: Filtered /meetings/ pages (with query params) have canonical tag pointing to base /meetings/ URL
- [x] **META-02**: Meeting detail pages (/meetings/<id>/) have self-referencing canonical tag
- [x] **META-03**: Filtered /meetings/ pages (with query params) have noindex,follow meta tag
- [x] **META-04**: /meetings/subscribe/ pages have noindex,nofollow meta tag

### Sitemap

- [x] **SMAP-01**: XML sitemap includes only real content pages (homepage, base /meetings/, individual meeting detail pages, static pages)
- [x] **SMAP-02**: XML sitemap excludes all parameterized URLs and /meetings/subscribe/

## v1.1 Requirements (Complete)

### i18n Language Switching

- [x] **I18N-01**: User can select a language from the dropdown and the page content changes to that language
- [x] **I18N-02**: Language switcher auto-submits without being blocked by Content Security Policy
- [x] **I18N-03**: Nav menu links carry `?lang=` parameter for non-English locales
- [x] **I18N-04**: Plugin-generated URLs carry `?lang=` parameter
- [x] **I18N-05**: Language choice persists via cookie
- [x] **I18N-06**: WP admin URLs and REST API calls are not affected by language URL filtering
- [x] **I18N-07**: Language switcher preserves active meetings filter params when switching languages

### Cleanup

- [x] **CLN-01**: Dark mode flash prevention inline script removed from wp_head
- [x] **CLN-02**: SCHEMA.md confirm_token note corrected

## Future Requirements

### i18n Enhancements

- **I18N-F1**: Language selection persists via URL path prefix (/ja/meetings/) instead of query param
- **I18N-F2**: hreflang tags reflect all available translations for SEO
- **I18N-F3**: Translation completeness indicator showing which content is available in each language

## Out of Scope

| Feature | Reason |
|---------|--------|
| Path-based i18n URLs (/es/meetings/) | Lang is a UI toggle, not translated content — canonicalize to base URL |
| hreflang annotations | Not needed since lang doesn't produce distinct content |
| Google Search Console re-crawl | Manual post-deploy step, not code work |
| Access100 API changes | Separate repo, separate milestone |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| CRAWL-01 | Phase 3 | Complete |
| CRAWL-02 | Phase 3 | Complete |
| META-01 | Phase 4 | Complete |
| META-02 | Phase 4 | Complete |
| META-03 | Phase 4 | Complete |
| META-04 | Phase 4 | Complete |
| SMAP-01 | Phase 5 | Complete |
| SMAP-02 | Phase 5 | Complete |

**Coverage:**
- v1.2 requirements: 8 total
- Mapped to phases: 8
- Unmapped: 0 ✓

---
*Requirements defined: 2026-03-17*
*Last updated: 2026-03-17 — traceability filled after roadmap creation*
