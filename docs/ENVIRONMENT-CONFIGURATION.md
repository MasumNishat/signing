# Environment Configuration Guide

## Overview

This document describes the environment configuration files and their usage across different deployment environments.

## Environment Files

### 1. `.env.example` - Development Template
**Purpose:** Base template for local development
**Usage:** Copy to `.env` for local development

**Key Settings:**
- `APP_ENV=local`
- `APP_DEBUG=true`
- Local PostgreSQL database
- Local Redis cache/queue
- File-based document storage
- Detailed logging (debug level)
- Relaxed security settings for development

**Setup:**
```bash
cp .env.example .env
php artisan key:generate
```

---

### 2. `.env.docker` - Docker Development
**Purpose:** Optimized for Docker Compose setup
**Usage:** Used automatically by Docker Compose

**Key Settings:**
- Uses Docker service names (postgres, redis, mailpit)
- Pre-configured for Docker networking
- Mailpit for email testing (accessible at http://localhost:8025)
- Local file storage (volume-mapped)
- Relaxed security for development

**Docker Services:**
- **App:** Laravel application (port 8000)
- **PostgreSQL:** Database (port 5432)
- **Redis:** Cache/Queue (port 6379)
- **Mailpit:** Email testing (ports 1025, 8025)
- **Horizon:** Queue monitoring

**Setup:**
```bash
# Docker Compose will use .env.docker automatically
docker-compose up -d
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

---

### 3. `.env.staging.example` - Staging Environment
**Purpose:** Pre-production testing environment
**Usage:** Copy to `.env` on staging server

**Key Settings:**
- `APP_ENV=staging`
- `APP_DEBUG=true` (for debugging)
- Managed PostgreSQL (staging database)
- Managed Redis (staging instance)
- AWS S3 (staging bucket: `signing-api-documents-staging`)
- AWS SES (staging email)
- HTTPS enforced
- Verbose logging (debug level)
- Test mode flags enabled
- Sentry error tracking (100% sample rate)
- Stripe test mode

**Differences from Production:**
- More verbose logging
- Relaxed CORS for localhost testing
- Test mode flags enabled
- Higher Sentry sample rate (1.0 vs 0.1)
- Debug mode enabled
- Allows test users

**Setup:**
```bash
cp .env.staging.example .env
php artisan key:generate
# Configure staging credentials
php artisan migrate
php artisan passport:install
php artisan db:seed
```

---

### 4. `.env.production.example` - Production Environment
**Purpose:** Live production environment
**Usage:** Copy to `.env` on production server

**Key Settings:**
- `APP_ENV=production`
- `APP_DEBUG=false` (security)
- Managed PostgreSQL (production database with SSL)
- Managed Redis (production cluster)
- AWS S3 (production bucket: `signing-api-documents-prod`)
- AWS SES (production email)
- HTTPS enforced (strict)
- Minimal logging (error level only)
- Strict security settings
- Sentry error tracking (10% sample rate)
- New Relic monitoring
- Stripe live mode

**Security Hardening:**
- `APP_DEBUG=false` - Never show stack traces
- `FORCE_HTTPS=true` - Always use HTTPS
- `SESSION_SECURE_COOKIE=true` - HTTPS-only cookies
- `SESSION_SAME_SITE=strict` - Strict same-site policy
- `DB_SSLMODE=require` - Database SSL required
- Limited CORS origins
- Error-level logging only

**Setup:**
```bash
cp .env.production.example .env
php artisan key:generate
# Configure production credentials securely
php artisan migrate --force
php artisan passport:install --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Environment Comparison Matrix

| Setting | Development | Docker | Staging | Production |
|---------|------------|--------|---------|-----------|
| **APP_ENV** | local | local | staging | production |
| **APP_DEBUG** | true | true | true | **false** |
| **Database** | Local PG | Docker PG | Managed PG | Managed PG |
| **Redis** | Local | Docker | Managed | Managed Cluster |
| **Storage** | local | local | S3 (staging) | S3 (prod) |
| **Email** | log | Mailpit | SES (staging) | SES (prod) |
| **HTTPS** | false | false | true | **true (strict)** |
| **Logging** | debug | debug | debug | **error only** |
| **CORS** | * | * | Relaxed | **Strict** |
| **Monitoring** | - | - | Optional | **Required** |
| **Error Tracking** | - | - | Sentry (100%) | Sentry (10%) |

---

## Configuration Sections

### Application Settings
```bash
APP_NAME="DocuSign Signing API"
APP_ENV=local|staging|production
APP_KEY=                          # Generate with: php artisan key:generate
APP_DEBUG=true|false
APP_URL=https://api.yourdomain.com
APP_TIMEZONE=UTC
```

### Database Configuration
```bash
DB_CONNECTION=pgsql
DB_HOST=localhost|postgres|managed-server.com
DB_PORT=5432
DB_DATABASE=signing_api
DB_USERNAME=postgres
DB_PASSWORD=                      # NEVER commit passwords
DB_SCHEMA=public
DB_SSLMODE=disable|require       # require for production
DB_CONNECTION_POOL_SIZE=10|20    # Higher for production
DB_IDLE_TIMEOUT=60
```

### Cache & Queue Configuration
```bash
CACHE_STORE=redis
CACHE_PREFIX=signing_api_prod_   # Unique per environment
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
QUEUE_PREFIX=signing_api_prod_   # Unique per environment
```

### Redis Configuration
```bash
REDIS_CLIENT=phpredis             # Faster than predis
REDIS_HOST=localhost|redis|managed-server.com
REDIS_PASSWORD=                   # Empty for local, required for managed
REDIS_PORT=6379
REDIS_DB=0                        # Default database
REDIS_CACHE_DB=1                  # Separate DB for cache
REDIS_QUEUE_DB=2                  # Separate DB for queues
REDIS_SESSION_DB=3                # Separate DB for sessions
```

### Filesystem Configuration
```bash
FILESYSTEM_DISK=local|s3
AWS_ACCESS_KEY_ID=                # For S3 access
AWS_SECRET_ACCESS_KEY=            # NEVER commit
AWS_BUCKET=signing-api-documents-prod
AWS_DEFAULT_REGION=us-east-1
```

### Mail Configuration
```bash
MAIL_MAILER=log|smtp|ses
MAIL_HOST=localhost|mailpit|smtp.server.com
MAIL_PORT=1025|587
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### OAuth & API Configuration
```bash
PASSPORT_ACCESS_TOKEN_LIFETIME=60              # minutes (1 hour)
PASSPORT_REFRESH_TOKEN_LIFETIME=20160          # minutes (14 days)
PASSPORT_PERSONAL_ACCESS_TOKEN_LIFETIME=262800 # minutes (6 months)
API_VERSION=v2.1
API_RATE_LIMIT_AUTHENTICATED=1000              # per hour
API_RATE_LIMIT_UNAUTHENTICATED=100             # per hour
```

### Horizon Configuration
```bash
HORIZON_PATH=horizon
HORIZON_MEMORY_LIMIT=128|256     # MB, higher for production
HORIZON_BALANCE=auto
HORIZON_PROCESSES=3|5            # More for production
HORIZON_TRIES=3
HORIZON_TIMEOUT=300              # seconds
```

### Security Configuration
```bash
FORCE_HTTPS=false|true           # true for staging/production
SESSION_SECURE_COOKIE=false|true # true for staging/production
SESSION_HTTP_ONLY=true           # Always true
SESSION_SAME_SITE=lax|strict     # strict for production
CORS_ALLOWED_ORIGINS=*|https://app.yourdomain.com
```

### Logging Configuration
```bash
LOG_CHANNEL=stack
LOG_STACK=single|daily,slack     # Production uses multiple
LOG_LEVEL=debug|error            # error for production
LOG_SLACK_WEBHOOK_URL=           # For production alerts
```

### Third-Party Services
```bash
# Error Tracking (Sentry)
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1|1.0

# Monitoring (New Relic)
NEW_RELIC_ENABLED=true|false
NEW_RELIC_APP_NAME="DocuSign Signing API - Production"
NEW_RELIC_LICENSE_KEY=

# Billing (Stripe)
STRIPE_KEY=                      # pk_test_* for staging, pk_live_* for prod
STRIPE_SECRET=                   # sk_test_* for staging, sk_live_* for prod
STRIPE_WEBHOOK_SECRET=           # Different per environment
```

### Document Processing
```bash
MAX_DOCUMENT_SIZE=25000000       # 25MB
MAX_DOCUMENTS_PER_ENVELOPE=50
ALLOWED_DOCUMENT_TYPES=pdf,doc,docx
```

---

## Best Practices

### 1. Never Commit Credentials
```bash
# .gitignore already includes:
.env
.env.local
.env.staging
.env.production
```

### 2. Use Environment-Specific Prefixes
Always use unique cache/queue prefixes per environment:
- Development: `signing_api_dev_`
- Staging: `signing_api_staging_`
- Production: `signing_api_prod_`

### 3. Secure Production Configuration
- Always set `APP_DEBUG=false` in production
- Enable `FORCE_HTTPS=true`
- Use `SESSION_SECURE_COOKIE=true`
- Set `SESSION_SAME_SITE=strict`
- Require SSL for database: `DB_SSLMODE=require`
- Limit CORS to specific origins
- Use error-level logging only

### 4. Cache Configuration in Production
```bash
# After deployment, always cache config:
php artisan config:cache
php artisan route:cache
php artisan view:cache

# To clear:
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 5. Separate Credentials by Environment
Use different credentials for each environment:
- Database users/passwords
- AWS access keys
- Stripe keys (test vs live)
- OAuth secrets
- API tokens

### 6. Environment Variable Validation
Laravel validates required variables on boot. Missing variables will cause errors.

---

## Deployment Checklist

### Local Development
- [ ] Copy `.env.example` to `.env`
- [ ] Generate app key: `php artisan key:generate`
- [ ] Configure local PostgreSQL
- [ ] Configure local Redis
- [ ] Run migrations: `php artisan migrate`
- [ ] Seed database: `php artisan db:seed`
- [ ] Install Passport: `php artisan passport:install`

### Docker Development
- [ ] Ensure `.env.docker` is present
- [ ] Start containers: `docker-compose up -d`
- [ ] Generate app key: `docker-compose exec app php artisan key:generate`
- [ ] Run migrations: `docker-compose exec app php artisan migrate`
- [ ] Seed database: `docker-compose exec app php artisan db:seed`
- [ ] Install Passport: `docker-compose exec app php artisan passport:install`

### Staging Deployment
- [ ] Copy `.env.staging.example` to `.env`
- [ ] Generate app key: `php artisan key:generate`
- [ ] Configure staging database credentials
- [ ] Configure staging Redis credentials
- [ ] Configure AWS S3 staging bucket
- [ ] Configure AWS SES staging credentials
- [ ] Run migrations: `php artisan migrate`
- [ ] Seed database: `php artisan db:seed`
- [ ] Install Passport: `php artisan passport:install`
- [ ] Test all endpoints
- [ ] Verify Sentry integration
- [ ] Verify email delivery

### Production Deployment
- [ ] Copy `.env.production.example` to `.env`
- [ ] Generate app key: `php artisan key:generate`
- [ ] Configure production database credentials (with SSL)
- [ ] Configure production Redis credentials
- [ ] Configure AWS S3 production bucket
- [ ] Configure AWS SES production credentials
- [ ] Configure Stripe live keys
- [ ] Configure New Relic
- [ ] Configure Sentry
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Install Passport: `php artisan passport:install --force`
- [ ] Cache config: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`
- [ ] Cache views: `php artisan view:cache`
- [ ] Test health endpoints
- [ ] Monitor error logs
- [ ] Verify monitoring dashboards

---

## Troubleshooting

### Issue: "No application encryption key has been specified"
**Solution:**
```bash
php artisan key:generate
```

### Issue: Database connection failed
**Check:**
- Database credentials in `.env`
- Database server is running
- Firewall/security group allows connections
- SSL mode matches server configuration

### Issue: Redis connection failed
**Check:**
- Redis server is running
- Redis host/port in `.env`
- Redis password (if required)
- Firewall/security group allows connections

### Issue: S3 upload failed
**Check:**
- AWS credentials in `.env`
- Bucket exists and is accessible
- IAM permissions allow s3:PutObject
- Region is correct

### Issue: Emails not sending
**Check:**
- Mail configuration in `.env`
- AWS SES credentials (for staging/production)
- Mailpit is running (for Docker)
- Email addresses are verified in SES (for staging)

---

## Security Notes

### Credential Management
- **NEVER** commit `.env` files to version control
- Use secure credential storage (AWS Secrets Manager, HashiCorp Vault, etc.)
- Rotate credentials regularly
- Use different credentials per environment

### Production Hardening
- Disable debug mode: `APP_DEBUG=false`
- Enable HTTPS: `FORCE_HTTPS=true`
- Secure cookies: `SESSION_SECURE_COOKIE=true`
- Strict same-site: `SESSION_SAME_SITE=strict`
- Database SSL: `DB_SSLMODE=require`
- Limit CORS origins
- Error-level logging only

### Monitoring
- Enable error tracking (Sentry)
- Enable APM (New Relic)
- Monitor Horizon queue dashboard
- Set up log aggregation
- Configure Slack alerts for critical errors

---

**Last Updated:** 2025-11-14
**Document Version:** 1.0
