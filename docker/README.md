# Docker Setup Guide

This guide explains how to set up and run the DocuSign Signing API using Docker.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Services](#services)
4. [Common Commands](#common-commands)
5. [Configuration](#configuration)
6. [Troubleshooting](#troubleshooting)
7. [Production Deployment](#production-deployment)

---

## Prerequisites

- **Docker**: Version 20.10 or higher
- **Docker Compose**: Version 2.0 or higher
- **Make** (optional): For simplified commands

### Installation

#### macOS
```bash
brew install docker docker-compose
```

#### Ubuntu/Debian
```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
# Log out and back in
```

#### Windows
Download and install [Docker Desktop](https://www.docker.com/products/docker-desktop)

---

## Quick Start

### 1. Clone and Configure

```bash
# Clone the repository
git clone <repository-url>
cd signing

# Copy Docker environment file
cp .env.docker .env

# Generate application key
docker-compose run --rm app php artisan key:generate
```

### 2. Build and Start Services

```bash
# Build Docker images
docker-compose build

# Start all services
docker-compose up -d

# Check service status
docker-compose ps
```

### 3. Initialize Application

```bash
# Generate OAuth keys
docker-compose exec app php artisan passport:keys

# Run database migrations
docker-compose exec app php artisan migrate

# Seed database (optional)
docker-compose exec app php artisan db:seed
```

### 4. Access Services

- **API**: http://localhost:8000
- **Horizon Dashboard**: http://localhost:8000/horizon
- **Mailpit UI**: http://localhost:8025
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379

---

## Services

### Application Services

#### app
- **Description**: PHP-FPM application server
- **Image**: Custom (built from Dockerfile)
- **Port**: 9000 (internal)
- **Volumes**: Project directory mounted

#### nginx
- **Description**: Web server
- **Image**: nginx:1.25-alpine
- **Port**: 8000 → 80
- **Configuration**: `docker/nginx/`

#### postgres
- **Description**: PostgreSQL database
- **Image**: postgres:16-alpine
- **Port**: 5432
- **Data**: Persistent volume `postgres-data`
- **Health Check**: Automatic

#### redis
- **Description**: Cache and queue store
- **Image**: redis:7-alpine
- **Port**: 6379
- **Data**: Persistent volume `redis-data`
- **Health Check**: Automatic

#### horizon
- **Description**: Laravel Horizon queue worker
- **Image**: Custom (built from Dockerfile)
- **Configuration**: `config/horizon.php`

#### scheduler
- **Description**: Laravel task scheduler (cron)
- **Image**: Custom (built from Dockerfile)
- **Schedule**: Every minute

#### mailpit
- **Description**: Email testing tool (development only)
- **Image**: axllent/mailpit
- **SMTP Port**: 1025
- **Web UI**: http://localhost:8025

---

## Common Commands

### Service Management

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart a specific service
docker-compose restart app

# View logs
docker-compose logs -f app

# View all logs
docker-compose logs -f
```

### Application Commands

```bash
# Run artisan commands
docker-compose exec app php artisan <command>

# Run composer
docker-compose exec app composer <command>

# Run PHPUnit tests
docker-compose exec app php artisan test

# Access app container shell
docker-compose exec app sh

# Access database
docker-compose exec postgres psql -U postgres signing_api
```

### Database Management

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Rollback migrations
docker-compose exec app php artisan migrate:rollback

# Seed database
docker-compose exec app php artisan db:seed

# Fresh migration with seed
docker-compose exec app php artisan migrate:fresh --seed

# Database backup
docker-compose exec postgres pg_dump -U postgres signing_api > backup.sql

# Database restore
docker-compose exec -T postgres psql -U postgres signing_api < backup.sql
```

### Cache Management

```bash
# Clear all caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Optimize for production
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### Queue Management

```bash
# Monitor Horizon
docker-compose exec app php artisan horizon

# Restart Horizon
docker-compose restart horizon

# Clear failed jobs
docker-compose exec app php artisan queue:flush

# Retry failed jobs
docker-compose exec app php artisan queue:retry all
```

---

## Configuration

### Environment Variables

Key environment variables for Docker setup:

```env
# Service Hosts (use Docker service names)
DB_HOST=postgres
REDIS_HOST=redis
MAIL_HOST=mailpit

# Ports (exposed to host)
APP_PORT=8000
DB_PORT=5432
REDIS_PORT=6379

# Database
DB_DATABASE=signing_api
DB_USERNAME=postgres
DB_PASSWORD=secret

# Redis
REDIS_PASSWORD=null
```

### Custom Configuration

#### Change Exposed Ports

Edit `.env`:
```env
APP_PORT=9000
DB_PORT=54320
REDIS_PORT=63790
```

Then restart:
```bash
docker-compose down
docker-compose up -d
```

#### Customize PHP Settings

Edit `docker/php/local.ini`:
```ini
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 512M
```

Restart app service:
```bash
docker-compose restart app
```

#### Customize Nginx

Edit `docker/nginx/conf.d/default.conf` and restart:
```bash
docker-compose restart nginx
```

---

## Troubleshooting

### Port Already in Use

```bash
# Check what's using the port
lsof -i :8000

# Change port in .env
APP_PORT=9000
docker-compose up -d
```

### Permission Issues

```bash
# Fix permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues

```bash
# Check PostgreSQL is running
docker-compose ps postgres

# Check PostgreSQL logs
docker-compose logs postgres

# Test connection
docker-compose exec app php artisan migrate:status
```

### Redis Connection Issues

```bash
# Check Redis is running
docker-compose ps redis

# Test Redis connection
docker-compose exec redis redis-cli ping
```

### Container Won't Start

```bash
# View detailed logs
docker-compose logs app

# Rebuild image
docker-compose build --no-cache app
docker-compose up -d app
```

### Clear Everything and Start Fresh

```bash
# Stop and remove containers, networks, volumes
docker-compose down -v

# Remove dangling images
docker image prune

# Rebuild and start
docker-compose build
docker-compose up -d

# Reinitialize
docker-compose exec app php artisan migrate:fresh --seed
```

---

## Production Deployment

### 1. Prepare Environment

```bash
# Copy production environment file
cp .env.production.example .env

# Edit .env with production values
nano .env

# Generate application key
docker-compose run --rm app php artisan key:generate
```

### 2. Build Production Images

```bash
# Build optimized production images
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
```

### 3. Deploy

```bash
# Start production services
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Cache configuration
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

### 4. Security Checklist

- [ ] `APP_DEBUG=false`
- [ ] Strong `APP_KEY` generated
- [ ] Secure database password set
- [ ] Redis password set
- [ ] SSL/TLS certificates configured
- [ ] Firewall rules configured
- [ ] Database ports not exposed externally
- [ ] Redis ports not exposed externally
- [ ] Regular backups configured
- [ ] Log monitoring enabled
- [ ] Error tracking (Sentry) configured

### 5. Monitoring

```bash
# Monitor resource usage
docker stats

# View logs
docker-compose logs -f --tail=100

# Check health
docker-compose ps
```

---

## Docker Compose Profiles

### Development (Default)
```bash
docker-compose up -d
```

Includes all services including Mailpit.

### Production
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Optimized for production, excludes development tools.

### Testing
```bash
# Run tests
docker-compose exec app php artisan test

# Run tests with coverage
docker-compose exec app php artisan test --coverage
```

---

## Backup and Restore

### Automated Backup Script

```bash
#!/bin/bash
# docker/scripts/backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=./backups

mkdir -p $BACKUP_DIR

# Backup database
docker-compose exec -T postgres pg_dump -U postgres signing_api \
  | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup storage files
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz storage/app

echo "Backup completed: $BACKUP_DIR"
```

### Restore from Backup

```bash
#!/bin/bash
# docker/scripts/restore.sh

BACKUP_FILE=$1

if [ -z "$BACKUP_FILE" ]; then
  echo "Usage: ./restore.sh <backup-file.sql.gz>"
  exit 1
fi

# Restore database
gunzip < $BACKUP_FILE | docker-compose exec -T postgres psql -U postgres signing_api

echo "Restore completed"
```

---

## Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Docker Best Practices](https://laravel.com/docs/deployment)
- [PHP Docker Official Images](https://hub.docker.com/_/php)
