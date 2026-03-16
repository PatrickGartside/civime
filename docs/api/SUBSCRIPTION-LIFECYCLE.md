# Subscription Lifecycle

This document extends the [DATA-FLOW.md sequence diagram](../architecture/DATA-FLOW.md) with API-level detail — exact endpoints, request/response shapes, and token behavior at each step. Read DATA-FLOW.md first for the high-level sequence; this document adds the specifics a plugin developer needs to implement or debug the subscription flow.

---

## Overview

A subscriber goes through four possible phases: subscription creation, email confirmation, optional preference management, and optional unsubscribe. Two tokens govern this flow: `confirm_token` (used once to verify email ownership) and `manage_token` (permanent self-service access for all subscription management operations). Neither token expires.

The entire flow is initiated server-to-server by WordPress using an `X-API-Key` — the subscriber's browser never calls the Access100 API directly. Only the confirm and unsubscribe links (embedded in emails) are called directly by the subscriber, and those are public endpoints that require no key.

---

## Token Model

### confirm_token

- **Location:** `users.confirm_token` (subscription flow) and `reminders.confirm_token` (reminder flow — separate column in a separate table)
- **Format:** 64-character hex string
- **Generated:** At subscription creation (`POST /subscriptions`) or reminder creation (`POST /reminders`)
- **Use:** Embedded as `?token={confirm_token}` in the confirmation email link
- **Validated by:** `GET /subscriptions/confirm?token=` or `GET /reminders/confirm?token=`
- **Lifecycle:** Not cleared after use — `users.confirmed_email = 1` is the authoritative confirmed state; the token column retains its value
- **Expiry:** None — does not expire; remains valid unless the user re-subscribes with the same email and a new token is generated

### manage_token

- **Location:** `users.manage_token`
- **Format:** 64-character hex string
- **Generated:** At subscription creation, returned in the 201 response body
- **Use:** Required `?token=` query param for all `GET`, `PATCH`, `PUT`, and `DELETE` calls on `/subscriptions/{id}`
- **Scope:** One token per user — covers all of that user's subscriptions regardless of how many councils they are subscribed to
- **Lifecycle:** Permanent — does not expire or rotate; the same token is used for all future self-service operations
- **Storage:** WordPress does not store the manage_token — it is returned in the API 201 response and embedded in every email footer sent to the subscriber

---

## Lifecycle Steps

### Step 1: Create Subscription

WordPress calls this endpoint when a visitor submits the subscribe form on civi.me.

```
POST /api/v1/subscriptions
X-API-Key: {key}
Content-Type: application/json
```

**Request body:**

```json
{
  "email": "user@example.com",
  "phone": "+18085551234",
  "channels": ["email"],
  "council_ids": [36, 42],
  "frequency": "immediate",
  "source": "civime"
}
```

| Field | Required | Default | Notes |
|-------|----------|---------|-------|
| `email` | Conditional | — | Required if `email` channel selected |
| `phone` | Conditional | — | E.164 format. Required if `sms` channel selected |
| `channels` | No | `["email"]` | `"email"`, `"sms"`, or both |
| `council_ids` | Conditional | `[]` | At least one of `council_ids` or `topics` required |
| `topics` | Conditional | `[]` | Topic slugs resolved to council IDs server-side |
| `frequency` | No | `"immediate"` | `"immediate"`, `"daily"`, or `"weekly"` |
| `source` | No | `"access100"` | CiviMe plugin always sends `"civime"` |

**Response (201 Created):**

```json
{
  "data": {
    "user_id": 1,
    "status": "pending_confirmation",
    "manage_token": "a1b2c3d4e5f6...",
    "councils": [36, 42],
    "channels": ["email"],
    "frequency": "immediate",
    "message": "Verification sent to user@example.com"
  }
}
```

**DB state after Step 1:**
- `users` row created: `confirm_token` set to new 64-char hex, `confirmed_email = 0`
- `users.manage_token` set to new 64-char hex (returned in response)
- `subscriptions` rows created for each council: `active = 0`

**Token role:** The API generates both tokens at this step. `confirm_token` is embedded in the confirmation email immediately. `manage_token` is returned to WordPress in the response body; WordPress embeds it in all subsequent emails sent to the subscriber.

**Side effect:** Confirmation email (and SMS if applicable) is sent automatically by the API before the 201 response is returned.

---

### Step 2: Confirm Email (Subscriber Clicks Link)

The subscriber clicks the confirmation link in their email. The link is a direct browser request to the API — not proxied through WordPress.

```
GET /api/v1/subscriptions/confirm?token={confirm_token}
(Public — no X-API-Key required)
```

**Response:** HTTP 302 redirect to `https://civi.me/notifications/confirmed`

**DB state after Step 2:**
- `users.confirmed_email` set to `1`
- `subscriptions.active` set to `1` for all rows belonging to this user

**Token role:** `confirm_token` is validated against `users.confirm_token`. After validation, the column is not cleared — `confirmed_email = 1` becomes the authoritative state. A second click on the same link will succeed silently (idempotent).

---

### Step 3: Retrieve Subscription Preferences

When a subscriber clicks "manage my subscriptions" in an email footer, WordPress receives the `manage_token` from the URL and calls this endpoint to fetch current preferences.

```
GET /api/v1/subscriptions/{user_id}?token={manage_token}
(Token auth — manage_token in query param)
```

**Response (200 OK):**

