# End-to-End Testing Guide

How to verify the full civi.me + Access100 API integration locally before deploying to production.

---

## Prerequisites

### 1. Start Both Docker Stacks

**Access100 API** (port 8082):
```bash
cd ~/dev/Access100/app\ website
cp .env.example .env          # Fill in DB creds, SendGrid key, Twilio SID/token, Claude key
docker compose up -d
```

**civi.me WordPress** (port 8080):
```bash
cd ~/docker/civime-wordpress
docker compose up -d
```

### 2. Configure civi.me to Talk to Local API

In WP Admin > **Settings > CiviMe**:
- **API URL**: `http://app:8082/api/v1` (Docker network) or `http://host.docker.internal:8082/api/v1`
- **API Key**: must match `API_KEY` in the Access100 `.env`
- **Cache**: disable during testing (or set TTL to 0)

Hit "Test Connection" — the health check banner should turn green.

### 3. Flush Rewrite Rules

```bash
# From inside the WP container, or via WP-CLI:
wp rewrite flush
```

Or: deactivate + reactivate both `civime-meetings` and `civime-notifications` plugins in WP Admin.

### 4. Seed Test Data

The API database needs meetings and councils. If running against a copy of production data, you're set. Otherwise, insert some test rows:

```sql
-- Minimal test council
INSERT INTO councils (id, name, parent_id, is_active)
VALUES (1, 'Test Council - Board of Education', NULL, 1);

-- Minimal test meeting (future date)
INSERT INTO meetings (state_id, title, meeting_date, meeting_time, location, description, council_id, status)
VALUES ('TEST-001', 'General Business Meeting', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '13:30:00',
        'Queen Liliuokalani Building, Room 404', 'Discussion of budget allocations and policy updates.',
        1, 'active');
```

---

## Test Flows

### Flow 1: Meetings List Page

**URL**: `http://localhost:8080/meetings/`

**Verify**:
- [ ] Page loads without PHP errors or blank content
- [ ] Meetings appear as date-grouped cards
- [ ] Each card shows: council name, date, time, location
- [ ] Filter bar renders: keyword search, council dropdown, county pills, date range inputs
- [ ] Selecting a council from the dropdown filters the list
- [ ] Selecting a county filters the list
- [ ] Entering a keyword and submitting filters the list
- [ ] Pagination appears when results exceed 20
- [ ] "View Details" links point to `/meetings/{state_id}`
- [ ] "Get Notified" button links to `/meetings/subscribe`
- [ ] Empty state message shows when filters return no results

**API calls to watch** (browser DevTools network tab or WP debug log):
- `GET /api/v1/meetings?limit=20&offset=0` (+ any filter params)
- `GET /api/v1/councils` (for dropdown)

### Flow 2: Meeting Detail Page

**URL**: `http://localhost:8080/meetings/TEST-001`

**Verify**:
- [ ] Breadcrumb shows: Meetings > Council Name
- [ ] Meta card shows: date, time, location, council name
- [ ] Zoom link appears if the meeting has one (or is absent cleanly)
- [ ] AI Summary section appears if `summary_text` is populated
- [ ] AI Summary section is absent (not empty box) if no summary
- [ ] Agenda text displays
- [ ] Attachments list appears if the meeting has attachments
- [ ] "Add to Calendar" ICS download link works
- [ ] "Official Notice" link opens `detail_url` in new tab
- [ ] "Get Notified" CTA appears at the bottom
- [ ] 404 page renders for a non-existent state_id (e.g., `/meetings/FAKE-999`)
- [ ] Page title updates to "Council Name — March 15, 2026"

**API calls**:
- `GET /api/v1/meetings/TEST-001`
- ICS URL constructed from `GET /api/v1/meetings/TEST-001/ics`

### Flow 3: Councils Browser

**URL**: `http://localhost:8080/meetings/councils/`

