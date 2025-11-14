# Environment Variables Documentation

This document provides comprehensive documentation for all environment variables used in the DocuSign Signing API project.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Application Configuration](#application-configuration)
3. [Database Configuration](#database-configuration)
4. [Cache & Session](#cache--session)
5. [Queue Configuration](#queue-configuration)
6. [Mail Configuration](#mail-configuration)
7. [OAuth & API](#oauth--api)
8. [Security](#security)
9. [Document Processing](#document-processing)
10. [Third-Party Services](#third-party-services)
11. [Environment-Specific Files](#environment-specific-files)

---

## Getting Started

### Initial Setup

1. **Copy the appropriate .env.example file:**
   ```bash
   # For local development
   cp .env.example .env

   # For production
   cp .env.production.example .env
   ```

2. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

3. **Generate OAuth keys:**
   ```bash
   php artisan passport:keys
   ```

4. **Configure database and other services** based on your environment

---

## Application Configuration

### APP_NAME
- **Description:** Application name displayed in emails and UI
- **Type:** String
- **Default:** "DocuSign Signing API"
- **Example:** `APP_NAME="My Signing Service"`

### APP_ENV
- **Description:** Application environment
- **Type:** String (enum)
- **Values:** `local`, `development`, `staging`, `production`
- **Default:** `local`
- **Production:** `production`

### APP_KEY
- **Description:** Encryption key for securing data
- **Type:** String (base64 encoded)
- **Required:** Yes
- **Generate:** `php artisan key:generate`

### APP_DEBUG
- **Description:** Enable detailed error pages
- **Type:** Boolean
- **Default:** `true` (local), `false` (production)
- **Warning:** MUST be `false` in production for security

### APP_URL
- **Description:** Base URL of the application
- **Type:** URL
- **Example:** `http://localhost` (local), `https://api.yourdomain.com` (production)
- **Used For:** Generating links in emails, OAuth redirects

### APP_TIMEZONE
- **Description:** Application timezone
- **Type:** String (PHP timezone)
- **Default:** `UTC`
- **Example:** `America/New_York`, `Europe/London`

---

## Database Configuration

### DB_CONNECTION
- **Description:** Database driver
- **Type:** String (enum)
- **Values:** `pgsql` (PostgreSQL), `mysql`, `sqlite`, `sqlsrv`
- **Default:** `pgsql`
- **Recommended:** `pgsql` for production

### PostgreSQL Configuration

#### DB_HOST
- **Description:** Database server hostname
- **Type:** String
- **Default:** `127.0.0.1`
- **Production:** Use managed service hostname

#### DB_PORT
- **Description:** Database server port
- **Type:** Integer
- **Default:** `5432`

#### DB_DATABASE
- **Description:** Database name
- **Type:** String
- **Default:** `signing_api`
- **Recommendation:** Use different names per environment

#### DB_USERNAME / DB_PASSWORD
- **Description:** Database credentials
- **Type:** String
- **Security:** Never commit passwords to version control

#### DB_SCHEMA
- **Description:** PostgreSQL schema name
- **Type:** String
- **Default:** `public`

#### DB_SSLMODE
- **Description:** SSL connection mode
- **Type:** String (enum)
- **Values:** `disable`, `allow`, `prefer`, `require`, `verify-ca`, `verify-full`
- **Default:** `prefer`
- **Production:** `require` or higher

### Connection Pool

#### DB_CONNECTION_POOL_SIZE
- **Description:** Number of persistent connections
- **Type:** Integer
- **Default:** `10`
- **Production:** `20-50` depending on load

#### DB_IDLE_TIMEOUT
- **Description:** Connection idle timeout in seconds
- **Type:** Integer
- **Default:** `60`

---

## Cache & Session

### CACHE_STORE
- **Description:** Cache driver
- **Type:** String (enum)
- **Values:** `file`, `redis`, `memcached`, `database`, `array`
- **Default:** `redis`
- **Production:** `redis` or `memcached`

### CACHE_PREFIX
- **Description:** Cache key prefix (useful for shared instances)
- **Type:** String
- **Default:** None
- **Example:** `signing_api_prod_`

### CACHE_TTL
- **Description:** Default cache time-to-live in seconds
- **Type:** Integer
- **Default:** `3600` (1 hour)

### SESSION_DRIVER
- **Description:** Session storage driver
- **Type:** String (enum)
- **Values:** `file`, `cookie`, `database`, `redis`, `array`
- **Default:** `database`
- **Production:** `redis` for performance

### SESSION_LIFETIME
- **Description:** Session lifetime in minutes
- **Type:** Integer
- **Default:** `120` (2 hours)

### SESSION_ENCRYPT
- **Description:** Encrypt session data
- **Type:** Boolean
- **Default:** `false`
- **Security:** Enable for sensitive applications

---

## Queue Configuration

### QUEUE_CONNECTION
- **Description:** Queue driver
- **Type:** String (enum)
- **Values:** `sync`, `database`, `redis`, `sqs`, `beanstalkd`
- **Default:** `redis`
- **Development:** `sync` for immediate execution
- **Production:** `redis` for performance

### QUEUE_PREFIX
- **Description:** Queue name prefix
- **Type:** String
- **Example:** `signing_api_prod_`

### FAILED_QUEUE_DRIVER
- **Description:** Failed job storage driver
- **Type:** String (enum)
- **Values:** `database`, `database-uuids`
- **Default:** `database-uuids`

---

## Redis Configuration

### REDIS_CLIENT
- **Description:** Redis client library
- **Type:** String (enum)
- **Values:** `phpredis`, `predis`
- **Default:** `phpredis`
- **Recommendation:** `phpredis` for performance (requires PHP extension)

### REDIS_HOST
- **Description:** Redis server hostname
- **Type:** String
- **Default:** `127.0.0.1`
- **Production:** Use managed Redis service

### REDIS_PASSWORD
- **Description:** Redis authentication password
- **Type:** String
- **Default:** `null` (no authentication)
- **Production:** Always use password

### REDIS_PORT
- **Description:** Redis server port
- **Type:** Integer
- **Default:** `6379`

### Redis Database Numbers

#### REDIS_DB
- **Description:** Default Redis database
- **Type:** Integer (0-15)
- **Default:** `0`

#### REDIS_CACHE_DB
- **Description:** Cache database
- **Default:** `1`

#### REDIS_QUEUE_DB
- **Description:** Queue database
- **Default:** `2`

#### REDIS_SESSION_DB
- **Description:** Session database
- **Default:** `3`

### Redis Cluster (Optional)

#### REDIS_CLUSTER
- **Description:** Enable Redis cluster
- **Type:** Boolean
- **Default:** `false`

#### REDIS_CLUSTER_NODES
- **Description:** Comma-separated cluster nodes
- **Example:** `127.0.0.1:6379,127.0.0.1:6380`

---

## Mail Configuration

### MAIL_MAILER
- **Description:** Mail driver
- **Type:** String (enum)
- **Values:** `smtp`, `ses`, `mailgun`, `postmark`, `sendmail`, `log`, `array`
- **Default:** `log` (development), `ses` (production)

### SMTP Configuration

#### MAIL_HOST
- **Description:** SMTP server hostname
- **Example:** `smtp.gmail.com`, `smtp.mailgun.org`

#### MAIL_PORT
- **Description:** SMTP server port
- **Values:** `25`, `587` (TLS), `465` (SSL)
- **Recommendation:** `587` with `MAIL_ENCRYPTION=tls`

#### MAIL_USERNAME / MAIL_PASSWORD
- **Description:** SMTP authentication credentials

#### MAIL_ENCRYPTION
- **Description:** Encryption protocol
- **Values:** `tls`, `ssl`, `null`
- **Default:** `tls`

### Mail From

#### MAIL_FROM_ADDRESS
- **Description:** Default sender email address
- **Example:** `noreply@yourdomain.com`
- **Required:** Yes

#### MAIL_FROM_NAME
- **Description:** Default sender name
- **Default:** `${APP_NAME}`

### AWS SES

#### AWS_SES_KEY
- **Description:** AWS SES access key ID

#### AWS_SES_SECRET
- **Description:** AWS SES secret access key

#### AWS_SES_REGION
- **Description:** AWS SES region
- **Default:** `us-east-1`

---

## OAuth & API

### Passport Configuration

#### PASSPORT_ACCESS_TOKEN_LIFETIME
- **Description:** Access token lifetime in minutes
- **Type:** Integer
- **Default:** `60` (1 hour)
- **Recommendation:** `15-60` minutes

#### PASSPORT_REFRESH_TOKEN_LIFETIME
- **Description:** Refresh token lifetime in minutes
- **Type:** Integer
- **Default:** `20160` (14 days)
- **Recommendation:** `7-30` days

#### PASSPORT_PERSONAL_ACCESS_TOKEN_LIFETIME
- **Description:** Personal access token lifetime in minutes
- **Type:** Integer
- **Default:** `262800` (6 months)

### API Configuration

#### API_VERSION
- **Description:** API version
- **Type:** String
- **Default:** `v2.1`

#### API_RATE_LIMIT_AUTHENTICATED
- **Description:** Requests per hour for authenticated users
- **Type:** Integer
- **Default:** `1000`

#### API_RATE_LIMIT_UNAUTHENTICATED
- **Description:** Requests per hour for unauthenticated users
- **Type:** Integer
- **Default:** `100`

#### API_RATE_LIMIT_WINDOW
- **Description:** Rate limit window in minutes
- **Type:** Integer
- **Default:** `60`

#### API_BURST_LIMIT
- **Description:** Burst requests per second
- **Type:** Integer
- **Default:** `20`

#### LOGIN_RATE_LIMIT
- **Description:** Login attempts per minute
- **Type:** Integer
- **Default:** `5`

---

## Horizon Configuration

### HORIZON_PATH
- **Description:** URL path for Horizon dashboard
- **Type:** String
- **Default:** `horizon`
- **Security:** Protect with authentication middleware

### HORIZON_MEMORY_LIMIT
- **Description:** Worker memory limit in MB
- **Type:** Integer
- **Default:** `128` (development), `256` (production)

### HORIZON_BALANCE
- **Description:** Queue balancing strategy
- **Type:** String (enum)
- **Values:** `simple`, `auto`
- **Default:** `auto`

### HORIZON_PROCESSES
- **Description:** Number of worker processes
- **Type:** Integer
- **Default:** `3` (development), `5-10` (production)

### HORIZON_TRIES
- **Description:** Job retry attempts
- **Type:** Integer
- **Default:** `3`

### HORIZON_TIMEOUT
- **Description:** Job timeout in seconds
- **Type:** Integer
- **Default:** `300` (5 minutes)

---

## Security

### FORCE_HTTPS
- **Description:** Redirect HTTP to HTTPS
- **Type:** Boolean
- **Default:** `false` (development), `true` (production)

### CORS Configuration

#### CORS_ALLOWED_ORIGINS
- **Description:** Allowed CORS origins
- **Type:** String (comma-separated or `*`)
- **Development:** `*`
- **Production:** Specific domains (e.g., `https://app.yourdomain.com`)

#### CORS_ALLOWED_METHODS
- **Description:** Allowed HTTP methods
- **Default:** `GET,POST,PUT,PATCH,DELETE,OPTIONS`

#### CORS_ALLOWED_HEADERS
- **Description:** Allowed request headers
- **Default:** `Content-Type,X-Requested-With,Authorization,X-Api-Key,X-Request-ID`

#### CORS_EXPOSED_HEADERS
- **Description:** Headers exposed to client
- **Default:** `X-RateLimit-Limit,X-RateLimit-Remaining,X-Request-ID`

### API Key Configuration

#### API_KEY_HASH_ALGO
- **Description:** Hash algorithm for API keys
- **Type:** String
- **Default:** `sha256`
- **Values:** `sha256`, `sha512`

#### API_KEY_LENGTH
- **Description:** API key length in bytes
- **Type:** Integer
- **Default:** `32`

### Password Rules

#### PASSWORD_MIN_LENGTH
- **Description:** Minimum password length
- **Type:** Integer
- **Default:** `8`

#### PASSWORD_REQUIRE_UPPERCASE
- **Description:** Require uppercase letters
- **Type:** Boolean
- **Default:** `true`

#### PASSWORD_REQUIRE_LOWERCASE
- **Description:** Require lowercase letters
- **Type:** Boolean
- **Default:** `true`

#### PASSWORD_REQUIRE_NUMBERS
- **Description:** Require numbers
- **Type:** Boolean
- **Default:** `true`

#### PASSWORD_REQUIRE_SPECIAL_CHARS
- **Description:** Require special characters
- **Type:** Boolean
- **Default:** `true`

---

## Document Processing

### MAX_DOCUMENT_SIZE
- **Description:** Maximum document size in bytes
- **Type:** Integer
- **Default:** `25000000` (25 MB)

### MAX_DOCUMENTS_PER_ENVELOPE
- **Description:** Maximum documents per envelope
- **Type:** Integer
- **Default:** `50`

### ALLOWED_DOCUMENT_TYPES
- **Description:** Allowed file types (comma-separated)
- **Type:** String
- **Default:** `pdf,doc,docx`

### PDF_PROCESSOR
- **Description:** PDF processing library
- **Type:** String (enum)
- **Values:** `pdftk`, `ghostscript`
- **Default:** `pdftk`

### IMAGE_PROCESSOR
- **Description:** Image processing library
- **Type:** String (enum)
- **Values:** `imagick`, `gd`
- **Default:** `imagick`

### MAX_IMAGE_SIZE
- **Description:** Maximum image size in bytes
- **Type:** Integer
- **Default:** `5000000` (5 MB)

---

## Third-Party Services

### Sentry (Error Tracking)

#### SENTRY_LARAVEL_DSN
- **Description:** Sentry DSN URL
- **Example:** `https://xxxxx@sentry.io/xxxxx`

#### SENTRY_TRACES_SAMPLE_RATE
- **Description:** Performance monitoring sample rate
- **Type:** Float (0.0 - 1.0)
- **Default:** `1.0` (development), `0.1` (production)

### New Relic (APM)

#### NEW_RELIC_ENABLED
- **Description:** Enable New Relic monitoring
- **Type:** Boolean
- **Default:** `false` (development), `true` (production)

#### NEW_RELIC_APP_NAME
- **Description:** Application name in New Relic
- **Example:** `DocuSign Signing API - Production`

#### NEW_RELIC_LICENSE_KEY
- **Description:** New Relic license key
- **Required:** If enabled

### Stripe (Payment Processing)

#### STRIPE_KEY
- **Description:** Stripe publishable key

#### STRIPE_SECRET
- **Description:** Stripe secret key

#### STRIPE_WEBHOOK_SECRET
- **Description:** Stripe webhook signing secret

---

## Environment-Specific Files

### File Structure

```
.env                      # Active environment configuration
.env.example              # Template for local development
.env.production.example   # Template for production
```

### Best Practices

1. **Never commit actual .env files** to version control
2. **Keep .env.example updated** when adding new variables
3. **Document all variables** with comments and examples
4. **Use different values** for each environment (dev, staging, prod)
5. **Rotate secrets regularly** (database passwords, API keys)
6. **Use environment-specific prefixes** for cache and queue keys
7. **Enable SSL/TLS** in production for all external connections
8. **Use managed services** in production (RDS, ElastiCache, SES)

### Environment Comparison

| Setting | Development | Production |
|---------|------------|------------|
| APP_ENV | `local` | `production` |
| APP_DEBUG | `true` | `false` |
| DB_SSLMODE | `prefer` | `require` |
| CACHE_STORE | `file` | `redis` |
| QUEUE_CONNECTION | `sync` | `redis` |
| MAIL_MAILER | `log` | `ses` |
| FILESYSTEM_DISK | `local` | `s3` |
| LOG_LEVEL | `debug` | `error` |
| FORCE_HTTPS | `false` | `true` |
| CORS_ALLOWED_ORIGINS | `*` | specific domains |

---

## Configuration Caching

For production performance, cache your configuration:

```bash
# Cache configuration
php artisan config:cache

# Clear configuration cache
php artisan config:clear
```

**Warning:** When configuration is cached, `.env` file is not read. Update cache after changing environment variables.

---

## Troubleshooting

### Configuration Not Loading
- Run `php artisan config:clear`
- Check file permissions on `.env`
- Verify `.env` file location (project root)

### Database Connection Issues
- Verify `DB_*` credentials
- Check firewall rules
- Test connection: `php artisan migrate:status`

### Redis Connection Issues
- Verify Redis is running: `redis-cli ping`
- Check `REDIS_*` configuration
- Ensure PHPRedis extension installed: `php -m | grep redis`

### Queue Not Processing
- Start Horizon: `php artisan horizon`
- Check queue configuration: `php artisan queue:monitor`
- Verify Redis connection

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Database password set and secure
- [ ] Redis password set
- [ ] `FORCE_HTTPS=true` in production
- [ ] `SESSION_SECURE_COOKIE=true` in production
- [ ] CORS origins restricted (not `*`)
- [ ] API rate limiting configured
- [ ] Sentry or error tracking enabled
- [ ] Regular secret rotation scheduled
- [ ] `.env` file not in version control
- [ ] Production `.env` file has restricted permissions (600)

---

## Additional Resources

- [Laravel Configuration Documentation](https://laravel.com/docs/configuration)
- [Laravel Environment Configuration](https://laravel.com/docs/configuration#environment-configuration)
- [Twelve-Factor App Methodology](https://12factor.net/config)
