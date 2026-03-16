# Data Flow

This document shows how data moves through the civi.me system at runtime. Three flows are covered: a public page load, the subscription lifecycle, and admin operations. Each is a separate sequence diagram.

---

## Public Page Load

When a visitor requests a public page, WordPress checks its transient cache before calling the Access100 API. Cache hits return immediately; misses fetch from the API and populate the cache for subsequent requests.

```mermaid
sequenceDiagram
    participant Browser
    participant WordPress
    participant TransientCache as WP Transient Cache
    participant API as Access100 API

    Browser->>WordPress: GET /meetings/
    WordPress->>WordPress: Rewrite rules match → civime_route=meetings-list
    WordPress->>WordPress: template_include → meetings-list.php
    WordPress->>TransientCache: get_transient(civime_cache_{hash})
    alt Cache hit
        TransientCache-->>WordPress: Cached meeting list (up to 15 min old)
    else Cache miss
        WordPress->>API: GET /api/v1/meetings (X-API-Key)
        API-->>WordPress: JSON meeting list
        WordPress->>TransientCache: set_transient(key, data, 900s)
    end
    WordPress-->>Browser: Rendered HTML
```

---

## Subscription Lifecycle

The subscription flow spans both systems and involves email. WordPress handles the form submission and sanitization; the Access100 API owns the subscription state and sends the confirmation email via Gmail API. The confirm and manage steps happen via token-authenticated API calls.

```mermaid
sequenceDiagram
    participant Browser
    participant WordPress
    participant API as Access100 API
    participant Gmail as Gmail API

    Browser->>WordPress: POST /meetings/subscribe/ (email, council_ids)
    WordPress->>WordPress: Honeypot check, nonce verify, sanitize
    WordPress->>API: POST /api/v1/subscriptions {email, council_ids, source=civime}
    API->>API: Generate confirm_token, store subscription (unconfirmed)
    API->>Gmail: Send confirmation email (confirm link with token)
    Gmail-->>API: Delivered
    API-->>WordPress: {id, status: pending}
    WordPress-->>Browser: Redirect → thank-you state

    Note over Browser,API: User clicks email link

    Browser->>API: GET /api/v1/subscriptions/{id}/confirm?token={confirm_token}
    API->>API: Validate token, mark confirmed, generate manage_token
    API-->>Browser: Redirect → /notifications/confirmed/

    Note over Browser,API: Future management

    Browser->>WordPress: GET /notifications/manage/?id={id}&token={manage_token}
    WordPress->>API: GET /api/v1/subscriptions/{id}?token={manage_token}
    API-->>WordPress: Full subscription data
    WordPress-->>Browser: Manage form
    Browser->>WordPress: POST updates
    WordPress->>API: PATCH /api/v1/subscriptions/{id}?token={manage_token}
    API-->>WordPress: Updated subscription
    WordPress-->>Browser: Redirect (PRG)
```

---

## Admin Operations

Admin pages in WordPress make live (uncached) API calls to ensure current data. Write operations (like triggering a scrape) use POST-redirect-GET to prevent duplicate submissions.

```mermaid
sequenceDiagram
    participant Admin as WP Admin (browser)
    participant WordPress
    participant API as Access100 API

    Admin->>WordPress: GET /wp-admin/admin.php?page=civime-sync
    WordPress->>WordPress: Check manage_options capability
    WordPress->>API: GET /api/v1/health (live, no cache)
    WordPress->>API: GET /api/v1/stats (cached)
    WordPress->>API: GET /api/v1/admin/scraper/runs (live, no cache)
    API-->>WordPress: All responses
    WordPress-->>Admin: Rendered admin page

    Note over Admin,API: Admin triggers manual scrape

    Admin->>WordPress: POST (nonce + action=trigger_scrape)
    WordPress->>API: POST /api/v1/admin/scraper/trigger (X-API-Key)
    API-->>WordPress: {status: triggered}
    WordPress-->>Admin: Redirect (PRG) → success notice
```

---

## See Also

- [OVERVIEW.md](OVERVIEW.md) — System context and component map
- [CACHING.md](CACHING.md) — Cache behavior: what gets cached, TTL, and how to clear
