# civi.me Architecture — Two-Part Build

## How the Two Systems Work Together

```
USER EXPERIENCE                          DATA & DELIVERY
━━━━━━━━━━━━━━━━                         ━━━━━━━━━━━━━━━
    civi.me                              Access100.app API
    (WordPress)                          (PHP + MySQL)
┌──────────────────┐                   ┌──────────────────────┐
│                  │                   │                      │
│  Meetings Page   │──GET /meetings──▶│  Meetings Database   │
│  Council Browser │──GET /councils──▶│  Council Directory   │
│  Meeting Detail  │──GET /meeting/X──▶│  Agendas, Summaries │
│                  │                   │                      │
│  ┌─────────────┐ │                   │  ┌────────────────┐  │
│  │ SUBSCRIBE   │ │                   │  │ Subscriptions  │  │
│  │ Form        │─┼─POST /subscribe──▶│  │ Table          │  │
│  │             │ │                   │  │ (email, phone, │  │
│  │ Email: ___  │ │                   │  │  council_ids,  │  │
│  │ Phone: ___  │ │                   │  │  preferences)  │  │
│  │ Councils: ☑ │ │                   │  └───────┬────────┘  │
│  └─────────────┘ │                   │          │           │
│                  │                   │          ▼           │
│  Guides & How-To │                   │  ┌────────────────┐  │
│  Ambassador Kit  │                   │  │ Change         │  │
│  Event Calendar  │                   │  │ Detection      │  │
│  Issue Explorer  │                   │  │ (cron: new     │  │
│                  │                   │  │  meetings,     │  │
│  About / Mission │                   │  │  cancellations,│  │
│  Get Involved    │                   │  │  agenda posts) │  │
│                  │                   │  └───────┬────────┘  │
│                  │                   │          │           │
│                  │                   │          ▼           │
│                  │    ◀──link────────│  ┌────────────────┐  │
│  Meeting Detail  │    back to        │  │ Notification   │  │
│  (landing page   │    civi.me        │  │ Delivery       │  │
│   for notif      │                   │  │                │  │
│   clicks)        │                   │  │ Email (SMTP)   │  │
│                  │                   │  │ SMS (Twilio)   │  │
└──────────────────┘                   │  └────────────────┘  │
                                       │                      │
                                       │  ┌────────────────┐  │
                                       │  │ Scraper        │  │
                                       │  │ eHawaii.gov    │  │
                                       │  │ County sites   │  │
                                       │  └────────────────┘  │
                                       │                      │
                                       │  ┌────────────────┐  │
                                       │  │ AI Summarizer  │  │
                                       │  │ Agenda → Plain │  │
                                       │  │ Language       │  │
                                       │  └────────────────┘  │
                                       └──────────────────────┘
```

---

## Notification Subscription Flow (Step by Step)

### 1. User discovers a meeting or council on civi.me

User visits `civi.me/meetings`, browses by date or topic, finds a council they care about.

### 2. User clicks "Get Notified"

A subscribe form appears — either inline on the meetings page or a dedicated `/subscribe` page.

```
┌────────────────────────────────────────────┐
│  Get Notified                              │
│                                            │
│  How should we reach you?                  │
│  ☑ Email    ☐ Text Message                 │
│                                            │
│  Email:  [ user@example.com          ]     │
│  Phone:  [ (808) 555-1234            ]     │
│                                            │
│  What do you want to hear about?           │
│  ☑ Statewide Independent Living Council    │
│  ☑ Board of Education                      │
│  ☐ Honolulu City Council                   │
│  ☐ Maui County Council                     │
│  [ Search councils... ]                    │
│                                            │
│  How often?                                │
│  ◉ When new meetings are posted            │
│  ○ Daily digest                            │
│  ○ Weekly digest                           │
│                                            │
│  [ Subscribe ]                             │
│                                            │
│  🔒 We never share your info. Unsubscribe  │
│     anytime. See our privacy policy.       │
└────────────────────────────────────────────┘
```

### 3. civi.me sends the subscription to Access100 API

```
POST https://access100.app/api/v1/subscriptions
Content-Type: application/json
X-API-Key: civime-prod-key

{
  "email": "user@example.com",
  "phone": "+18085551234",
  "channels": ["email", "sms"],
  "council_ids": [42, 107],
  "frequency": "immediate",
  "source": "civime",
  "opted_in_at": "2026-02-27T19:30:00-10:00"
}
```

### 4. Access100 API stores the subscription

- Validates email format, phone format
- Sends confirmation email/SMS with opt-in verification link
- Stores subscription with `confirmed: false` until verified
- Returns subscription ID to civi.me

