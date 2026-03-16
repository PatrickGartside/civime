# Caching

The civime-core API client caches public-facing API responses in WordPress to reduce latency and API load. Admin endpoints and the health check always fetch live data.

---

## What Gets Cached

### Cached (15-minute default TTL)

| Endpoint | Data |
|----------|------|
| Meeting list | All upcoming meetings |
| Meeting detail | Single meeting with agenda/documents |
| Council list | All councils |
| Council profile | Single council with members and vacancies |
| Public stats | Aggregate engagement metrics |

### Never Cached

| Endpoint | Reason |
|----------|--------|
| API health check | Always live — powers status display on the Sync admin page |
| All admin endpoints | Subscribers, meetings management, reminders, scraper data — always current |
| POST / PATCH / DELETE | Write operations always bypass the cache |

---

## Cache Duration

Public API responses are cached for **15 minutes** by default. The TTL is configurable in WordPress **Settings > CiviMe**.

> **Note:** There is an intentional lag for public visitors — meeting data displayed on the site may be up to 15 minutes old. Admin pages always show current data.

---

## Clearing the Cache

The cache can be cleared from the WordPress admin in two ways:

- **Clear all at once:** Settings > CiviMe — use the "Clear Cache" action to remove all cached API data immediately.
- **Per-endpoint automatic clearing:** When admin operations modify data (e.g., editing a meeting or council), the relevant cache entries are cleared automatically.

---

## Enabling and Disabling

Caching can be disabled entirely in **Settings > CiviMe**. This is useful during development or when troubleshooting data freshness issues.

---

## See Also

- [DATA-FLOW.md](DATA-FLOW.md) — The public page load diagram shows the cache check in sequence
- [OVERVIEW.md](OVERVIEW.md) — System context and component map