**Verify**:
- [ ] Council cards render with: name, meeting count
- [ ] Search field filters councils by name (client-side or server-side depending on implementation)
- [ ] County filter pills work
- [ ] "View Meetings" link on each card goes to `/meetings/?council_id=X`
- [ ] Empty state shows when no councils match filters

**API calls**:
- `GET /api/v1/councils` (+ any filter params)

### Flow 4: Subscribe to Notifications

**URL**: `http://localhost:8080/meetings/subscribe`

**Verify**:
- [ ] Form renders: channel checkboxes (email, SMS), email input, phone input, council picker, frequency radios
- [ ] Checking "Email" shows the email field; unchecking hides it
- [ ] Checking "Text Message" shows the phone field; unchecking hides it
- [ ] Council picker renders all councils with search filter
- [ ] Council search filters the list as you type, count updates
- [ ] Pre-selecting a council via `?council_id=1` pre-checks that council
- [ ] Submit with valid data → redirects to success message (or confirmed page)
- [ ] Submit with invalid email → shows validation error
- [ ] Submit with no councils selected → shows validation error
- [ ] Submit with neither channel selected → shows validation error
- [ ] Honeypot field (if visible somehow) rejects submission when filled

**API calls**:
- `GET /api/v1/councils` (to populate council picker)
- `POST /api/v1/subscriptions` (on form submit)

**Check in the database after submission**:
```sql
SELECT * FROM users ORDER BY id DESC LIMIT 1;
SELECT * FROM subscriptions ORDER BY id DESC LIMIT 1;
SELECT * FROM subscription_councils ORDER BY subscription_id DESC LIMIT 5;
```

### Flow 5: Double Opt-In Confirmation

**Trigger**: After Flow 4, the API sends a confirmation email/SMS.

**Email confirmation**:
- [ ] Check the SendGrid activity log (or local mailcatcher) for the confirmation email
- [ ] Email contains a link like `https://access100.app/api/v1/subscriptions/confirm?token=...`
- [ ] For local testing, change the domain to `http://localhost:8082/api/v1/subscriptions/confirm?token=...`
- [ ] Clicking the link sets `confirmed_email = 1` in the users table
- [ ] Redirects to `http://localhost:8080/notifications/confirmed`
- [ ] The confirmed landing page renders with a success message

**SMS confirmation** (if Twilio is configured):
- [ ] SMS received with "Reply YES to confirm"
- [ ] Replying YES hits the Twilio webhook → sets `confirmed_phone = 1`

**Check in the database**:
```sql
SELECT confirmed_email, confirmed_phone, confirm_token FROM users WHERE id = <user_id>;
```

### Flow 6: Manage Preferences

**URL**: `http://localhost:8080/notifications/manage?id=<sub_id>&token=<manage_token>`

Get the values from the database:
```sql
SELECT s.id, u.manage_token
FROM subscriptions s
JOIN users u ON s.user_id = u.id
ORDER BY s.id DESC LIMIT 1;
```

**Verify**:
- [ ] Page loads and shows current subscription settings
- [ ] Channels can be toggled (email on/off, SMS on/off)
- [ ] Frequency can be changed (immediate, daily, weekly)
- [ ] Councils can be added/removed
- [ ] Saving changes → success message, data persists on reload
- [ ] "Unsubscribe" button shows confirmation dialog
- [ ] Confirming unsubscribe → redirects to `/notifications/unsubscribed`
- [ ] Invalid/missing token → error message (not a crash)

**API calls**:
- `GET /api/v1/subscriptions/<id>?token=<manage_token>`
- `GET /api/v1/councils`
- `PATCH /api/v1/subscriptions/<id>?token=<manage_token>` (on save)
- `PUT /api/v1/subscriptions/<id>/councils?token=<manage_token>` (on save)
- `DELETE /api/v1/subscriptions/<id>?token=<manage_token>` (on unsubscribe)

### Flow 7: Unsubscribe (One-Click)

**Trigger**: The unsubscribe link in notification emails.

**URL**: `http://localhost:8082/api/v1/subscriptions/unsubscribe?token=<confirm_token>`