```json
{
  "data": {
    "user_id": 1,
    "email": "user@example.com",
    "phone": "+18085551234",
    "confirmed_email": true,
    "confirmed_phone": false,
    "subscriptions": [
      {
        "subscription_id": 10,
        "council_id": 36,
        "council_name": "Board of Education",
        "channels": ["email"],
        "frequency": "immediate",
        "active": true
      }
    ]
  }
}
```

**Token role:** `manage_token` validated against `users.manage_token`. Returns all subscriptions (active and inactive) for this user.

---

### Step 4: Update Preferences

The subscriber submits a preference update form. WordPress sends this PATCH to the API.

```
PATCH /api/v1/subscriptions/{user_id}?token={manage_token}
X-API-Key: {key}
Content-Type: application/json
```

**Request body (all fields optional):**

```json
{
  "email": "new@example.com",
  "phone": "+18085559999",
  "channels": ["email", "sms"],
  "frequency": "daily"
}
```

**Token role:** `manage_token` validated. Updates user-level fields only (not per-council settings). If `email` changes, `confirmed_email` is reset to `0` and a new confirmation email is sent.

---

### Step 5: Replace Council Subscriptions

The subscriber updates which councils they follow. WordPress sends the full replacement list — any councils not in the new list are deactivated.

```
PUT /api/v1/subscriptions/{user_id}/councils?token={manage_token}
X-API-Key: {key}
Content-Type: application/json
```

**Request body:**

```json
{
  "council_ids": [36, 55, 78]
}
```

**Effect:** This is a full replace, not a merge.
- Councils in the new list that have no existing row: new `subscriptions` rows created with `active = 1`
- Councils in the new list that have an existing row: `active` set to `1` (re-activates if previously unsubscribed)
- Councils with existing rows not in the new list: `active` set to `0` (soft-deactivated)

**Token role:** `manage_token` validated.

---

### Step 6: One-Click Unsubscribe (From Email Footer)

The subscriber clicks the unsubscribe link in an email footer. This is a direct browser request — no API key or WordPress involvement.

```
GET /api/v1/subscriptions/unsubscribe?token={manage_token}
(Public — no X-API-Key required)
```

**Response:** HTTP 302 redirect to `https://civi.me/notifications/unsubscribed`

**DB state after Step 6:**
- All `subscriptions.active` set to `0` for this user

**Token role:** `manage_token` is used here (not `confirm_token`). The `manage_token` is embedded in every email footer. This makes unsubscribe one-click with no login required.

---

### Step 7: Delete Subscription (Programmatic Unsubscribe)

Same outcome as Step 6, but initiated programmatically from the WordPress manage-subscriptions UI.

```
DELETE /api/v1/subscriptions/{user_id}?token={manage_token}
X-API-Key: {key}
```

**Effect:** Soft-deletes all subscriptions (sets `active = 0`). The user row and subscription rows are retained in the database. The subscriber can re-subscribe at any time.

**Token role:** `manage_token` validated.

---

## Reminder Flow

Meeting-day reminders are a separate, lighter flow. A reminder is a one-time email sent on the morning of a specific meeting date. It uses its own `confirm_token` stored in `reminders.confirm_token` — entirely separate from the subscription `confirm_token` in `users.confirm_token`.

### Create Reminder

```
POST /api/v1/reminders
X-API-Key: {key}
Content-Type: application/json
```

**Request body:**

```json
{
  "email": "user@example.com",
  "meeting_state_id": "76181",
  "source": "civime"
}
```

**DB state:** Creates a `reminders` row with a new 64-char `confirm_token`, `confirmed = 0`, `sent = 0`. Sends a confirmation email immediately.

### Confirm Reminder

```
GET /api/v1/reminders/confirm?token={confirm_token}
(Public — no X-API-Key required)
```

**Response:** HTTP 302 redirect (to a confirmation page)

**DB state:** Sets `reminders.confirmed = 1`. On the morning of the meeting, the cron job checks for confirmed, unsent reminders matching that date and sends the reminder email.

**Key distinction:** This `confirm_token` is the value in `reminders.confirm_token`. It is independent of any subscription a subscriber may also have. A user does not need an active subscription to create a reminder, and a reminder confirmation does not affect `users.confirmed_email`.

---

## Notes for Plugin Developers

**WordPress never calls the API from the browser.** All API calls from the WordPress side go through the `CiviMe_API_Client` class in `civime-core`. This class handles authentication (`X-API-Key` header), caching (WordPress transients for GET endpoints), and error normalization (returns `WP_Error` on failure). See `civime-core/includes/class-api-client.php`.

**WordPress does not store manage_token.** The token is returned in the `POST /subscriptions` 201 response. WordPress passes it to the subscriber via the confirmation email and stores nothing. The subscribe-to-manage email round-trip means subscribers always have their token in their inbox. There is no "forgot token" recovery flow — a subscriber who loses access to their email must re-subscribe.

**The confirm and unsubscribe endpoints are public.** `GET /subscriptions/confirm` and `GET /subscriptions/unsubscribe` do not require `X-API-Key`. They are designed to be called directly by the subscriber's browser via the links in emails. WordPress does not proxy these requests.

**Token validation uses timing-safe comparison.** Both `confirm_token` and `manage_token` lookups in the API use constant-time string comparison to prevent timing attacks. Tokens are 64 hex characters (256 bits of entropy) — brute-forcing is not feasible.
