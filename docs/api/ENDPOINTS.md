# Access100 API — Endpoint Reference

Human-readable reference for all Access100 API endpoints, grouped by resource domain. For full request/response schemas and interactive exploration, see [openapi.yaml](./openapi.yaml) or the [Redoc reference](./redoc.html).

**Base URL:** `https://access100.app/api/v1`

---

## Authentication

The API uses two authentication patterns depending on the caller and the operation.

**X-API-Key (server-to-server):** WordPress sends an `X-API-Key` header on every server-to-server API call. The key is never exposed to the browser — it is set in WordPress via the CiviMe Core plugin settings (stored in `wp-config.php` or the options table). This pattern is required for all data-mutating public routes (POST/PATCH/PUT/DELETE) and for all `/admin/*` routes.

**manage_token (subscriber self-service):** A 64-character hex string generated at subscription creation time and returned in the `POST /subscriptions` 201 response. It is sent as a `?token=` query parameter. The token is scoped to a single user and covers all of that user's subscriptions. It never expires and does not rotate. WordPress never stores this token — it lives only in the email links sent to subscribers, so subscribers carry their own auth credential in every email.

**confirm_token (one-time opt-in):** A separate 64-character hex string generated at subscription or reminder creation time. It is embedded in the confirmation email link sent to the subscriber and validated by `GET /subscriptions/confirm` or `GET /reminders/confirm`. The token column is not cleared after use — `users.confirmed_email = 1` is the authoritative confirmed state.

---

## Endpoint Groups

### 1. System

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/` | Public | API root — returns a list of all available endpoints |
| GET | `/api/v1/health` | Public | Health check with DB connectivity status and operational metrics |
| GET | `/api/v1/stats` | Public | Platform-wide aggregate statistics (meetings, councils, subscribers) |

---

### 2. Meetings

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/meetings` | API Key | List meetings with optional filters (date range, council, topic, full-text search) |
| GET | `/api/v1/meetings/{state_id}` | API Key | Full meeting detail including agenda, AI summary, attachments, and topic tags |
| GET | `/api/v1/meetings/{state_id}/summary` | API Key | AI-generated HTML summary only (returns 404 if not yet generated) |
| GET | `/api/v1/meetings/{state_id}/ics` | API Key | iCalendar download for calendar app import (raw iCal, not JSON envelope) |

**Notes:**
- `state_id` matches `[a-zA-Z0-9_-]{1,50}` — a synthetic or eHawaii state identifier
- Meetings list defaults to today forward, ordered by `meeting_date ASC, meeting_time ASC`
- The ICS endpoint returns `text/calendar` — not a JSON response

---

### 3. Councils

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/councils` | API Key | List all councils with optional filters (name search, jurisdiction, entity type, topic) |
| GET | `/api/v1/councils/{id}` | API Key | Council detail with child councils |
| GET | `/api/v1/councils/{id}/meetings` | API Key | Upcoming meetings for a specific council (paginated) |
| GET | `/api/v1/councils/{id}/profile` | API Key | Extended council profile with plain-language description, contact info, and governance details |
| GET | `/api/v1/councils/{id}/authority` | API Key | Statutory references establishing the council's legal authority |
| GET | `/api/v1/councils/{id}/members` | API Key | Board/commission member roster ordered by role then display order |
| GET | `/api/v1/councils/{id}/vacancies` | API Key | Open appointment seats ordered by application deadline |
| GET | `/api/v1/councils/slug/{slug}` | API Key | Lookup a council by its URL-friendly profile slug |

**Notes:**
- `GET /councils` returns all matching councils (no pagination) ordered alphabetically
- `GET /councils/{id}/meetings` returns only upcoming meetings (`meeting_date >= today`)
- `GET /councils/{id}/profile` returns 404 if no profile record exists for this council

---

### 4. Topics

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/topics` | API Key | List all 16 civic topic categories with council and meeting counts |
| GET | `/api/v1/topics/{slug}` | API Key | Topic detail with all mapped councils and their upcoming meeting counts |
| GET | `/api/v1/topics/{slug}/meetings` | API Key | Upcoming meetings matching this topic (via council mapping or AI classification) |

**Available topic slugs:** `environment`, `housing`, `education`, `health`, `transportation`, `public-safety`, `economy`, `culture`, `agriculture`, `energy`, `water`, `disability`, `veterans`, `technology`, `budget`, `governance`

---

### 5. Subscriptions

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/v1/subscriptions` | API Key | Create a subscription — triggers confirmation email/SMS; subscription is inactive until confirmed |
| GET | `/api/v1/subscriptions/confirm` | Public | Confirm opt-in via email link (`?token={confirm_token}`) — redirects 302 to civi.me/notifications/confirmed |
| GET | `/api/v1/subscriptions/unsubscribe` | Public | One-click unsubscribe via email footer link (`?token={manage_token}`) — deactivates all subscriptions; redirects 302 |
| GET | `/api/v1/subscriptions/{id}` | Token | Retrieve current subscription preferences and active council list |
| PATCH | `/api/v1/subscriptions/{id}` | Token | Update user-level preferences (email, phone, channels, frequency) |
| PUT | `/api/v1/subscriptions/{id}/councils` | Token | Replace the full council subscription list (deactivates any councils not in the new list) |
| DELETE | `/api/v1/subscriptions/{id}` | Token | Soft-delete all subscriptions for the user (same effect as one-click unsubscribe) |

**Notes:**
- The `{id}` path param is the numeric `user_id` returned in the POST 201 response
- `?token={manage_token}` is required on all Token-auth routes
- `POST /subscriptions` always returns status `"pending_confirmation"` — subscriptions are inactive until email link is clicked
- `manage_token` is returned in the 201 response body — WordPress passes it to the subscriber via the confirmation email; it is never stored in WordPress

---

### 6. Reminders

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/v1/reminders` | API Key | Create a one-time meeting-day reminder — triggers a confirmation email with a separate `confirm_token` |
| GET | `/api/v1/reminders/confirm` | Public | Confirm a reminder opt-in (`?token={confirm_token}`) — sets `reminders.confirmed = 1`; redirects 302 |

