# Copilot Instructions — FormBuilder

## Project Overview

Multi-tenant SaaS platform for visual form building with workflow automation. Modular monolith architecture.

## Architecture

- **Monorepo**: `app/api/` (Symfony) + `app/web/` (React SPA) + `packages/types/` (shared types)
- **Backend**: PHP 8.4, Symfony 8 with API Platform, Doctrine ORM, Messenger (async queues via Redis)
- **Frontend**: React, TypeScript, Vite, Tailwind CSS v4, React Flow (workflow editor), dnd-kit (drag-and-drop)
- **Infrastructure**: Docker Compose, FrankenPHP (Caddy-based), PostgreSQL 16 (pgvector), Redis, Mercure (WebSockets), Mailpit (dev email)
- **Routing**: Caddy reverse proxy gateway routes `sentinel.localhost` → web, `api.sentinel.localhost` → API
- **Data flow**: YAML (import/export/AI) → Symfony YAML Parser → PHP Array → JSONB (PostgreSQL) → React Props

## Development Environment

Everything runs in Docker. All commands go through `docker compose exec` (wrapped by `Makefile`).

```
make install          # First-time setup: copy .env, build, install deps
make start / stop     # Start/stop all services
make logs             # Tail all logs (logs-api, logs-web for individual)
```

**URLs (dev)**:
- Web: https://sentinel.localhost
- API: https://api.sentinel.localhost
- API Docs: https://api.sentinel.localhost/api
- pgAdmin: http://localhost:5050
- Mailpit: http://localhost:8025
- Mercure: https://api.sentinel.localhost/.well-known/mercure

### API Commands (Symfony)

```
make api-test                          # Run all API tests (PHPUnit)
make api-shell                         # Shell into API container
make api-console <command>             # Run Symfony console command
make db-migrate                        # Run Doctrine migrations
make db-reset                          # Drop + recreate + migrate (destructive)
make db-seed                           # Load fixtures
```

Run a single test from inside the container:
```
docker compose exec api php bin/phpunit tests/Path/To/TestFile.php
docker compose exec api php bin/phpunit --filter testMethodName
```

### Web Commands (React)

```
make web-test                          # Run all frontend tests
make web-build                         # Production build
make web-shell                         # Shell into web container
```

Run a single test from inside the container:
```
docker compose exec web npx vitest run src/path/to/file.test.ts
```

### HTTPS / SSL Certificates

The Caddy gateway handles TLS. Configuration differs between local development and production.

#### Local Development (mkcert)

Local HTTPS uses [mkcert](https://github.com/FiloSottile/mkcert) to generate certificates trusted by the system and browsers. The `compose.override.yml` mounts `Caddyfile.dev` (with `tls` directives) and the `certs/` directory.

**First-time setup**:
```bash
# Install mkcert CA into system/browser trust stores (one-time)
mkcert -install

# Generate wildcard certificate for sentinel.localhost
mkdir -p docker/caddy/certs
cd docker/caddy/certs
mkcert sentinel.localhost "*.sentinel.localhost"
```

This creates `sentinel.localhost+1.pem` and `sentinel.localhost+1-key.pem` in `docker/caddy/certs/`. Certs are gitignored — each developer generates their own.

**Regenerate** (if expired or domain changes): re-run the `mkcert` command above, then `docker compose restart caddy`.

#### Production (automatic Let's Encrypt)

In production, Caddy automatically obtains and renews TLS certificates from Let's Encrypt. No manual certificate management needed.

Set the `DOMAIN` env var in `.env` (or your deployment config):
```env
DOMAIN=myapp.com
```

Caddy will automatically:
- Obtain certificates for `myapp.com` and `api.myapp.com`
- Renew them before expiry
- Redirect HTTP → HTTPS

**Requirements**: ports 80 and 443 must be publicly accessible, and DNS A records for `myapp.com` and `api.myapp.com` must point to the server.

### PHP Configuration

PHP ini files live in `docker/api/conf.d/` and are copied into the container at build time:

| File | Stage | Purpose |
|------|-------|---------|
| `app.ini` | All | Base config: timezone, memory limit, upload limits, OPcache |
| `app.dev.ini` | Dev only | Error display, OPcache revalidation, Xdebug settings |
| `app.prod.ini` | Prod only | Errors hidden, OPcache optimized, Symfony preloading |

### Xdebug

Xdebug is installed only in the `dev` Docker stage (not in production). Configured via `docker/api/conf.d/app.dev.ini` and `compose.override.yml` (`XDEBUG_MODE=debug`).

**PhpStorm setup**:
1. Settings → PHP → Servers: add server named `sentinel`, host `api.sentinel.localhost`, port `80`, Debugger `Xdebug`. Enable path mappings: map project's `app/api` → `/app`
2. Settings → PHP → Debug: ensure Xdebug port is `9003`
3. Run → Start Listening for PHP Debug Connections

## Domain Model Conventions

### Multi-tenancy

All data is scoped to an **Organization**. Every query and mutation must respect organization isolation.

### Form Hierarchy (Composite Pattern)

Forms use a tree structure, not a flat list:
- **Stage** → logical step/page of the form
- **Container** → layout wrapper (12-column grid), holds children
- **Element** → leaf component (Input, Radio, Checkbox, File Upload, etc.)

Containers don't store values — they store other elements via `children`.

### Revision Control

Form schema changes create a new **revision**. Submissions are bound to the `revision_id` at creation time. Users who started filling an older revision complete it on the old structure.

### Token System

Templating uses `{{ form.field_name }}`, `{{ system.date }}`, `{{ current_user.email }}`, and custom tokens. Used in integrations, PDF templates, and messages. Secrets in integration configs must be masked before logging.

### Integrations

- **Lookup** (synchronous): real-time data fetching for dropdowns/autofill, cache results
- **Execution** (asynchronous): post-submission actions via Symfony Messenger queue (email, webhooks, CRM push)

## MCP Servers

### Playwright (browser automation & testing)

```
npx @playwright/mcp@latest
```

Configure in PhpStorm: Settings → Tools → AI Assistant → MCP Servers.