```
201 Created
{
  "subscription_id": "sub_abc123",
  "status": "pending_confirmation",
  "message": "Verification sent to user@example.com"
}
```

### 5. User confirms (double opt-in)

- Email: "Click here to confirm your subscription" → link to Access100 API
- SMS: "Reply YES to confirm meeting alerts from civi.me"
- On confirmation, subscription becomes `confirmed: true`
- Confirmation redirects back to civi.me with a success message

### 6. Access100 detects changes and sends notifications

A cron job runs every 15-30 minutes:
1. Scraper checks eHawaii calendar + county sites for new/changed meetings
2. Change detector compares against last known state
3. For each change, find all confirmed subscribers for that council
4. Queue notifications by channel (email, SMS) and frequency (immediate, daily, weekly)
5. Send via SMTP (email) or Twilio (SMS)
6. All notification links point back to `civi.me/meetings/{id}` — not Access100

### 7. Notification content

**Email example:**
```
Subject: New Meeting: Board of Education — March 15

A new meeting has been posted for a council you follow.

Board of Education
General Business Meeting
Thursday, March 15, 2026 at 1:30 PM

Location: Queen Liliuokalani Building, Room 404

View details & agenda: https://civi.me/meetings/12345

---
You're receiving this because you subscribed at civi.me.
Manage preferences: https://civi.me/notifications/manage?token=xxx
Unsubscribe: https://access100.app/api/v1/unsubscribe?token=xxx
```

**SMS example:**
```
civi.me: New meeting posted — Board of Education, Mar 15 1:30 PM.
Details: https://civi.me/m/12345
Reply STOP to unsubscribe.
```

### 8. User manages preferences on civi.me

`civi.me/notifications/manage` provides a UI to:
- Add/remove councils
- Change channels (email, SMS, both)
- Change frequency
- Unsubscribe entirely

All changes POST back to Access100 API:
```
PATCH /api/v1/subscriptions/{id}
PUT /api/v1/subscriptions/{id}/councils
DELETE /api/v1/subscriptions/{id}
```

---

## Part 1: civi.me (WordPress)

### What It Is
The public website. Everything a resident sees and interacts with. Built as a Docker WordPress instance with a custom theme and plugins.

### Pages & Sections

```
civi.me/
├── /                          # Landing page — mission, problem, CTA
├── /about                     # About the project, team, values
├── /meetings                  # Meetings calendar (from API)
│   ├── /meetings/{id}         # Meeting detail (agenda, summary, attachments)
│   └── /meetings/subscribe    # Subscribe to council notifications
├── /notifications/manage      # Manage your notification preferences
├── /get-involved              # Volunteer, ambassador program, contribute
│   ├── /ambassador-toolkit    # Toolkit for youth ambassadors
│   └── /letter-writing-kit    # Letter writing party resources
├── /guides                    # How-to guides
│   ├── /how-to-testify        # Step-by-step testimony guide
│   ├── /find-your-reps        # Who represents me? tool
│   └── /...                   # More guides as created
├── /events                    # Upcoming letter writing parties, town halls
├── /issues                    # Issue explorer (future)
└── /privacy                   # Privacy policy (important for trust)
```

### WordPress Plugins to Build

| Plugin | Purpose | Priority | API Dependency |
|--------|---------|----------|----------------|
| `civime-core` | Shared utilities, API client class, settings page, caching | Tier 1 | Yes — base API client |
| `civime-meetings` | Meeting calendar, detail pages, search/filter | Tier 1 | `GET /meetings`, `GET /councils` |
| `civime-notifications` | Subscribe UI, preference management, unsubscribe | Tier 1 | `POST/PATCH/DELETE /subscriptions` |
| `civime-guides` | Custom Post Type for how-to guides, categorized | Tier 1 | No — WordPress-native content |
| `civime-events` | Event listings for parties, town halls, ambassador meetups | Tier 2 | No — WordPress-native content |
| `civime-who-represents-me` | Address → reps lookup (client-side JS, privacy) | Tier 2 | No — uses external geocoding APIs |
| `civime-phone` | Integration with civic phone system | Tier 3 | Twilio webhook config |
| `civime-surveys` | Web-based community pulse surveys | Tier 3 | Stores locally or via API |
| `civime-bill-tracker` | Legislative bill tracking | Tier 4 (2027) | Future API endpoints |
| `civime-i18n` | Multilingual content management | Ongoing | Translation layer |

### Custom Theme Requirements