**Verify**:
- [ ] Hitting the URL deactivates the subscription (`active = 0`)
- [ ] Redirects to `http://localhost:8080/notifications/unsubscribed`
- [ ] The goodbye page renders with a re-subscribe link
- [ ] Hitting the same URL again doesn't crash (idempotent)

### Flow 8: Change Detection → Notification Delivery

This tests the full notification pipeline end-to-end.

**Setup**: You need a confirmed subscriber (Flows 4 + 5 complete).

**Step 1 — Simulate a meeting change**:
```sql
-- Insert a new meeting for the subscribed council
INSERT INTO meetings (state_id, title, meeting_date, meeting_time, location, description, council_id, status, created_at)
VALUES ('TEST-002', 'Special Session', DATE_ADD(CURDATE(), INTERVAL 14 DAY), '10:00:00',
        'State Capitol, Room 325', 'Emergency discussion of proposed legislation.',
        1, 'active', NOW());
```

**Step 2 — Run the change detection cron**:
```bash
# Inside the API Docker container:
docker exec -it access100-app php /var/www/html/api/cron/notify.php

# Or dry-run first to see what would happen:
docker exec -it access100-app php /var/www/html/api/cron/notify.php --dry-run
```

**Verify**:
- [ ] Dry-run output shows the new meeting detected and the subscriber matched
- [ ] Real run queues/sends a notification
- [ ] Notification log entry created:
  ```sql
  SELECT * FROM notification_log ORDER BY id DESC LIMIT 5;
  ```
- [ ] Email received (check SendGrid activity or mailcatcher)
- [ ] Email contains correct meeting title, date, time, location
- [ ] Email "View details" link points to `civi.me/meetings/TEST-002`
- [ ] Email unsubscribe link works
- [ ] If SMS channel active: SMS received with meeting info

### Flow 9: Daily Digest

**Setup**: A subscriber with `frequency = 'daily'` and pending queued notifications.

```sql
-- Set a test subscriber to daily frequency
UPDATE subscriptions SET frequency = 'daily' WHERE id = <sub_id>;

-- Insert another new meeting to trigger a queued notification
INSERT INTO meetings (state_id, title, meeting_date, meeting_time, location, council_id, status, created_at)
VALUES ('TEST-003', 'Budget Review', DATE_ADD(CURDATE(), INTERVAL 21 DAY), '14:00:00',
        'City Hall, Conference Room B', 1, 'active', NOW());
```

**Run change detection** (queues instead of sending for daily subscribers):
```bash
docker exec -it access100-app php /var/www/html/api/cron/notify.php
```

**Run daily digest**:
```bash
docker exec -it access100-app php /var/www/html/api/cron/digest.php --dry-run
docker exec -it access100-app php /var/www/html/api/cron/digest.php
```

**Verify**:
- [ ] Queued notifications exist in `notification_queue` with `status = 'pending'`
- [ ] Digest cron batches them into one email per subscriber
- [ ] Digest email lists all new/changed meetings since last digest
- [ ] Queue entries marked as `status = 'sent'` after processing

### Flow 10: AI Summary Generation

```bash
# Make sure a meeting has agenda text but no summary:
# (TEST-001 should already have description text)

docker exec -it access100-app php /var/www/html/api/cron/summarize.php --dry-run
docker exec -it access100-app php /var/www/html/api/cron/summarize.php
```

**Verify**:
- [ ] Dry-run identifies meetings needing summaries
- [ ] After real run, `summary_text` is populated:
  ```sql
  SELECT state_id, LEFT(summary_text, 100) FROM meetings WHERE summary_text IS NOT NULL;
  ```
- [ ] Summary is plain-language, not raw agenda
- [ ] Meeting detail page (`/meetings/TEST-001`) now shows the AI Summary section

---

## Visual & Accessibility Checks

Run these on every page (`/meetings/`, `/meetings/TEST-001`, `/meetings/councils/`, `/meetings/subscribe`, `/notifications/manage`):

