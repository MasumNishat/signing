# CI/CD Pipeline Documentation

This document describes the Continuous Integration and Continuous Deployment (CI/CD) pipeline for the DocuSign Signing API project.

## Table of Contents

1. [Overview](#overview)
2. [Workflows](#workflows)
3. [Pipeline Stages](#pipeline-stages)
4. [Secrets Configuration](#secrets-configuration)
5. [Deployment Process](#deployment-process)
6. [Monitoring & Notifications](#monitoring--notifications)
7. [Troubleshooting](#troubleshooting)

---

## Overview

The CI/CD pipeline is built using **GitHub Actions** and consists of three main workflows:

1. **CI Pipeline** (`ci.yml`) - Automated testing and quality checks
2. **Deployment Pipeline** (`deploy.yml`) - Automated deployment to staging/production
3. **Code Quality** (`code-quality.yml`) - Static analysis and code quality metrics

### Pipeline Triggers

| Workflow | Triggers |
|----------|----------|
| CI | Push to `develop`/`main`, Pull requests |
| Deploy | Push to `develop`/`main`, Version tags (`v*`) |
| Code Quality | Pull requests, Schedule (weekly), Manual |

---

## Workflows

### 1. CI Pipeline (`ci.yml`)

**Purpose:** Ensure code quality and functionality before deployment

**Jobs:**
1. **Lint & Code Style**
   - PHP syntax check
   - PHP CS Fixer (dry-run)
   - Laravel Pint

2. **Static Analysis**
   - PHPStan analysis
   - Psalm analysis

3. **Unit Tests**
   - Run PHPUnit unit tests
   - Generate code coverage
   - Upload to Codecov
   - Minimum coverage: 80%

4. **Integration Tests**
   - Run feature/integration tests
   - Uses PostgreSQL & Redis services
   - Full database migrations
   - Upload coverage to Codecov

5. **Security Checks**
   - Composer security audit
   - Check for known vulnerabilities

6. **Build Check**
   - Create optimized production build
   - Upload build artifacts

**Environments:**
- Unit Tests: SQLite in-memory
- Integration Tests: PostgreSQL 16, Redis 7

**Pass Criteria:**
- All linting checks pass
- All unit tests pass
- All integration tests pass
- Code coverage ≥ 80%
- No security vulnerabilities
- Build succeeds

---

### 2. Deployment Pipeline (`deploy.yml`)

**Purpose:** Deploy application to staging and production environments

**Jobs:**

#### Setup
- Determine target environment (staging/production)
- Extract version from git tag or commit

#### Build Docker Image
- Build production Docker image
- Tag with version, branch, SHA
- Push to container registry
- Use layer caching for faster builds

#### Deploy to Staging
- **Trigger:** Push to `develop` branch
- **Environment:** staging
- **URL:** https://staging-api.yourdomain.com
- **Steps:**
  1. Pull latest Docker images
  2. Start containers
  3. Run database migrations
  4. Cache configuration
  5. Restart Horizon workers
  6. Run smoke tests
  7. Send Slack notification

#### Deploy to Production
- **Trigger:** Push to `main` branch or version tag
- **Environment:** production
- **URL:** https://api.yourdomain.com
- **Steps:**
  1. Create database backup
  2. Pull latest Docker images
  3. Start containers
  4. Run database migrations
  5. Cache configuration
  6. Restart Horizon workers
  7. Run smoke tests
  8. Create GitHub release (if version tag)
  9. Notify Sentry of deployment
  10. Send Slack notification

**Deployment Strategy:**
- Blue-Green deployment (zero downtime)
- Automatic rollback on smoke test failure
- Database backups before production deployment

---

### 3. Code Quality Pipeline (`code-quality.yml`)

**Purpose:** Maintain high code quality standards

**Jobs:**

1. **PHPStan** - Static analysis (level max)
2. **Psalm** - Additional static analysis
3. **PHP Code Sniffer** - PSR-12 compliance
4. **Laravel Pint** - Laravel code style
5. **PHP Mess Detector** - Code quality metrics
6. **PHP Copy/Paste Detector** - Duplication detection
7. **Code Coverage** - Full test coverage analysis
8. **Dependency Analysis** - Check for outdated/incompatible packages

**Schedule:**
- Runs weekly on Sunday at midnight (UTC)
- Runs on all pull requests
- Can be triggered manually

---

## Pipeline Stages

### Stage 1: Pre-Commit (Local)
*Optional but recommended*

```bash
# Install pre-commit hooks
cp .git-hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

Checks:
- PHP syntax errors
- Laravel Pint formatting
- PHPStan analysis (basic)

### Stage 2: Pull Request

**Triggered by:** Opening or updating a PR

**Process:**
1. Run CI pipeline (lint, test, build)
2. Run code quality checks
3. Generate coverage reports
4. Post results as PR comments
5. Block merge if checks fail

**Required Checks:**
- ✅ Lint & Code Style
- ✅ Unit Tests (≥80% coverage)
- ✅ Integration Tests
- ✅ Build Success

**Optional Checks:**
- ⚠️ Static Analysis (warnings allowed)
- ⚠️ Security Audit (warnings allowed)

### Stage 3: Merge to Develop

**Triggered by:** Merging PR to `develop`

**Process:**
1. Run full CI pipeline
2. Build Docker image (tagged as `develop`)
3. Deploy to staging environment
4. Run smoke tests
5. Notify team via Slack

### Stage 4: Merge to Main (Production)

**Triggered by:** Merging to `main` or pushing version tag

**Process:**
1. Run full CI pipeline
2. Create database backup
3. Build Docker image (tagged as version)
4. Deploy to production
5. Run smoke tests
6. Create GitHub release
7. Notify Sentry
8. Notify team via Slack

---

## Secrets Configuration

### Required Secrets

Configure these secrets in GitHub Settings → Secrets and variables → Actions:

#### Docker Registry
```
DOCKER_REGISTRY      # Docker registry URL (e.g., ghcr.io)
DOCKER_USERNAME      # Registry username
DOCKER_PASSWORD      # Registry password/token
```

#### Staging Environment
```
STAGING_HOST         # Staging server hostname
STAGING_USER         # SSH username
STAGING_SSH_KEY      # Private SSH key for authentication
```

#### Production Environment
```
PRODUCTION_HOST      # Production server hostname
PRODUCTION_USER      # SSH username
PRODUCTION_SSH_KEY   # Private SSH key for authentication
```

#### Notifications
```
SLACK_WEBHOOK        # Slack webhook URL for notifications
```

#### Monitoring (Optional)
```
SENTRY_ORG           # Sentry organization slug
SENTRY_AUTH_TOKEN    # Sentry authentication token
CODECOV_TOKEN        # Codecov upload token
```

### Generating SSH Keys for Deployment

```bash
# Generate SSH key pair
ssh-keygen -t ed25519 -C "github-actions@signing-api" -f deploy_key

# Add public key to server's authorized_keys
cat deploy_key.pub | ssh user@server 'cat >> ~/.ssh/authorized_keys'

# Add private key to GitHub Secrets
cat deploy_key
```

---

## Deployment Process

### Manual Deployment

#### Deploy to Staging

```bash
# Via GitHub Actions UI
1. Go to Actions → Deploy workflow
2. Click "Run workflow"
3. Select "staging" environment
4. Click "Run workflow"
```

#### Deploy to Production

```bash
# Via Git Tag
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# Via GitHub Actions UI
1. Go to Actions → Deploy workflow
2. Click "Run workflow"
3. Select "production" environment
4. Click "Run workflow"
```

### Rollback Procedure

#### Quick Rollback (Docker)

```bash
# SSH to server
ssh user@production-server

# Rollback to previous image
cd /var/www/signing-api
docker-compose down
docker tag signing-api:previous signing-api:latest
docker-compose up -d
```

#### Full Rollback (Git)

```bash
# Find previous good version
git log --oneline

# Create hotfix branch from good version
git checkout -b hotfix/rollback-v1.0.1 <commit-sha>

# Tag and push
git tag -a v1.0.1 -m "Rollback to stable version"
git push origin v1.0.1
```

---

## Monitoring & Notifications

### Slack Notifications

Deployment status notifications are sent to Slack:

**Success:**
```
✅ Staging deployment succeeded
Branch: develop
Commit: abc123 - feat: add new feature
URL: https://staging-api.yourdomain.com
```

**Failure:**
```
❌ Production deployment failed
Branch: main
Commit: def456 - fix: critical bug
Error: Smoke tests failed
```

### Sentry Integration

Production deployments are automatically reported to Sentry:

- Release version tracked
- Error rates monitored
- Performance metrics collected

### Health Checks

Automated smoke tests check:

- `/health` endpoint responds with 200
- Database connectivity
- Redis connectivity
- Basic API functionality

---

## Troubleshooting

### CI Pipeline Failures

#### Tests Failing Locally But Passing in CI

**Solution:**
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Reinstall dependencies
rm -rf vendor
composer install

# Run tests
php artisan test
```

#### Code Coverage Below Threshold

**Solution:**
```bash
# Generate coverage report
php artisan test --coverage

# View detailed report
open coverage/html/index.html

# Add tests for uncovered code
```

#### PHPStan/Psalm Errors

**Solution:**
```bash
# Run locally
vendor/bin/phpstan analyse

# Fix issues or add to baseline
vendor/bin/phpstan analyse --generate-baseline

# Run Psalm
vendor/bin/psalm
```

### Deployment Failures

#### Docker Build Fails

**Solution:**
```bash
# Build locally
docker-compose build

# Check Dockerfile syntax
# Fix any issues

# Push changes
git add Dockerfile
git commit -m "fix: resolve Docker build issues"
git push
```

#### Database Migration Fails

**Solution:**
```bash
# SSH to server
ssh user@server

# Check migration status
cd /var/www/signing-api
docker-compose exec app php artisan migrate:status

# Rollback if needed
docker-compose exec app php artisan migrate:rollback

# Fix migration files
# Redeploy
```

#### Smoke Tests Fail

**Solution:**
```bash
# Check application logs
docker-compose logs app

# Check database connectivity
docker-compose exec app php artisan migrate:status

# Check Redis connectivity
docker-compose exec redis redis-cli ping

# Manual smoke test
curl -v https://api.yourdomain.com/health
```

### Common Issues

#### Secret Not Found

**Error:** `Error: Secret STAGING_HOST not found`

**Solution:**
1. Go to GitHub Settings → Secrets and variables → Actions
2. Add the missing secret
3. Re-run the workflow

#### SSH Authentication Failed

**Error:** `Permission denied (publickey)`

**Solution:**
1. Verify SSH key is correctly added to server
2. Check key format (no extra whitespace)
3. Regenerate key if needed

#### Docker Login Failed

**Error:** `Error: Cannot perform an interactive login from a non TTY device`

**Solution:**
1. Check `DOCKER_PASSWORD` secret value
2. Regenerate token if using personal access token
3. Verify registry URL is correct

---

## Best Practices

### For Developers

1. **Always run tests locally** before pushing
2. **Check CI status** before merging PRs
3. **Write meaningful commit messages** (follows Conventional Commits)
4. **Keep branches up to date** with develop/main
5. **Monitor deployment notifications** in Slack

### For Deployments

1. **Deploy to staging first** - Always test in staging before production
2. **Create backups** - Automatic for production, manual for major changes
3. **Monitor after deployment** - Watch logs and metrics for 15-30 minutes
4. **Plan rollback** - Know how to rollback before deploying
5. **Communicate** - Notify team of planned deployments

### For CI/CD Maintenance

1. **Keep workflows updated** - Review and update workflows quarterly
2. **Monitor pipeline performance** - Optimize slow jobs
3. **Rotate secrets regularly** - Update SSH keys, tokens every 90 days
4. **Review notifications** - Ensure alerts reach the right people
5. **Test disaster recovery** - Practice rollback procedures

---

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Docker Build Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [PHPUnit Testing](https://phpunit.de/documentation.html)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [Conventional Commits](https://www.conventionalcommits.org/)