- Clean, minimal, professional — designed with UI UX Pro Max
- Mobile-first responsive
- WCAG 2.1 AA compliant (contrast, focus states, screen reader support)
- Light/dark mode (system preference + toggle)
- Hawaii-appropriate feel — not generic, not corporate
- Fast — minimal JS, optimized images, Redis-cached API calls
- Block editor (Gutenberg) compatible for content pages

### Content to Write (Launch)

| Page | Content Type | Notes |
|------|-------------|-------|
| Home | Mission statement, problem framing, CTA | The "elevator pitch" page |
| About | Who we are, values, political neutrality statement | Build trust |
| Get Involved | Ambassador signup, volunteer opportunities | Call to action |
| Ambassador Toolkit | Hosting guide, letter templates, event checklist | Downloadable resources |
| Letter Writing Kit | Templates, rep contact finder, submission guides | Neutral — facts, not positions |
| How to Testify | Step-by-step for first-timers | Plain language |
| Privacy Policy | What we collect, what we don't, opt-in policy | Critical for trust |

---

## Part 2: Access100.app API

### What It Is
The data and delivery engine. Scrapes government meeting data, stores it, generates AI summaries, manages subscriptions, and sends notifications. Serves data to civi.me via REST API.

### What Already Exists

| Component | Status | Location |
|-----------|--------|----------|
| MySQL database | Working | `u325862315_access100` on Hostinger |
| `councils` table | Working | ~300+ councils with hierarchy |
| `meetings` table | Working | Upcoming meetings with dates, locations, URLs |
| `attachments` table | Working | Meeting document files |
| `users` table | Exists | Email-only, no confirmation system |
| `subscriptions` table | Exists | Saves but never delivers |
| eHawaii scraper | Working | Populates meetings from calendar.ehawaii.gov |
| Calendar UI | Working | `meetings/index.php` — list/filter/search |
| Detail UI | Working | `meetings/detail.php` — agenda, attachments |
| ICS export | Working | `meetings/ics.php` — calendar file download |
| AI summaries | Broken | `summary_text` column exists but never populated |
| Email delivery | Broken | No SMTP/delivery infrastructure |
| SMS delivery | Missing | Not built |
| REST API | Missing | No API layer — only HTML pages |

### API Endpoints to Build

#### Meetings
```
GET    /api/v1/meetings
       ?date_from=2026-03-01
       &date_to=2026-03-31
       &council_id=42
       &q=housing
       &county=honolulu          # state | honolulu | maui | hawaii | kauai
       &limit=50
       &offset=0

GET    /api/v1/meetings/{state_id}
       → full detail: agenda, location, zoom link, attachments, AI summary

GET    /api/v1/meetings/{state_id}/summary
       → AI-generated plain-language summary only

GET    /api/v1/meetings/{state_id}/ics
       → iCalendar file for this meeting
```

#### Councils
```
GET    /api/v1/councils
       ?q=education
       &parent_id=5              # filter by parent council
       &has_upcoming=true        # only councils with future meetings

GET    /api/v1/councils/{id}
       → council detail with parent info

GET    /api/v1/councils/{id}/meetings
       → upcoming meetings for this council
```

#### Subscriptions
```
POST   /api/v1/subscriptions
       → create new subscription (triggers confirmation)
       Body: { email, phone, channels, council_ids, frequency, source }

GET    /api/v1/subscriptions/{id}
       → subscription details (auth required — token in manage link)

PATCH  /api/v1/subscriptions/{id}
       → update channels, frequency, contact info

PUT    /api/v1/subscriptions/{id}/councils
       → replace council list

DELETE /api/v1/subscriptions/{id}
       → unsubscribe completely

GET    /api/v1/subscriptions/confirm?token=xxx
       → confirm double opt-in (redirects to civi.me success page)

GET    /api/v1/subscriptions/unsubscribe?token=xxx
       → one-click unsubscribe (CAN-SPAM / TCPA compliant)
```

#### Health / Meta
```
GET    /api/v1/health
       → { status: "ok", meetings_count: 1234, last_scrape: "..." }

GET    /api/v1/stats
       → public stats: total meetings, total councils, coverage info
```

### Database Changes Needed

