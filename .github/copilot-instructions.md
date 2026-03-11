# Copilot Instructions — FormBuilder

## Project Overview

Multi-tenant SaaS platform for visual form building with workflow automation. Modular monolith architecture.

## Sentinel — Dual-Mode Architecture

Your application (**Sentinel**) has a single codebase (Symfony 7 + React + PostgreSQL with pgvector + Redis), but thanks to an environment variable (e.g., `APP_MODE`), it can operate in two parallel realities:

---

### ☁️ Mode 1: SaaS (Your Cloud Server)

This is your public business where you earn money from subscriptions.

- **Access:** Anyone can visit your website (e.g., `sentinel-app.com`).
- **Registration:** Open. Users enter their email and are required to verify it (to filter out spam bots).
- **Workspace Creation:** Upon registration, their first `Workspace` is automatically created for them, and they are assigned the `OWNER` role.
- **Monetization:** Limits are enforced. If they want to create more than 5 forms or invite colleagues to their Workspace, they are prompted to upgrade to a paid tier via Stripe.
- **Infrastructure:** You manage the servers, backups, and updates yourself.

---

### 📦 Mode 2: Self-Hosted (Client's Server or POS Terminal)

This is the version for enterprise clients, supermarkets, or anyone who wants total control over their data on their own hardware.

- **Access:** The client simply downloads your `docker-compose.prod.yml` and runs `docker compose up -d` on their local server or computer.
- **Registration:** Closed. On the very first launch, a sleek Setup Wizard appears. The first person to enter their details becomes the Super-Admin. No email verification is required.
- **Team Management:** The Admin creates a `Workspace` (e.g., "Main Supermarket Checkouts") and manually creates accounts for cashiers, providing them with logins/passwords and specific roles.
- **Offline Mode (PWA):** The React frontend loads onto the POS terminal and can work completely offline. It saves receipts and form submissions to the browser's local database (IndexedDB) and syncs them to the server in batches once the internet connection is restored.
- **Limits:** Disabled. It's their hardware, so they can create an unlimited number of forms.

---

### 🏗 Solid Foundation (Shared across both modes)

Regardless of where the app is deployed, the underlying architecture is flawless:

1. **Data Isolation:** Everything is tied to Workspaces. API requests always route through `/api/workspaces/{workspaceId}/...`.
2. **Ownership:** Forms belong to the Workspace, not to a specific user. Users are merely participants (`WorkspaceMember` entity) with varying access levels (Admin, Editor, Viewer).
3. **AI Readiness:** The PostgreSQL database is spun up with the `pgvector` extension out of the box. This prepares you to easily roll out AI features (like semantic search through form responses) in the future.
4. **CI/CD:** Zero manual builds. Every push to your GitHub `main` branch automatically builds the Docker images and pushes them to the GitHub Container Registry (GHCR), ensuring your clients always download the freshest release.

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
| `10-app.ini` | All | Base config: timezone, memory limit, upload limits, OPcache |
| `20-app.dev.ini` | Dev only | OPcache disabled, error display, Xdebug settings |
| `20-app.prod.ini` | Prod only | Errors hidden, OPcache optimized, Symfony preloading |

### Xdebug

Xdebug is installed only in the `dev` Docker stage (not in production). Configured via `docker/api/conf.d/20-app.dev.ini` and `compose.override.yml` (`XDEBUG_MODE=debug`).

**PhpStorm setup**:
1. Settings → PHP → Servers: add server named `sentinel`, host `api.sentinel.localhost`, port `80`, Debugger `Xdebug`. Enable path mappings: map project's `app/api` → `/app`
2. Settings → PHP → Debug: ensure Xdebug port is `9003`
3. Run → Start Listening for PHP Debug Connections

## Domain Model Conventions

### Multi-tenancy

All data is scoped to a **Workspace**. Every query and mutation must respect workspace isolation.

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

## Coding Conventions

### PHP (Backend)

- All PHP files start with `declare(strict_types=1);`
- Entities use UUID primary keys via Symfony UID + `doctrine.uuid_generator`:
  ```php
  #[ORM\Column(type: UuidType::NAME, unique: true)]
  #[ORM\GeneratedValue(strategy: 'CUSTOM')]
  #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
  private ?Uuid $id = null;
  ```
- All entities use `TimestampableTrait` for `createdAt`/`updatedAt`
- Serialization is controlled via `#[Groups(['entity:read', 'entity:write'])]` on entity properties
- Controllers use `#[MapRequestPayload]` to bind and validate request bodies into DTOs; DTOs use Symfony Assert constraints
- Controllers use `#[CurrentUser]` attribute to inject the authenticated user
- Controllers return `$this->json($data, context: ['groups' => ['entity:read']])` — never raw arrays
- PHPStan level 6 is enforced; avoid `mixed` types and unsafe dynamic access

### PHPUnit Tests

API tests extend `Symfony\Bundle\FrameworkBundle\Test\WebTestCase` and create all test data in-memory via helper methods (no fixtures):
```php
$client = static::createClient();
$user = $this->createActiveUser('unique-prefix-' . uniqid());
$workspace = $this->createWorkspaceForUser($user, 'WS Name');
$client->loginUser($user);
$client->request('GET', '/api/workspaces/' . $workspace->getId() . '/forms', [], [], [
    'HTTP_ACCEPT' => 'application/json',
]);
$this->assertResponseIsSuccessful();
```

### TypeScript (Frontend)

- Shared types live in `packages/types/` (currently a placeholder — define types there when adding cross-package contracts)
- App mode is consumed via `useModeContext()` hook from `ModeContext`; components should branch on `AppMode: 'saas' | 'self_hosted'`
- Toast notifications use the `useToast()` hook from `components/toast/`
- All new pages go in `pages/` and must be lazy-loaded via `React.lazy()` in `App.tsx`

## MCP Servers

### Playwright (browser automation & testing)

```
npx @playwright/mcp@latest
```

Configure in PhpStorm: Settings → Tools → AI Assistant → MCP Servers.