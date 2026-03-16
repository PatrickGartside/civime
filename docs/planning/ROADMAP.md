# Feature Roadmap

civi.me makes Hawaii government functionally accessible — surfacing council meetings, enabling reminders, and presenting information in multiple languages so all residents can participate. Items in this roadmap are prioritized against that core value: work that most directly restores or expands the platform's ability to serve Hawaii residents comes first.

This roadmap uses three tiers. Tier 0 addresses things that are currently broken and actively undermine the mission. Tier 1 covers the most valuable next features. Tier 2 lists longer-horizon work that is identified but not yet scheduled.

---

## Tier 0: Fix What's Broken

These items represent confirmed live failures. They ship before any new feature work.

---

### Phase: Fix Translation Dropdown Persistence

**System:** WordPress — `civime-i18n` plugin

**Goal:** Language selection persists across page navigation so the 15 OLA languages are actually usable.

**Priority:** Highest — 15 languages are configured but effectively inaccessible. Language continuity across pages is the minimum for the feature to function. This directly undermines the civic accessibility mission.

**Success Criteria:**

- A user who selects Japanese on the home page and navigates to the Meetings page still sees Japanese on the Meetings page
- A user who selects a language and closes their browser returns to find their language preference preserved
- The language switcher dropdown appears on all pages including custom plugin routes (`/meetings/`, `/subscribe/`, `/manage/`)

**Key Findings (from source code):**

- Navigation links registered in the WordPress nav menu use standard `home_url()` calls and do not include `?lang=` parameters
- The `CiviMe_Nav_Walker` does not override href generation — links lose language state on every page transition
- Cookie persistence is the fallback mechanism but may have HTTPS/SameSite issues in production
- Fix approaches: (a) nav walker override that appends `?lang=` to hrefs for non-English locales, (b) JavaScript that adds `?lang=` to all links after page load, or (c) strengthen cookie-only persistence

---

### Phase: Fix Meeting Date Scraper

**System:** Access100 API — `cron/scrape.php`, `cron/scrape-maui-legistar.php`

**Goal:** Scraped meeting dates match the actual council meeting calendars.

**Priority:** High — wrong meeting dates cause users to miss government meetings. Accurate dates are the core function of the platform.

**Success Criteria:**

- Meeting dates displayed on civi.me match the dates shown on the source government calendar pages
- The scraper logs a warning when it falls back to `pubDate` instead of extracting the date from the RSS description
- Timezone handling is explicit (HST) for both eHawaii and Legistar scrapers

**Key Findings (from source code):**

- The eHawaii scraper uses `preg_match('/Date:\s*(\d{4}\/\d{2}\/\d{2})/i', ...)` to extract meeting dates from RSS description text
- When this regex fails (format variation or missing date field), the scraper falls back to `pubDate` — the RSS publication timestamp, not the meeting date — potentially days or weeks different
- The Legistar scraper parses `"2026-03-04T00:00:00"` with no timezone specifier; if the server runs UTC, timezone ambiguity could shift dates
- Fix requires: validate date regex against actual format variations, add explicit timezone handling, add fallback logging

---

### Phase: Fix SCHEMA.md confirm_token Note

**System:** Documentation — `docs/data-model/SCHEMA.md`

**Goal:** The `confirm_token` column documentation is factually correct.

**Priority:** Medium within Tier 0 — small fix with high accuracy value. Prevents contributor confusion when implementing subscription flows.

**Success Criteria:**

- `SCHEMA.md` `confirm_token` note no longer states "cleared after use"
- Note reflects that `confirmed_email=1` is the authoritative confirmation state and the token is retained after use

---

## Tier 1: Next Features

These items deliver meaningful new value and are ready to be planned and executed.

---

### Phase: Cron Monitoring and Alerting

**System:** Access100 API — cron system

**Goal:** Silent cron failures are detected and surfaced before users notice broken functionality.

**Priority:** High — the scraper, notification, and digest cron jobs are the delivery mechanism for the platform's core value. When they fail silently, the platform stops working without any signal. A cron failure is currently indistinguishable from a working system until a user notices stale data or a missed notification.

**Success Criteria:**

- An admin is notified (email or dashboard alert) when a cron job fails repeatedly
- The scraper, notification, and digest cron jobs each have health check visibility
- Cron failure history is accessible in the admin dashboard

---

### Phase: Automated Tests for Access100 API

**System:** Access100 API

**Goal:** Critical business logic has test coverage enabling confident iteration.

**Priority:** High — zero test coverage means every change requires manual verification and regressions are caught only in production. The subscription flow and scraper date parsing are the highest-risk areas.

**Success Criteria:**

- Subscription flow (subscribe, confirm, manage, unsubscribe) has automated tests
- Scraper date parsing logic has automated tests covering known format variations
- Token auth (`confirm_token`, `manage_token`) has automated tests
- Tests can be run with a single command

---

### Phase: Meeting Search and Filtering Improvement

**System:** WordPress (`civime-meetings` plugin) + Access100 API

**Goal:** Users can find specific meetings efficiently through keyword search, date range, and council filtering.

**Priority:** Medium-high — improved meeting discovery directly serves the civic accessibility mission. Users who cannot find relevant meetings cannot participate.

**Success Criteria:**

- Users can search meetings by keyword
- Users can filter meetings by date range
- Users can filter meetings by council
- Search results are relevant and fast

---

### Phase: Email Notification and Reminder UX

**System:** WordPress (`civime-notifications` plugin) + Access100 API

**Goal:** The subscription and reminder user experience is polished enough for civic engagement at scale.

**Priority:** Medium — engaged subscribers are the platform's long-term value mechanism. The cron system and reminder infrastructure exist; the user-facing experience needs iteration.

**Success Criteria:**

- Reminder signup flow is intuitive with clear feedback at each step
- Users can manage their notification preferences (frequency, channels)
- Digest emails are well-formatted and provide enough context for action

---

## Tier 2: Longer Horizon

These items are identified and prioritized below Tier 1. They are not yet scheduled for planning.

- **Topics Feature Activation** — Wire up the existing `topics` table and classification cron to user-facing topic-based subscriptions. The table and cron job exist; this needs API endpoints and WordPress UI.

- **CI/CD Pipeline** — GitHub Actions for linting, testing (once tests exist), and deployment automation. Currently fully manual.

- **C4 Container Diagram** — Add architectural depth for contributors. The Context diagram exists but the Container diagram was scoped out of Phase 2.

- **v2 Documentation** — Error response catalog (DOC-01), changelog (DOC-02), automated API spec validation in CI (DOC-03).

- **civime-events Decision** — Decide whether to build out the stub events plugin or deprecate it. Currently registers a CPT with no content strategy or API connection.

- **OpenAPI 3.1 Cleanup** — Migrate 59 `nullable: true` occurrences from OAS 3.0 pattern to proper 3.1 `type: ['string', 'null']` syntax. Spec validates today but uses the older pattern.