```sql
-- Extend users table
ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL;
ALTER TABLE users ADD COLUMN confirmed_email BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN confirmed_phone BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN confirm_token VARCHAR(64) DEFAULT NULL;
ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Extend subscriptions table
ALTER TABLE subscriptions ADD COLUMN channels SET('email','sms') DEFAULT 'email';
ALTER TABLE subscriptions ADD COLUMN frequency ENUM('immediate','daily','weekly') DEFAULT 'immediate';
ALTER TABLE subscriptions ADD COLUMN source VARCHAR(50) DEFAULT 'access100';
ALTER TABLE subscriptions ADD COLUMN active BOOLEAN DEFAULT TRUE;
ALTER TABLE subscriptions ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Notification log (track what was sent)
CREATE TABLE notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    meeting_id INT NOT NULL,
    channel ENUM('email','sms') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent','failed','bounced') DEFAULT 'sent',
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(id)
);

-- Track scraper state for change detection
CREATE TABLE scraper_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(100) NOT NULL,
    last_run TIMESTAMP,
    meetings_found INT DEFAULT 0,
    meetings_new INT DEFAULT 0,
    meetings_changed INT DEFAULT 0,
    status ENUM('success','error') DEFAULT 'success',
    error_message TEXT DEFAULT NULL
);
```

### Backend Systems to Build

#### 1. REST API Layer
- Lightweight PHP router (no heavy framework — match existing codebase style)
- JSON responses with proper HTTP status codes
- API key authentication (`X-API-Key` header)
- Rate limiting (prevent abuse)
- CORS headers for civi.me domain

#### 2. AI Summary Pipeline
```
Scraper detects new meeting with agenda
       │
       ▼
Agenda text extracted (from DB or attachment PDF)
       │
       ▼
Send to Claude API: "Summarize this government meeting agenda
in plain language. What will be discussed? What decisions might
be made? Who should care about this?"
       │
       ▼
Store summary in meetings.summary_text
       │
       ▼
Available via GET /api/v1/meetings/{id}/summary
```

#### 3. Change Detection + Notification Pipeline
```
Cron runs every 15 minutes
       │
       ▼
Scraper checks eHawaii + county sites
       │
       ▼
Compare against last known state (scraper_state table)
       │
       ▼
For new/changed meetings:
  1. Update meetings table
  2. Generate AI summary if agenda available
  3. Find all confirmed subscribers for affected councils
  4. Queue notifications by channel + frequency
       │
       ▼
Immediate: Send now (email via SMTP, SMS via Twilio)
Daily:     Batch at 7 AM HST
Weekly:    Batch on Monday 7 AM HST
       │
       ▼
Log all sends in notification_log
Links in notifications → civi.me/meetings/{id}
```

#### 4. Email Delivery (SendGrid)
- SendGrid API for transactional email
- Two sender identities:
  - `alert@civi.me` — notifications originating from civi.me subscriptions
  - `alert@access100.app` — notifications for Access100-direct subscribers
- HTML email templates (responsive, accessible)
- Unsubscribe header (RFC 8058 — List-Unsubscribe-Post)
- Bounce/complaint handling via SendGrid webhooks
- SendGrid API key stored in Access100 environment config

#### 5. SMS Delivery
- Twilio API for outbound SMS
- Dedicated civi.me phone number
- TCPA compliant: opt-in confirmation, STOP keyword handling
- Short URLs for meeting links (character limit)

---

## API Authentication & Security

### For civi.me → Access100 API
- API key in `X-API-Key` header
- Key stored in WordPress `wp-config.php` (never in client-side code)
- All API calls server-to-server (WordPress PHP → Access100 PHP)
- Users never see or interact with the Access100 API directly

### For subscription management links
- Token-based auth: each subscription gets a unique manage token
- `civi.me/notifications/manage?token=xxx` → civi.me passes token to API
- Tokens are long, random, unguessable (64 chars)
- No login required — the token IS the auth (like email unsubscribe links)

### For Access100 admin
- Separate admin auth (not exposed via public API)
- Dashboard for monitoring scraper health, notification delivery, subscriber counts

---

## Hosting

Both systems run on the **home server** (Docker). Will migrate later as needed.

```
Home Server
├── civi.me Docker stack (WordPress + MySQL + Nginx)
├── Access100 API (PHP, connects to existing MySQL)
└── Reverse proxy / DNS pointing civi.me + access100.app API
```

---

## Execution Plan

This is broken into two parallel workstreams. Both can be built independently — the WordPress site can show static content and mock data while the API is being built, and the API can be tested with curl/Postman before the WordPress plugins consume it.

### Part 1: civi.me WordPress

#### Step 1.1 — Docker Infrastructure ✅
- [x] Create `~/docker/civime-wordpress/docker-compose.yml` (WordPress latest + MariaDB 10.11, NPM reverse proxy)
- [x] Nginx Proxy Manager handles reverse proxy (already running, shared with other WP sites)
- [x] Create `~/docker/civime-wordpress/.env` (DB credentials)
- [x] `docker compose up -d` — WordPress + MariaDB running, connected to npm_default network
- [x] SSL configured via NPM — Let's Encrypt cert (expires 2026-05-29), Force SSL, HTTP/2, HSTS
- [x] Theme + plugin dirs bind-mounted from `~/dev/civi.me/wp-content/` into container
- [x] https://civi.me live and serving WordPress install wizard

