# Contributing to civi.me

Thank you for your interest in contributing to civi.me. This project exists to make Hawaii's government information genuinely accessible to residents — every improvement helps. Whether you're fixing a bug, improving accessibility, adding a translation, or refining documentation, contributions are welcome.

---

## Development Setup

### Prerequisites

- Docker and Docker Compose
- PHP 8.2+ (runs in container, but useful for IDE type checking and linting)
- Git

### Getting your environment running

Follow the [Infrastructure Setup Guide](docs/infrastructure/INFRASTRUCTURE.md) for complete local development setup, including Docker configuration, environment variables, and troubleshooting.

Quick version:

1. Clone your fork:
   ```bash
   git clone https://github.com/YOUR-USERNAME/civi.me.git
   cd civi.me
   ```

2. Set up Docker infrastructure per the [Infrastructure Setup Guide](docs/infrastructure/INFRASTRUCTURE.md).

3. Configure the API connection in WP Admin > Settings > CiviMe:
   - **API URL:** your local or dev Access100 API URL
   - **API Key:** your dev API key

The theme and plugins are bind-mounted from the repo into the WordPress container — changes you make in your editor are immediately reflected in the running site.

Do not duplicate the Docker setup here — refer to [INFRASTRUCTURE.md](docs/infrastructure/INFRASTRUCTURE.md) for the full configuration.

---

## How to Contribute

### Fork and Branch

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a feature branch from `main`:
   ```bash
   git checkout -b feature/your-description
   ```

### Branch Naming

- `feature/description` — New features or enhancements
- `fix/description` — Bug fixes
- `docs/description` — Documentation-only changes
- `a11y/description` — Accessibility improvements

### Making Changes

- Read the [Architecture Overview](docs/architecture/OVERVIEW.md) before making changes — understanding the two-system boundary is important
- Check the [Plugin Reference](docs/plugins/PLUGINS.md) for patterns to follow in each plugin
- All API calls go through `civime_api()` — never make direct HTTP requests from plugin code
- Test locally against the running WordPress container before submitting

### Submit a Pull Request

1. Push your branch to your fork
2. Open a pull request against `main` on the upstream repo
3. In the PR description, explain what changed and why
4. Include screenshots for any visual changes
5. Note any accessibility testing you did (keyboard nav, screen reader, contrast check)

No issue is required before submitting a PR. We review all contributions. If you are planning a significant change, opening a discussion first can save rework — but it is not required.

---

## Coding Standards

These are the key conventions. For full patterns, examples, and architectural details, see the [Plugin Reference](docs/plugins/PLUGINS.md).

### PHP

- PHP 8.2+ with union types and return types
- `CiviMe_{Plugin}_` class prefix (e.g., `CiviMe_Meetings_Router`, `CiviMe_Core_API_Client`)
- SPL autoloader pattern (see any plugin's main file for the pattern)
- Router → Controller → Template structure for page rendering
- All API calls through `civime_api()` singleton — never instantiate `CiviMe_Core_API_Client` directly

### CSS

- BEM naming: `.block__element--modifier`
- Use theme CSS custom properties — never hardcode colors or spacing values
- Mobile-first responsive design (base styles for mobile, media queries for wider viewports)
- 44px minimum touch targets (WCAG 2.1 AA requirement)
- Dark mode styles via `[data-theme="dark"]` selector

### JavaScript

- Vanilla JS only — no jQuery, no frameworks
- IIFE scope to prevent global pollution
- Progressive enhancement — everything must work without JS enabled
- `DOMContentLoaded` for initialization

### Security (Non-Negotiable)

These rules apply to every form, every endpoint, every template:

- **Nonce verification** on every form submission: `wp_verify_nonce()`
- **Input sanitization**: `sanitize_text_field(wp_unslash())` for text, `sanitize_email()` for email, `absint()` for integer IDs
- **Output escaping**: `esc_html()`, `esc_attr()`, `esc_url()` on all template output — no raw variable output, no exceptions
- **POST-redirect-GET** for all form submissions — prevent double-submit on refresh
- Never expose API keys, tokens, or internal error details to the browser

---

## Accessibility

This project holds an A+ accessibility rating. Please maintain it.

Every change that touches the frontend must meet WCAG 2.1 AA:

- Semantic HTML elements (`<nav>`, `<main>`, `<section>`, `<article>`, etc.)
- Proper heading hierarchy — never skip levels (h1 → h2 → h3)
- ARIA labels on interactive elements that don't have visible text labels
- Color contrast ratios: 4.5:1 for normal text, 3:1 for large text
- Focus management for any dynamically inserted content
- Form labels associated with inputs via `for`/`id` attributes

If you add a new UI component, test it with keyboard navigation and at least one screen reader (VoiceOver on macOS, NVDA on Windows, or TalkBack on Android).

---

## Before Submitting

Run through this checklist before opening a PR:

- [ ] No PHP errors or warnings in the WordPress error log
- [ ] All template output is properly escaped
- [ ] All forms have nonce verification
- [ ] Layout is responsive on mobile viewports
- [ ] Dark mode renders correctly
- [ ] Keyboard navigation works through all interactive elements
- [ ] Screen reader announces dynamic content changes

---

## Architecture Quick Reference

Key architectural decisions that affect day-to-day development:

- **Plugin-per-feature** — each feature is its own plugin; only shared utilities belong in civime-core
- **WordPress calls Access100 API server-to-server** — browsers never make API calls directly; WordPress proxies all data
- **Token-based subscription auth** — subscribers use opaque email tokens, not WordPress accounts
- **Transient caching** — API responses are cached via WordPress transients; public pages use 15-minute TTL; admin pages bypass the cache

For the rationale behind these decisions, see the [Architecture Decision Records](docs/decisions/).

---

## License

By contributing, you agree that your contributions will be licensed under the GPL v2 — the same license as the project. See [LICENSE](LICENSE) for details.
