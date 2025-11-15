# ============================================================================
# Makefile for DocuSign Signing API
# ============================================================================
# Usage:
#   make help         - Show available commands
#   make setup        - Initial project setup
#   make start        - Start all services
#   make stop         - Stop all services
# ============================================================================

.PHONY: help setup build start stop restart logs shell test

# Default target
.DEFAULT_GOAL := help

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
RED := \033[0;31m
YELLOW := \033[0;33m
NC := \033[0m # No Color

## help: Show this help message
help:
	@echo "$(BLUE)DocuSign Signing API - Available Commands$(NC)"
	@echo ""
	@grep -E '^## .*:' Makefile | sed 's/## //' | column -t -s ':'

## ============================================================================
## Setup Commands
## ============================================================================

## setup: Initial project setup (run once)
setup:
	@echo "$(GREEN)Setting up project...$(NC)"
	@cp .env.docker .env
	@docker-compose build
	@docker-compose up -d
	@docker-compose exec app php artisan key:generate
	@docker-compose exec app php artisan passport:keys
	@docker-compose exec app php artisan migrate
	@docker-compose exec app php artisan db:seed
	@echo "$(GREEN)Setup complete!$(NC)"
	@echo "API: http://localhost:8000"
	@echo "Mailpit: http://localhost:8025"

## build: Build Docker images
build:
	@echo "$(GREEN)Building Docker images...$(NC)"
	@docker-compose build

## rebuild: Rebuild Docker images (no cache)
rebuild:
	@echo "$(GREEN)Rebuilding Docker images...$(NC)"
	@docker-compose build --no-cache

## ============================================================================
## Service Management
## ============================================================================

## start: Start all services
start:
	@echo "$(GREEN)Starting services...$(NC)"
	@docker-compose up -d
	@docker-compose ps

## stop: Stop all services
stop:
	@echo "$(YELLOW)Stopping services...$(NC)"
	@docker-compose down

## restart: Restart all services
restart:
	@echo "$(YELLOW)Restarting services...$(NC)"
	@docker-compose restart

## ps: Show service status
ps:
	@docker-compose ps

## logs: Show logs (use SERVICE=app for specific service)
logs:
	@docker-compose logs -f $(SERVICE)

## ============================================================================
## Application Commands
## ============================================================================

## shell: Access app container shell
shell:
	@docker-compose exec app sh

## tinker: Open Laravel Tinker
tinker:
	@docker-compose exec app php artisan tinker

## cache-clear: Clear all caches
cache-clear:
	@echo "$(YELLOW)Clearing caches...$(NC)"
	@docker-compose exec app php artisan cache:clear
	@docker-compose exec app php artisan config:clear
	@docker-compose exec app php artisan route:clear
	@docker-compose exec app php artisan view:clear
	@echo "$(GREEN)Caches cleared!$(NC)"

## cache: Cache configuration for production
cache:
	@echo "$(GREEN)Caching configuration...$(NC)"
	@docker-compose exec app php artisan config:cache
	@docker-compose exec app php artisan route:cache
	@docker-compose exec app php artisan view:cache
	@echo "$(GREEN)Configuration cached!$(NC)"

## ============================================================================
## Database Commands
## ============================================================================

## migrate: Run database migrations
migrate:
	@echo "$(GREEN)Running migrations...$(NC)"
	@docker-compose exec app php artisan migrate

## migrate-fresh: Fresh migration with seed
migrate-fresh:
	@echo "$(RED)⚠️  This will drop all tables!$(NC)"
	@docker-compose exec app php artisan migrate:fresh --seed

## migrate-rollback: Rollback last migration
migrate-rollback:
	@docker-compose exec app php artisan migrate:rollback

## seed: Seed database
seed:
	@docker-compose exec app php artisan db:seed

## db-shell: Access PostgreSQL shell
db-shell:
	@docker-compose exec postgres psql -U postgres signing_api

## db-backup: Backup database
db-backup:
	@mkdir -p backups
	@docker-compose exec -T postgres pg_dump -U postgres signing_api > backups/backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)Database backed up to backups/$(NC)"

## ============================================================================
## Testing Commands
## ============================================================================

## test: Run all tests
test:
	@echo "$(GREEN)Running tests...$(NC)"
	@docker-compose exec app php artisan test

## test-unit: Run unit tests only
test-unit:
	@docker-compose exec app php artisan test --testsuite=Unit

## test-feature: Run feature tests only
test-feature:
	@docker-compose exec app php artisan test --testsuite=Feature

## test-coverage: Run tests with coverage
test-coverage:
	@docker-compose exec app php artisan test --coverage

## ============================================================================
## Queue & Horizon Commands
## ============================================================================

## horizon: Start Horizon
horizon:
	@docker-compose exec app php artisan horizon

## horizon-restart: Restart Horizon workers
horizon-restart:
	@docker-compose restart horizon
	@echo "$(GREEN)Horizon restarted!$(NC)"

## queue-work: Start queue worker
queue-work:
	@docker-compose exec app php artisan queue:work

## queue-flush: Clear failed jobs
queue-flush:
	@docker-compose exec app php artisan queue:flush

## queue-retry: Retry all failed jobs
queue-retry:
	@docker-compose exec app php artisan queue:retry all

## ============================================================================
## Development Commands
## ============================================================================

## install: Install composer dependencies
install:
	@docker-compose exec app composer install

## update: Update composer dependencies
update:
	@docker-compose exec app composer update

## fresh: Fresh install (clear everything and start over)
fresh:
	@echo "$(RED)⚠️  This will delete all data!$(NC)"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		echo ""; \
		docker-compose down -v; \
		docker-compose build; \
		docker-compose up -d; \
		docker-compose exec app php artisan key:generate; \
		docker-compose exec app php artisan passport:keys; \
		docker-compose exec app php artisan migrate:fresh --seed; \
		echo "$(GREEN)Fresh install complete!$(NC)"; \
	fi

## fix-permissions: Fix file permissions
fix-permissions:
	@echo "$(GREEN)Fixing permissions...$(NC)"
	@docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
	@docker-compose exec app chmod -R 775 storage bootstrap/cache

## ============================================================================
## Production Commands
## ============================================================================

## prod-build: Build production images
prod-build:
	@echo "$(GREEN)Building production images...$(NC)"
	@docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache

## prod-start: Start production services
prod-start:
	@echo "$(GREEN)Starting production services...$(NC)"
	@docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

## prod-stop: Stop production services
prod-stop:
	@docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

## prod-deploy: Deploy to production
prod-deploy:
	@echo "$(GREEN)Deploying to production...$(NC)"
	@docker-compose -f docker-compose.yml -f docker-compose.prod.yml build
	@docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
	@docker-compose exec app php artisan migrate --force
	@docker-compose exec app php artisan config:cache
	@docker-compose exec app php artisan route:cache
	@docker-compose exec app php artisan view:cache
	@echo "$(GREEN)Deployment complete!$(NC)"

## ============================================================================
## Cleanup Commands
## ============================================================================

## clean: Remove stopped containers and dangling images
clean:
	@echo "$(YELLOW)Cleaning up...$(NC)"
	@docker-compose down
	@docker system prune -f
	@echo "$(GREEN)Cleanup complete!$(NC)"

## clean-all: Remove everything including volumes
clean-all:
	@echo "$(RED)⚠️  This will delete all data!$(NC)"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		echo ""; \
		docker-compose down -v; \
		docker system prune -af --volumes; \
		echo "$(GREEN)Everything cleaned!$(NC)"; \
	fi
