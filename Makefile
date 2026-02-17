# Makefile

.DEFAULT_GOAL := help

.PHONY: help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: install
install: ## Install project
	@echo "🚀 Installing FormBuilder..."
	cp -n .env.example .env || true
	docker compose build --no-cache
	docker compose up -d
	$(MAKE) api-install
	$(MAKE) web-install
	@echo "✅ Installation complete!"
	@echo "🌐 Web: https://sentinel.localhost"
	@echo "🔌 API: https://api.sentinel.localhost"
	@echo ""
	@echo "💡 Run 'make ide-sync' to sync node_modules for IDE autocompletion"

.PHONY: start
start: ## Start all services
	docker compose up -d

.PHONY: stop
stop: ## Stop all services
	docker compose down

.PHONY: restart
restart: stop start ## Restart all services

.PHONY: logs
logs: ## View logs
	docker compose logs -f

.PHONY: logs-api
logs-api: ## View API logs
	docker compose logs -f api

.PHONY: logs-web
logs-web: ## View Web logs
	docker compose logs -f web

.PHONY: logs-worker
logs-worker: ## View Worker logs
	docker compose logs -f worker

# === API Commands ===

.PHONY: api-install
api-install: ## Install API dependencies
	docker compose exec api composer install

.PHONY: api-shell
api-shell: ## API shell
	docker compose exec api bash

.PHONY: api-test
api-test: ## Run API tests
	docker compose exec -e XDEBUG_MODE=off -e APP_ENV=test api php bin/phpunit

.PHONY: api-console
api-console: ## Symfony console
	docker compose exec api php bin/console $(filter-out $@,$(MAKECMDGOALS))

.PHONY: db-migrate
db-migrate: ## Run migrations
	docker compose exec api php bin/console doctrine:migrations:migrate --no-interaction

.PHONY: db-reset
db-reset: ## Reset database (⚠️ DESTRUCTIVE)
	docker compose exec api php bin/console doctrine:database:drop --force --if-exists
	docker compose exec api php bin/console doctrine:database:create
	$(MAKE) db-migrate

# === Worker Commands ===

.PHONY: worker-restart
worker-restart: ## Restart the worker
	docker compose restart worker

.PHONY: worker-failed
worker-failed: ## List failed messages
	docker compose exec worker php bin/console messenger:failed:show

.PHONY: worker-retry
worker-retry: ## Retry all failed messages
	docker compose exec worker php bin/console messenger:failed:retry

# === Web Commands ===

.PHONY: web-install
web-install: ## Install Web dependencies
	docker compose exec web npm install

.PHONY: web-shell
web-shell: ## Web shell
	docker compose exec web sh

.PHONY: web-test
web-test: ## Run Web tests
	docker compose exec web npm test

.PHONY: web-build
web-build: ## Build Web for production
	docker compose exec web npm run build

# === Cleanup ===

.PHONY: clean
clean: ## Clean all data (⚠️ DESTRUCTIVE)
	docker compose down -v
	rm -rf app/api/var/cache/*
	rm -rf app/web/dist

# === Code Quality ===

.PHONY: lint
lint: ## Run all linters (PHP-CS-Fixer, PHPStan, ESLint, TypeScript)
	docker compose run --rm -T tools php vendor/bin/php-cs-fixer fix --dry-run --diff
	docker compose run --rm -T tools php vendor/bin/phpstan analyse --no-progress --memory-limit=256M
	docker compose exec -T web npx eslint src --quiet
	docker compose exec -T web npx tsc --noEmit

.PHONY: fix
fix: ## Auto-fix code style (PHP-CS-Fixer, ESLint)
	docker compose run --rm -T tools php vendor/bin/php-cs-fixer fix
	docker compose exec -T web npx eslint src --fix

# === IDE Support ===

.PHONY: ide-sync
ide-sync: ## Sync node_modules from container to host (for IDE autocompletion)
	@echo "📦 Syncing node_modules from container..."
	docker compose cp web:/app/node_modules app/web/
	@echo "✅ node_modules synced to app/web/node_modules"

%:
	@: