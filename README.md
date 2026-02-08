# Sentinel

Multi-tenant SaaS platform for visual form building with workflow automation.

## Tech Stack

| Layer          | Technology                                                        |
|----------------|-------------------------------------------------------------------|
| **Backend**    | PHP 8.4, Symfony 8, API Platform, Doctrine ORM, Messenger         |
| **Frontend**   | React 19, TypeScript, Vite, Tailwind CSS v4                       |
| **Database**   | PostgreSQL 16 (pgvector), Redis 7 (cache + queue)                 |
| **Infra**      | Docker Compose, FrankenPHP, Caddy, Mercure (WebSockets)           |
| **Quality**    | GrumPHP, PHPStan, PHP-CS-Fixer, ESLint, Prettier                 |

## Project Structure

```
├── app/
│   ├── api/              # Symfony API (FrankenPHP)
│   └── web/              # React SPA (Vite)
├── docker/
│   ├── api/              # API Dockerfile, Caddyfile, PHP config
│   └── caddy/            # Gateway Caddyfile, mkcert certs
├── packages/
│   └── types/            # Shared TypeScript types
├── compose.yml           # Base Docker Compose
├── compose.override.yml  # Dev overrides (Xdebug, bind mounts, pgAdmin, Mailpit)
├── Makefile              # All dev commands
├── grumphp.yml           # Pre-commit hook config
├── phpstan.neon          # Static analysis config
└── .php-cs-fixer.dist.php # Code style config
```

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) & Docker Compose
- [mkcert](https://github.com/FiloSottile/mkcert) (for local HTTPS)
- [Composer](https://getcomposer.org/) (for host-level dev tooling)
- [Node.js](https://nodejs.org/) 20+ (for host-level linting)

## Getting Started

### 1. Generate local SSL certificates

```bash
mkcert -install

mkdir -p docker/caddy/certs
cd docker/caddy/certs
mkcert sentinel.localhost "*.sentinel.localhost"
cd ../../..
```

### 2. Install and start

```bash
cp .env.example .env
make install
```

### 3. Install code quality tools (host)

```bash
composer install
cd app/web && npm install && cd ../..
```

### 4. Open in browser

| Service   | URL                                          |
|-----------|----------------------------------------------|
| Web App   | https://sentinel.localhost                    |
| API       | https://api.sentinel.localhost                |
| API Docs  | https://api.sentinel.localhost/api            |
| Mercure   | https://api.sentinel.localhost/.well-known/mercure |
| pgAdmin   | http://localhost:5050                         |
| Mailpit   | http://localhost:8025                         |

## Make Commands

Run `make help` to see all available commands.

### General

| Command          | Description                          |
|------------------|--------------------------------------|
| `make install`   | First-time setup (build + start)     |
| `make start`     | Start all services                   |
| `make stop`      | Stop all services                    |
| `make restart`   | Restart all services                 |
| `make logs`      | Tail all logs                        |
| `make clean`     | Remove all data (⚠️ destructive)     |

### API (Symfony)

| Command                         | Description                    |
|---------------------------------|--------------------------------|
| `make api-shell`                | Shell into API container       |
| `make api-test`                 | Run PHPUnit tests              |
| `make api-console <cmd>`        | Run Symfony console command    |
| `make db-migrate`               | Run Doctrine migrations        |
| `make db-reset`                 | Drop + recreate + migrate      |

### Worker (Messenger)

| Command                | Description                    |
|------------------------|--------------------------------|
| `make logs-worker`     | Tail worker logs               |
| `make worker-restart`  | Restart the worker             |
| `make worker-failed`   | List failed messages           |
| `make worker-retry`    | Retry all failed messages      |

### Web (React)

| Command            | Description                    |
|--------------------|--------------------------------|
| `make web-shell`   | Shell into web container       |
| `make web-test`    | Run frontend tests             |
| `make web-build`   | Production build               |

### Code Quality

| Command      | Description                                       |
|--------------|---------------------------------------------------|
| `make lint`  | Run all linters (dry-run)                         |
| `make fix`   | Auto-fix code style                               |

GrumPHP runs PHPStan, PHP-CS-Fixer, and ESLint automatically on every `git commit`.

## HTTPS / SSL

### Local Development

Uses [mkcert](https://github.com/FiloSottile/mkcert) for locally-trusted certificates. The `compose.override.yml` mounts a dev-specific Caddyfile with `tls` directives pointing to the generated certs.

Regenerate if expired: `cd docker/caddy/certs && mkcert sentinel.localhost "*.sentinel.localhost"`, then `docker compose restart caddy`.

### Production

Set the `DOMAIN` environment variable — Caddy automatically obtains and renews certificates via Let's Encrypt:

```env
DOMAIN=myapp.com
```

**Requirements**: ports 80/443 publicly accessible, DNS A records for `myapp.com` and `api.myapp.com` pointing to the server.

## Xdebug (PhpStorm)

Xdebug is enabled in the dev Docker stage only. To connect PhpStorm:

1. **Settings → PHP → Servers**: add server named `sentinel`, host `api.sentinel.localhost`, port `80`. Enable path mappings: `app/api` → `/app`
2. **Settings → PHP → Debug**: Xdebug port `9003`
3. **Run → Start Listening for PHP Debug Connections**

## Environment Variables

Configuration is split between two `.env` files:

- **Root `.env`** — Docker Compose variables (database credentials, domain, ports)
- **`app/api/.env`** — Symfony defaults (DATABASE_URL, MESSENGER_TRANSPORT_DSN, CORS)

Docker environment variables (from `compose.yml`) override Symfony defaults at runtime.

## License

Proprietary — All rights reserved.
