# Plugin-Per-Feature Architecture

## Status

Accepted

## Context and Problem Statement

When building the WordPress frontend for civi.me, a decision was needed on how to package feature code. The main choice was between a single monolithic plugin containing all feature logic, and a plugin-per-feature approach where each major feature (meetings, notifications, guides, i18n, events, topics) is its own independent WordPress plugin. Each approach has different implications for activation safety, code ownership, and deployment cadence.

## Decision Drivers

* Activation independence: a broken deploy of one plugin must not take down other features
* Clear code ownership: a contributor should be able to work on one feature without reading the full codebase
* Incremental deployment: plugins must be able to evolve at different paces (civime-meetings is Complete; civime-events is Active/in-development)

## Considered Options

* One monolithic civime plugin containing all features
* Plugin-per-feature with shared civime-core

## Decision Outcome

Chosen option: "Plugin-per-feature with civime-core as shared foundation", because it enables activation independence, enforces a clean dependency direction (all plugins depend on civime-core, no plugin depends on another feature plugin), and allows features to evolve at different paces.

### Positive Consequences

* Any single plugin can be disabled for debugging or staged rollout without affecting other features
* New feature plugins can be added without modifying existing ones
* civime-core provides one place to change shared behavior (API client, caching) for all plugins

### Negative Consequences

* Inter-plugin coordination is required: civime-meetings registers notification sub-routes for URL ordering reasons; the civime_route query var is shared between meetings and topics
* Each plugin needs its own bootstrap, autoloader, and constants — minor boilerplate duplication
