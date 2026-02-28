# Changelog

All notable changes to the civi.me WordPress project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.0] - 2026-02-28

### Added

#### Theme
- Custom theme with Lexend + Source Sans 3 typography
- CSS custom property design system (50+ tokens for colors, spacing, shadows)
- Light/dark mode with system preference detection and manual toggle
- Mobile-first responsive layout with hamburger navigation
- Skip-to-content link with focus management
- Anti-FOUC script for dark mode
- WCAG 2.1 AA compliant color contrast, focus states, and touch targets
- Print stylesheet

#### civime-core Plugin
- API client with 14 methods covering meetings, councils, topics, and subscriptions
- WordPress Settings page (API URL, API key, cache TTL)
- Transient-based caching with configurable TTL (errors never cached)
- Health check with real-time status banner
- Cache management UI for admins
- Page sync from HTML source files via WP-CLI
- `civime_api()` singleton helper

#### civime-meetings Plugin
- Meetings list with filters (keyword, council, county, date range)
- Meeting detail page with agenda, AI summary, attachments, and calendar export
- Council browser with search and county filter
- ICS calendar file proxy (server-side, includes API key)
- Date-grouped meeting cards
- "Get Notified" call-to-action integration

#### civime-notifications Plugin
- Subscribe form with email and SMS channels
- Phone number normalization to E.164 format
- Honeypot anti-spam (no CAPTCHA)
- POST-redirect-GET for all form submissions
- Double opt-in confirmation landing page
- Manage preferences page (token-based, no login required)
- Unsubscribe confirmation page
- `[civime_subscribe_cta]` shortcode with customizable attributes
- Council picker with search filter and pre-selection via URL parameter

#### civime-topics Plugin
- "What Matters to Me" topic picker with 16 policy topics
- Topic-to-council mapping for filtered meeting browsing
- Reduces cognitive load vs. browsing 300+ councils directly

#### civime-events Plugin
- `civime_event` custom post type for community events
- `event_type` taxonomy (letter writing, ambassadors, info sessions, town halls)
- Archive and single event templates

#### civime-guides Plugin
- `civime_guide` custom post type for civic engagement guides
- `guide_category` taxonomy
- Content: How to Testify, Attending a Meeting, Voting in Hawaii, Getting Started

#### Content Pages
- Home (hero, problem statement, topic picker, CTA)
- About (mission, team, values)
- Get Involved (volunteer, partnerships)
- Ambassador Toolkit (event hosting, templates)
- Privacy Policy
- Contact

#### Documentation
- ARCHITECTURE.md — Full system design with diagrams
- PROJECT.md — Vision, scope, roadmap
- TESTING.md — End-to-end test flows and troubleshooting
- CONTRIBUTING.md — Developer setup and coding standards
