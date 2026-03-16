# Token-Based Subscription Authentication

## Status

Accepted

## Context and Problem Statement

Hawaii residents subscribing to civic meeting alerts need to be able to manage their preferences and unsubscribe. The system needed an authentication mechanism for subscription management that does not require creating or maintaining a WordPress account. The target audience is general public — civic residents who should not need to learn a registration flow just to receive government alerts.

## Decision Drivers

* Low barrier to entry: target audience should not need to create and maintain a WordPress account to receive civic alerts
* Email verification: subscription must require proving ownership of the email address before activation
* Stateless from WordPress perspective: WordPress should not hold subscriber session state

## Considered Options

* WordPress account creation (register, login, manage preferences in WP dashboard)
* Token-based auth (confirm_token + manage_token stored in API, passed in URLs)

## Decision Outcome

Chosen option: "Token-based auth (confirm_token + manage_token)", because it removes the WordPress account requirement for residents, proves email ownership at the point of subscription, keeps WordPress stateless with respect to subscriber sessions, and matches the established pattern for civic tech alert subscriptions.

### Positive Consequences

* Subscription management works without any WordPress login or account creation
* WordPress cannot be attacked to gain subscription management access — tokens live only in the Access100 API database
* Unsubscribe path is always accessible from the email itself (no "forgot password" flow needed)

### Negative Consequences

* Token compromise = subscription access. Tokens must be treated as credentials and handled carefully in URLs (HTTPS required; avoid logging)
* No password reset flow exists — losing access to the confirming email address means losing subscription management access (accepted trade-off for this use case)
* WordPress cannot independently verify token validity — it relies entirely on the API response (this is by design; WordPress is deliberately stateless with respect to subscriber sessions)
