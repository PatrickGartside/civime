# Contributing to civi.me

Thank you for your interest in making government more accessible to the people of Hawaii. This guide will help you get started.

## Development Setup

### Prerequisites

- Docker and Docker Compose
- A text editor (VS Code recommended)
- Basic familiarity with WordPress theme/plugin development

### Local Environment

1. Clone the repository:

   ```bash
   git clone https://github.com/civime/civi.me.git ~/dev/civi.me
   ```

2. Set up the Docker infrastructure (see the Docker setup in the project wiki or ask a maintainer).

3. The theme and plugins are bind-mounted from this repo into the WordPress container:
   - `wp-content/themes/civime/` — Theme
   - `wp-content/plugins/civime-*/` — Plugins

4. Configure the API connection in WP Admin > Settings > CiviMe:
   - API URL: your local Access100 API URL
   - API Key: your dev API key

### Project Structure

```
wp-content/
├── themes/civime/          # Custom theme (design system, layout, navigation)
├── plugins/
│   ├── civime-core/        # API client, settings, caching
│   ├── civime-meetings/    # Meeting calendar + council browser
│   ├── civime-notifications/ # Subscribe + manage notification preferences
│   ├── civime-topics/      # "What Matters to Me" topic picker
│   ├── civime-events/      # Community event listings
│   └── civime-guides/      # How-to civic engagement guides
└── page-content/           # Static page HTML (synced via WP-CLI)
```

## Coding Standards

### PHP

- PHP 8.2+ required
- Use union types and return types
- Follow `CiviMe_{Plugin}_` class prefix convention
- Use the SPL autoloader pattern (see any plugin's main file)
- All API calls go through `civime_api()` — never make direct HTTP requests

### Security (Non-Negotiable)

- **Nonce verification** on every form submission: `wp_verify_nonce()`
- **Input sanitization**: `sanitize_text_field(wp_unslash())` for text, `sanitize_email()` for email, `absint()` for IDs
- **Output escaping**: `esc_html()`, `esc_attr()`, `esc_url()` on all template output — no exceptions
- **POST-redirect-GET** for all form submissions
- Never expose API keys, tokens, or error details to the browser

### CSS

- BEM naming convention: `.block__element--modifier`
- Use theme CSS custom properties (never hardcode colors)
- Mobile-first responsive design
- 44px minimum touch targets (WCAG 2.1 AA)
- Include dark mode styles using `[data-theme="dark"]` selector

### JavaScript

- Vanilla JS only (no jQuery, no frameworks)
- IIFE scope to prevent global pollution
- Progressive enhancement (everything must work without JS)
- `DOMContentLoaded` event for initialization

### Accessibility (WCAG 2.1 AA)

This project has an A+ accessibility rating. Please maintain it:

- Semantic HTML elements (`<nav>`, `<main>`, `<section>`, etc.)
- Proper heading hierarchy (h1 → h2 → h3, never skip levels)
- ARIA labels on interactive elements
- Focus management for dynamic content
- Color contrast ratios: 4.5:1 normal text, 3:1 large text
- Form labels associated with inputs via `for`/`id`

## Making Changes

### Branch Naming

- `feature/description` — New features
- `fix/description` — Bug fixes
- `docs/description` — Documentation changes

### Commit Messages

Use clear, descriptive commit messages:

```
Add council search filter to meetings list

- Filter bar now includes a council dropdown
- Councils fetched from API with caching
- Mobile-responsive filter layout
```

### Pull Requests

1. Create a feature branch from `main`
2. Make your changes following the standards above
3. Test locally against the WordPress container
4. Submit a PR with:
   - Description of what changed and why
   - Screenshots for visual changes
   - Accessibility testing notes

### Testing Checklist

Before submitting a PR, verify:

- [ ] No PHP errors or warnings
- [ ] All output properly escaped
- [ ] Forms have nonce verification
- [ ] Responsive layout works on mobile
- [ ] Dark mode renders correctly
- [ ] Keyboard navigation works
- [ ] Screen reader announces content properly

## Architecture Decisions

- **Plugin-per-feature**: Each feature is a separate plugin for loose coupling
- **Server-side API calls**: WordPress calls Access100 API server-to-server (never from the browser)
- **Transient caching**: API responses cached via WordPress transients (configurable TTL)
- **Token-based subscription auth**: Users manage notifications without a WordPress account
- **No CAPTCHA**: Honeypot anti-spam (privacy-preserving)

## Getting Help

- Open an issue for bugs or feature requests
- Tag maintainers for architecture questions
- Check ARCHITECTURE.md for system design details
- Check TESTING.md for end-to-end testing flows

## License

By contributing, you agree that your contributions will be licensed under the same license as the project.