### Dark Mode
- [ ] Toggle system dark mode (or use the theme toggle)
- [ ] All text remains readable (no white-on-white or black-on-black)
- [ ] Cards, inputs, buttons use appropriate dark colors
- [ ] No hard-coded colors that break in dark mode

### Mobile Responsive
- [ ] Chrome DevTools device toolbar at 375px (iPhone SE)
- [ ] No horizontal scrolling
- [ ] Touch targets are at least 44x44px
- [ ] Filter bar stacks vertically on mobile
- [ ] Cards are full-width on mobile
- [ ] Navigation drawer works

### Keyboard Navigation
- [ ] Tab through every interactive element — focus ring visible on each
- [ ] Enter/Space activates buttons and links
- [ ] Escape closes any modals or drawers
- [ ] Skip link ("Skip to content") works on every page

### Screen Reader (Optional but Recommended)
- [ ] Page headings are announced in logical order (h1 > h2 > h3)
- [ ] Form labels are associated with inputs
- [ ] Error messages are announced when they appear
- [ ] Decorative images have empty alt attributes

---

## API Direct Testing (curl)

Useful for isolating issues — test the API independently of WordPress.

```bash
API="http://localhost:8082/api/v1"
KEY="your-api-key-here"

# Health check (no auth required)
curl -s "$API/health" | jq .

# Stats (no auth required)
curl -s "$API/stats" | jq .

# List meetings
curl -s -H "X-API-Key: $KEY" "$API/meetings?limit=5" | jq .

# Single meeting
curl -s -H "X-API-Key: $KEY" "$API/meetings/TEST-001" | jq .

# Meeting summary
curl -s -H "X-API-Key: $KEY" "$API/meetings/TEST-001/summary" | jq .

# Meeting ICS
curl -s -H "X-API-Key: $KEY" "$API/meetings/TEST-001/ics"

# List councils
curl -s -H "X-API-Key: $KEY" "$API/councils" | jq .

# Create subscription
curl -s -X POST -H "X-API-Key: $KEY" -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "channels": ["email"],
    "council_ids": [1],
    "frequency": "immediate",
    "source": "civime"
  }' "$API/subscriptions" | jq .

# Rate limit test (should get 429 after threshold)
for i in $(seq 1 120); do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" -H "X-API-Key: $KEY" "$API/meetings?limit=1")
  echo "Request $i: $STATUS"
  if [ "$STATUS" = "429" ]; then echo "Rate limited at request $i"; break; fi
done
```

---

## Troubleshooting

| Symptom | Likely Cause | Fix |
|---|---|---|
| Meetings page is blank | API URL misconfigured in CiviMe settings | Check Settings > CiviMe, test connection |
| Meetings page shows error banner | API key mismatch | Ensure WP API key matches Access100 `.env` |
| 404 on `/meetings/` | Rewrite rules not flushed | `wp rewrite flush` or re-activate plugins |
| Fields missing on cards (no council name) | Data mapper not applied | Verify `class-data-mapper.php` exists and is autoloaded |
| Subscribe form 500 error | API subscriptions endpoint issue | Check API container logs: `docker logs access100-app` |
| Confirmation email not received | SendGrid not configured | Check `.env` for `SENDGRID_API_KEY`, check SendGrid dashboard |
| Cron finds no changes | `scraper_state` has recent timestamp | Reset: `UPDATE scraper_state SET last_run = '2000-01-01'` |
| Dark mode colors wrong | Hard-coded color in template | All colors should use `var(--color-*)` custom properties |

---

## Cleanup After Testing

```sql
-- Remove test data
DELETE FROM meetings WHERE state_id LIKE 'TEST-%';
DELETE FROM notification_log WHERE meeting_id NOT IN (SELECT id FROM meetings);
DELETE FROM notification_queue WHERE meeting_id NOT IN (SELECT id FROM meetings);
-- Leave councils — they're real data from the scraper
```
