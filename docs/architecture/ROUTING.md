# civi.me URL Routing

civi.me uses two routing mechanisms side-by-side. Router-class plugins register custom rewrite rules; CPT plugins use WordPress native routing. This document maps every URL to the plugin that owns it.

---

## Routing Mechanisms

### Router Classes (civime-meetings, civime-notifications, civime-topics)

These plugins register custom rewrite rules programmatically:

- Register rules via `add_rewrite_rule(..., 'top')` on the `init` hook
- Map URL patterns to query vars, then from query vars to templates via a `template_include` filter
- Override the 404 response status to 200 via the `wp` action (WordPress would otherwise treat unknown rewrite vars as 404s)

### Custom Post Types (civime-guides, civime-events)

These plugins use WordPress's built-in CPT routing:

- Register a CPT with `public: true` and `has_archive: true`
- WordPress handles URL routing natively via the CPT slug (e.g., `civime_guide` → `/guides/`)
- The plugin uses `template_include` to load its own template instead of falling through to the theme

---

## Complete Routing Table

| URL Pattern | Plugin | Mechanism | Query Var | Template |
|---|---|---|---|---|
| `/meetings/` | civime-meetings | Router | `civime_route=meetings-list` | `templates/meetings-list.php` |
| `/meetings/{id}/` | civime-meetings | Router | `civime_route=meeting-detail` | `templates/meeting-detail.php` |
| `/meetings/{id}/ics/` | civime-meetings | Router | `civime_route=meeting-ics` | Served directly (ICS proxy) |
| `/meetings/{id}/notify/` | civime-meetings* | Router | `civime_notif_route=notify` | notifications `templates/notify.php` |
| `/meetings/subscribe/` | civime-meetings* | Router | `civime_notif_route=subscribe` | notifications `templates/subscribe.php` |
| `/councils/` | civime-meetings | Router | `civime_route=councils-list` | `templates/councils-list.php` |
| `/councils/{slug}/` | civime-meetings | Router | `civime_route=council-profile` | `templates/council-profile.php` |
| `/notifications/manage/` | civime-notifications | Router | `civime_notif_route=manage` | `templates/manage.php` |
| `/notifications/confirmed/` | civime-notifications | Router | `civime_notif_route=confirmed` | `templates/confirmed.php` |
| `/notifications/unsubscribed/` | civime-notifications | Router | `civime_notif_route=unsubscribed` | `templates/unsubscribed.php` |
| `/topics/` | civime-topics | Router | `civime_route=topic-picker` | `templates/topic-picker.php` |
| `/what-matters/` | civime-topics | Router | `civime_route=topic-picker` | `templates/topic-picker.php` (alias) |
| `/topics/{slug}/` | civime-topics | Router | `civime_route=topic-page` | `templates/topic-page.php` |
| `/guides/` | civime-guides | CPT archive | WordPress native | `templates/archive-guide.php` |
| `/guides/{slug}/` | civime-guides | CPT single | WordPress native | `templates/single-guide.php` |
| `/events/` | civime-events | CPT archive | WordPress native | (plugin template) |
| `/events/{slug}/` | civime-events | CPT single | WordPress native | (plugin template) |
| All other URLs | civime (theme) / WordPress | WP native | — | Theme templates |

\* Registered by civime-meetings router to control matching order; template rendered by civime-notifications.

---

## Priority System

The priority system determines which rules appear earliest in WordPress's compiled rewrite array — and therefore match first.

### How it works

The `'top'` position in `add_rewrite_rule()` prepends the rule to the compiled rewrite array. Plugins that register on `init` at a higher priority number fire later. Because each call prepends, later-registered rules appear earlier in the final array and match first.

| Plugin | `init` Priority | Registered After |
|---|---|---|
| civime-meetings | 10 (default) | Earlier |
| civime-topics | 10 (default) | Earlier |
| civime-notifications | 11 | Later (fires after 10) |

**Consequence:** Notifications rules appear before meetings rules in the compiled array — they match first.

### The meetings/notify cross-registration

The `/meetings/{id}/notify/` and `/meetings/subscribe/` rules are registered in the **civime-meetings router** (not civime-notifications), specifically so they sit in the correct position relative to the `/meetings/{id}/` catch-all rule.

If those rules were registered by civime-notifications at priority 11, they would appear before the `/meetings/{id}/` rule — correct. But they would also appear before `/meetings/{id}/ics/` — incorrect. Registering them in the meetings router at priority 10 places them exactly where they belong in the matching sequence.

---

## Shared Query Variables

Two query vars are shared across plugins by design:

**`civime_route`**
Shared between civime-meetings and civime-topics. Both plugins use the same `template_include` dispatch pattern, and the value (e.g., `meetings-list`, `topic-picker`) is unique per URL so there is no collision.

**`civime_notif_route`**
Logically owned by civime-notifications. However, it is registered in the civime-meetings router to control matching order (see Priority System above). The template is loaded by civime-notifications regardless of where the rule is registered.

This is the one intentional cross-plugin coordination point in the routing system. It is load-bearing — changing which plugin registers those rules affects matching order.

---

## See Also

- [OVERVIEW.md](./OVERVIEW.md) — Two-system boundary and design principles
- [DATA-FLOW.md](./DATA-FLOW.md) — Sequence diagrams for page load, subscription lifecycle, and admin operations