#### Step 1.2 — Theme ✅
- [x] UI UX Pro Max: Accessible & Ethical style, Corporate Trust typography (Lexend + Source Sans 3)
- [x] Theme scaffold: style.css, functions.php, index.php, header.php, footer.php, front-page.php, page.php, 404.php
- [x] Design tokens via CSS custom properties: colors (ocean blue, reef teal, sunset orange), typography scale, 8px spacing grid
- [x] Responsive layout: sticky header, mobile nav drawer, footer, container system (640/768/1024/1280px breakpoints)
- [x] Light/dark mode: CSS custom properties + localStorage + prefers-color-scheme, anti-FOUC inline script
- [x] WCAG 2.1 AA: skip link, focus-visible states (3px), 44px touch targets, ARIA labels, semantic HTML, print styles
- [x] Gutenberg block overrides, prose typography, card components, button system, form styles
- [x] Nav walkers: CiviMe_Nav_Walker, CiviMe_Mobile_Nav_Walker, CiviMe_Footer_Nav_Walker

#### Step 1.3 — Content Pages (WordPress Native) ✅
- [x] Home (ID 6) — front-page.php template handles hero/problem/cards/CTA; set as static front page
- [x] About (ID 7) — vision, values, political neutrality, team, Access100 relationship, Civil Beat reference
- [x] Get Involved (ID 8) — ambassador program, volunteer, open source, partnerships, youth focus
- [x] Ambassador Toolkit (ID 12, child of Get Involved) — event checklist, hosting guide, letter templates, pizza fund
- [x] Letter Writing Kit (ID 13, child of Get Involved) — neutral templates, rep lookup, submission guides
- [x] How to Testify (ID 11) — plain-language step-by-step, written + in-person + remote, Sunshine Law context
- [x] Privacy Policy (ID 9) — plain-language, opt-in only, no tracking, double opt-in, set as WP privacy page
- [x] Events (ID 10) — placeholder with event type descriptions and CTA
- [x] Primary Menu: About, Get Involved, Events, How to Testify (assigned to `primary` location)
- [x] Footer Menu: About, Get Involved, Ambassador Toolkit, Letter Writing Kit, Privacy, GitHub
- [x] Pretty permalinks (/%postname%/), civime theme activated, all 3 plugins activated
- [x] Content source files saved in wp-content/page-content/*.html

#### Step 1.4 — `civime-core` Plugin ✅
- [x] Plugin scaffold: autoloader (spl_autoload_register), CiviMe_ prefix → includes/class-*.php
- [x] Settings page (WP Admin > Settings > CiviMe): API URL, API key (password field, preserved on blank submit), cache TTL, cache enable/disable
- [x] `CiviMe_API_Client` class — 14 public methods covering all API endpoints:
  - Meetings: get_meetings, get_meeting, get_meeting_summary, get_meeting_ics_url
  - Councils: get_councils, get_council, get_council_meetings
  - Subscriptions: create_subscription (auto-injects source=civime), get_subscription, update_subscription, update_subscription_councils, delete_subscription
  - Health: get_health (never cached), get_stats
- [x] Transient caching: civime_cache_ prefix, configurable TTL, bulk flush via $wpdb, errors never cached
- [x] Error handling: all methods return array|WP_Error, HTTP errors mapped to WP_Error codes
- [x] Settings page: live health check banner (green/red), health detail table, clear cache button (nonce-protected)
- [x] Helper functions: civime_api() singleton, civime_get_option() wrapper

#### Step 1.5 — `civime-meetings` Plugin ✅
- [x] Router: rewrite rules for `/meetings/`, `/meetings/{id}`, `/meetings/councils/` with query vars, 200 status, body classes
- [x] Meeting list view: date-grouped cards, council filter dropdown, county filter, date range filter, keyword search, pagination
- [x] Meeting detail view (`/meetings/{state_id}`): council name, date/time, location, zoom link, agenda text, AI summary, attachments, "Add to Calendar" ICS link, breadcrumb nav, 404 handling
- [x] Council browse view: searchable list of all councils with meeting counts, county filter, next meeting date
- [x] "Get Notified" button on meeting detail and council pages → links to subscribe form
- [x] CSS: responsive layout, WCAG 2.1 AA (44px touch targets, focus states), print styles, light/dark mode via theme tokens
- [x] JS: auto-submit filter selects, smooth scroll to results on filtered views

#### Step 1.6 — `civime-notifications` Plugin ✅
- [x] Router: rewrite rules (priority 9, before meetings catch-all) for `/meetings/subscribe`, `/notifications/manage`, `/notifications/confirmed`, `/notifications/unsubscribed`
- [x] Subscribe page (`/meetings/subscribe`): channel checkboxes (email/SMS), email input, phone input, council picker with search filter, frequency radios (immediate/daily/weekly), honeypot anti-spam, nonce verification, server-side validation, API submission, POST-redirect-GET on success, pre-select council via `?council_id=` param
- [x] Form submits to WP backend → validates → POSTs to Access100 API via `civime_api()->create_subscription()`
- [x] Confirmation landing page (`/notifications/confirmed`) — success message after double opt-in
- [x] Unsubscribed landing page (`/notifications/unsubscribed`) — goodbye message with re-subscribe link
- [x] Manage preferences page (`/notifications/manage?id=X&token=Y`): token-based auth, fetch subscription + all councils from API, edit channels/frequency/councils, save via `update_subscription()` + `update_subscription_councils()`, unsubscribe with confirmation dialog via `delete_subscription()`
- [x] `[civime_subscribe_cta]` shortcode: embeddable CTA card with optional council_id, title, description, button_text attributes
- [x] Updated meetings plugin "Get Notified" links to pass `council_id` for pre-selection
- [x] CSS: form styles, council picker, status pages, responsive, WCAG (44px targets, focus states), print styles
- [x] JS: channel toggle (show/hide email/phone fields), council picker search filter with live count, error notice auto-scroll

---

### Part 2: Access100.app API

#### Step 2.1 — API Router
- [ ] Create `api/` directory at Access100 app root
- [ ] `api/index.php` — lightweight router (no framework, match existing PHP style)
- [ ] `.htaccess` rewrite: `/api/v1/*` → `api/index.php`
- [ ] `api/config.php` — database connection, API keys, SendGrid key, Twilio credentials
- [ ] `api/middleware/auth.php` — validate `X-API-Key` header
- [ ] `api/middleware/cors.php` — allow requests from civi.me domain
- [ ] `api/middleware/rate-limit.php` — basic IP + key rate limiting
- [ ] JSON response helper: `json_response($data, $status_code)`

#### Step 2.2 — Meetings Endpoints
- [ ] `GET /api/v1/meetings` — list with filters (date range, council, keyword, county, pagination)
- [ ] `GET /api/v1/meetings/{state_id}` — full detail with joins (council, parent council, attachments)
- [ ] `GET /api/v1/meetings/{state_id}/summary` — AI summary only (or 404 if not yet generated)
- [ ] `GET /api/v1/meetings/{state_id}/ics` — iCalendar file download
- [ ] Response format: JSON with consistent structure (`{ data: {...}, meta: {...} }`)

#### Step 2.3 — Councils Endpoints
- [ ] `GET /api/v1/councils` — list with filters (keyword, parent, has_upcoming)
- [ ] `GET /api/v1/councils/{id}` — detail with parent info and upcoming meeting count
- [ ] `GET /api/v1/councils/{id}/meetings` — meetings for this council

#### Step 2.4 — Subscriptions Endpoints
- [ ] `POST /api/v1/subscriptions` — create subscription
  - Validate email/phone format
  - Generate 64-char confirm token and manage token
  - Store with `confirmed: false`
  - Send confirmation via SendGrid (email) and/or Twilio (SMS)
  - Return `201 { subscription_id, status: "pending_confirmation" }`
- [ ] `GET /api/v1/subscriptions/confirm?token=xxx` — confirm double opt-in
  - Set `confirmed: true`
  - Redirect to `civi.me/notifications/confirmed`
- [ ] `GET /api/v1/subscriptions/{id}?token=xxx` — get subscription details (manage token auth)
- [ ] `PATCH /api/v1/subscriptions/{id}?token=xxx` — update channels, frequency
- [ ] `PUT /api/v1/subscriptions/{id}/councils?token=xxx` — replace council list
- [ ] `DELETE /api/v1/subscriptions/{id}?token=xxx` — unsubscribe
- [ ] `GET /api/v1/subscriptions/unsubscribe?token=xxx` — one-click unsubscribe (for email links)

#### Step 2.5 — Database Migrations
- [ ] Run SQL to extend `users` table (phone, confirmed_email, confirmed_phone, confirm_token, manage_token, created_at)
- [ ] Run SQL to extend `subscriptions` table (channels, frequency, source, active, created_at)
- [ ] Create `notification_log` table
- [ ] Create `scraper_state` table
- [ ] Create `notification_queue` table (for digest batching)

```sql
CREATE TABLE notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    meeting_id INT NOT NULL,
    channel ENUM('email','sms') NOT NULL,
    scheduled_for TIMESTAMP NOT NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
    FOREIGN KEY (meeting_id) REFERENCES meetings(id)
);
```

#### Step 2.6 — SendGrid Integration
- [ ] Install SendGrid PHP library (or use HTTP API directly for no-dependency approach)
- [ ] `api/services/email.php`:
  - `send_confirmation_email($email, $confirm_url)` — via `alert@civi.me` or `alert@access100.app` based on `source`
  - `send_meeting_notification($subscription, $meeting)` — notification email with template
  - `send_digest($subscription, $meetings)` — daily/weekly digest
- [ ] HTML email template: responsive, accessible, branded per source (civi.me vs access100)
- [ ] SendGrid webhook endpoint for bounces/complaints → auto-deactivate subscriptions

#### Step 2.7 — Twilio SMS Integration
- [ ] `api/services/sms.php`:
  - `send_confirmation_sms($phone, $confirm_keyword)` — "Reply YES to confirm"
  - `send_meeting_alert($phone, $meeting)` — short notification with link
- [ ] Twilio webhook for inbound SMS: handle YES (confirm), STOP (unsubscribe)
- [ ] Dedicated civi.me Twilio number

#### Step 2.8 — AI Summary Pipeline
- [ ] `api/services/summarizer.php`:
  - Takes meeting agenda text (from DB or parsed PDF attachment)
  - Sends to Claude API with prompt for plain-language summary
  - Stores result in `meetings.summary_text`
- [ ] Trigger: called by scraper when new meetings are found with agenda content
- [ ] Fallback: if agenda is only in PDF attachment, extract text first

#### Step 2.9 — Change Detection + Notification Cron
- [ ] `api/cron/notify.php` — runs every 15 minutes
  1. Query scraper_state for last run time
  2. Find meetings created/modified since last run
  3. For each changed meeting, find all confirmed+active subscribers for that council
  4. For `immediate` subscribers: queue notification now
  5. For `daily`/`weekly` subscribers: queue for next batch window
  6. Process queue: send via SendGrid or Twilio
  7. Log all sends in `notification_log`
  8. Update `scraper_state`
- [ ] `api/cron/digest.php` — runs daily at 7 AM HST
  - Batch all pending `daily` notifications per subscriber into one email
  - Clear processed items from queue
- [ ] `api/cron/weekly-digest.php` — runs Monday 7 AM HST
  - Same for `weekly` subscribers

#### Step 2.10 — Health & Stats
- [ ] `GET /api/v1/health` — API status, DB connection, last scrape time, queue depth
- [ ] `GET /api/v1/stats` — public stats (total meetings tracked, councils covered, subscriber count)

---

## Execution Order

### Completed

```
Part 1: civi.me WordPress ✅
├── 1.1  Docker infrastructure (WP + MariaDB, NPM, SSL)
├── 1.2  Theme (design tokens, responsive, dark mode, WCAG 2.1 AA)
├── 1.3  Content pages (Home, About, Get Involved, Toolkit, Letter Kit, Testify, Privacy, Events)
├── 1.4  civime-core plugin (API client, transient caching, settings page)
├── 1.5  civime-meetings plugin (list/detail/councils views, data mapper)
└── 1.6  civime-notifications plugin (subscribe, manage, confirm, unsubscribe)

Part 2: Access100 API ✅
├── 2.1   API Router + middleware chain
├── 2.2   Meetings endpoints
├── 2.3   Councils endpoints
├── 2.4   Subscriptions endpoints
├── 2.5   Database migrations
├── 2.6   SendGrid integration
├── 2.7   Twilio SMS integration
├── 2.8   AI summary pipeline
├── 2.9   Change detection + notification cron
└── 2.10  Health & stats
```

### Up Next — Part 3: Integration Testing & Launch

```
Phase 3.1: Local Integration Testing  ← YOU ARE HERE
├── [ ] Both Docker stacks running (WP on :8080, API on :8082)
├── [ ] Configure civime-core settings to point at local API
├── [ ] Verify /meetings/ list renders with real data
├── [ ] Verify /meetings/{id} detail page (meta, summary, attachments)
├── [ ] Verify /meetings/councils/ browse page
├── [ ] Verify /meetings/subscribe form submission → API
├── [ ] Verify double opt-in confirmation flow
├── [ ] Verify /notifications/manage preference editing
├── [ ] Verify unsubscribe flow
├── [ ] Test change detection cron → notification delivery (email + SMS)
├── [ ] Test daily and weekly digest cron
├── [ ] Dark mode visual check on all pages
├── [ ] Mobile responsive check on all pages
└── [ ] See: ~/dev/civi.me/TESTING.md for detailed walkthrough

Phase 3.2: WCAG & Performance Audit
├── [ ] axe DevTools or Lighthouse accessibility audit
├── [ ] Keyboard-only navigation test (all pages)
├── [ ] Screen reader spot-check (VoiceOver / NVDA)
├── [ ] API load testing (ab or wrk against key endpoints)
├── [ ] Check rate limiter under sustained requests
└── [ ] Review cache TTLs and transient expiry

Phase 3.3: Production Deployment
├── [ ] Upload API files to Hostinger
├── [ ] Run migration on production DB
├── [ ] Set production .env (SendGrid, Twilio, Claude API keys)
├── [ ] Configure Hostinger cron jobs (4 entries)
├── [ ] Smoke test API health endpoint
├── [ ] Set civime-core settings to production API URL + key
├── [ ] DNS: point civi.me to home server
├── [ ] SSL cert verification
├── [ ] Smoke test full flow on production
└── [ ] Go live
```

---

## File Structure Summary

```
~/dev/civi.me/                                # civi.me project root
├── ARCHITECTURE.md                           # This file
├── TESTING.md                                # E2E testing guide
├── docker-compose.yml                        # WordPress + MariaDB
├── .env                                      # Credentials (gitignored)
└── wp-content/
    ├── page-content/                         # Source HTML for WP pages
    ├── themes/
    │   └── civime/                            # Custom theme
    │       ├── style.css
    │       ├── functions.php
    │       ├── index.php, header.php, footer.php
    │       ├── front-page.php, page.php, 404.php
    │       └── assets/css/, assets/js/
    └── plugins/
        ├── civime-core/
        │   ├── civime-core.php               # Bootstrap, autoloader, helpers
        │   ├── includes/
        │   │   ├── class-api-client.php       # 14 API methods, transient caching
        │   │   └── class-settings.php         # WP Admin settings page
        │   └── admin/
        │       └── settings-page.php          # Health check, cache flush UI
        ├── civime-meetings/
        │   ├── civime-meetings.php            # Bootstrap, autoloader, enqueue
        │   ├── includes/
        │   │   ├── class-router.php           # Rewrite rules (/meetings/, /meetings/{id}, /meetings/councils/)
        │   │   ├── class-list.php             # Meetings list controller
        │   │   ├── class-detail.php           # Meeting detail controller
        │   │   ├── class-councils-list.php    # Councils list controller
        │   │   ├── class-data-mapper.php      # API → template field translation
        │   │   └── shortcodes.php             # [civime_subscribe_cta]
        │   ├── templates/
        │   │   ├── meetings-list.php          # Date-grouped card list + filters
        │   │   ├── meeting-detail.php         # Full detail page
        │   │   └── councils-list.php          # Council browser grid
        │   └── assets/
        │       ├── css/meetings.css
        │       └── js/meetings.js
        └── civime-notifications/
            ├── civime-notifications.php       # Bootstrap, autoloader, enqueue
            ├── includes/
            │   ├── class-router.php           # Rewrite rules (subscribe, manage, confirmed, unsubscribed)
            │   ├── class-subscribe.php        # Subscribe form controller
            │   ├── class-manage.php           # Manage preferences controller
            │   └── shortcodes.php             # [civime_subscribe_cta]
            ├── templates/
            │   ├── subscribe.php              # Subscribe form
            │   ├── manage.php                 # Edit preferences
            │   ├── confirmed.php              # Confirmation landing
            │   └── unsubscribed.php           # Goodbye landing
            └── assets/
                ├── css/notifications.css
                └── js/notifications.js

~/dev/Access100/app website/public_html/      # Access100 API
├── meetings/                                  # Legacy meeting UI (keep)
└── api/                                       # REST API
    ├── index.php                              # Router
    ├── config.php                             # DB, keys, helpers
    ├── .htaccess                              # Security + routing
    ├── TCPA_COMPLIANCE.md                     # SMS compliance plan
    ├── middleware/                             # cors.php, auth.php, rate-limit.php
    ├── endpoints/                             # health.php, stats.php, meetings.php, councils.php, subscriptions.php, webhooks.php
    ├── services/                              # email.php (SendGrid), sms.php (Twilio), summarizer.php (Claude)
    ├── cron/                                  # notify.php, digest.php, weekly-digest.php, summarize.php
    └── migrations/                            # 001-extend-subscriptions.sql
```
