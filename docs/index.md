# civi.me Documentation

Civic engagement platform for Hawaii — making government information functionally accessible. civi.me consists of two systems: a WordPress frontend and the Access100 API backend.

## Documentation Sections

- **[Architecture](architecture/OVERVIEW.md)** — Two-system overview, URL routing, data flow, caching behavior
- **[API](api/ENDPOINTS.md)** — All Access100 API endpoints, subscription lifecycle, OpenAPI reference
- **[Data Model](data-model/SCHEMA.md)** — Database schema and entity relationships
- **[Decisions](decisions/ADR-001-plugin-per-feature.md)** — Architecture Decision Records (plugin-per-feature, token auth)
- **[Infrastructure](infrastructure/INFRASTRUCTURE.md)** — Docker setup, environment configuration, local development
- **[Plugins](plugins/PLUGINS.md)** — WordPress plugin and theme reference (7 plugins + theme)

## Getting Started

**New contributor?** Start with the [Architecture Overview](architecture/OVERVIEW.md) to understand the two-system boundary, then follow the [Infrastructure Setup Guide](infrastructure/INFRASTRUCTURE.md) to run locally.

**Looking to contribute?** See [CONTRIBUTING.md](https://github.com/civime/civi.me/blob/main/CONTRIBUTING.md) for workflow and coding standards.

## About

civi.me is an open source project making Hawaii's government more accessible. Built with WordPress, PHP, and the Access100 API.