**Notes:**
- Reminders use their own `confirm_token` stored in `reminders.confirm_token`, separate from `users.confirm_token`
- A confirmed reminder sends one email on the morning of the meeting date
- These routes are not included in the public OpenAPI spec — they are documented in ENDPOINTS.md only

---

### 7. Webhooks

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/api/v1/webhooks/sendgrid` | Public | Receives SendGrid bounce/spam/drop events; auto-deactivates affected email subscriptions |
| POST | `/api/v1/webhooks/twilio` | Public | Receives inbound SMS (YES/STOP/HELP); responds with TwiML; handles TCPA-compliant opt-out |

**Supported Twilio keywords:**

| Keyword | Action |
|---------|--------|
| `YES`, `CONFIRM`, `Y` | Confirms phone subscription |
| `STOP`, `CANCEL`, `UNSUBSCRIBE`, `QUIT`, `END` | Unsubscribes (TCPA compliant) |
| `HELP`, `INFO` | Returns help message |

---

### 8. Admin

> **Internal use only.** Admin endpoints require `X-API-Key` and are not included in the OpenAPI specification. They are called exclusively by the WordPress admin dashboard via the `CiviMe_API_Client` class in `civime-core`.

#### Subscribers

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/admin/subscribers` | API Key | Paginated subscriber list with filters (search, status, confirmed) |
| POST | `/api/v1/admin/subscribers` | API Key | Create a subscriber directly, bypassing the confirmation flow |
| PATCH | `/api/v1/admin/subscribers/{id}` | API Key | Update a subscriber's email, channels, frequency, or council list |
| DELETE | `/api/v1/admin/subscribers/{id}` | API Key | Deactivate all subscriptions for a user; pass `?hard=true` for permanent delete |

#### Reminders

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/admin/reminders` | API Key | Paginated reminder list with filters (search, confirmed, sent) |
| DELETE | `/api/v1/admin/reminders/{id}` | API Key | Permanently delete a reminder record |

#### Meetings

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/admin/meetings` | API Key | Paginated meetings list with admin filters (date range, council, status) |
| GET | `/api/v1/admin/meetings/check-links` | API Key | Batch-check all meeting `detail_url` links for broken URLs |
| PATCH | `/api/v1/admin/meetings/{id}` | API Key | Update a meeting's fields (state_id, detail_url, status, title) |

#### Scraper

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/admin/scraper/runs` | API Key | Recent scraper run history with statistics |
| POST | `/api/v1/admin/scraper/trigger` | API Key | Manually trigger an eHawaii scraper run |
| POST | `/api/v1/admin/scraper/trigger-nco` | API Key | Manually trigger an NCO neighborhood board scraper run |
| POST | `/api/v1/admin/scraper/trigger-honolulu-boards` | API Key | Manually trigger a Honolulu boards and commissions scraper run |
| POST | `/api/v1/admin/scraper/trigger-maui` | API Key | Manually trigger a Maui Legistar scraper run |

#### Councils

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/v1/admin/councils` | API Key | Paginated council list with admin filters (search, active status, jurisdiction) |
| GET | `/api/v1/admin/councils/{id}` | API Key | Full council detail including profile, members, vacancies, and legal authority |
| PATCH | `/api/v1/admin/councils/{id}` | API Key | Update council base fields and profile |
| POST | `/api/v1/admin/councils/{id}/members` | API Key | Add a member to a council |
| DELETE | `/api/v1/admin/councils/{id}/members/{member_id}` | API Key | Remove a member from a council |
| POST | `/api/v1/admin/councils/{id}/vacancies` | API Key | Add a vacancy record to a council |
| DELETE | `/api/v1/admin/councils/{id}/vacancies/{vacancy_id}` | API Key | Remove a vacancy record from a council |
| POST | `/api/v1/admin/councils/{id}/authority` | API Key | Add a legal authority citation to a council |
| DELETE | `/api/v1/admin/councils/{id}/authority/{auth_id}` | API Key | Remove a legal authority citation from a council |

---

## Route Count Summary

| Group | Public Routes | Admin Routes |
|-------|--------------|--------------|
| System | 3 | — |
| Meetings | 4 | 3 |
| Councils | 8 | 9 |
| Topics | 3 | — |
| Subscriptions | 7 | — |
| Reminders | 2 | 2 |
| Webhooks | 2 | — |
| Subscribers | — | 4 |
| Scraper | — | 5 |
| **Total** | **29** | **23** |

**Grand total: 52 routes**
