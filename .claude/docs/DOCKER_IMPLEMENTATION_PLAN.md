# Docker Implementation Plan - ChurchEase

**Date:** 2025-11-08
**Status:** In Progress
**Scope:** P0 Critical Fixes + SSR Support + Laravel Horizon Integration

---

## Executive Summary

This document outlines the implementation plan to fix critical issues in the ChurchEase Docker local development environment. The current setup has a solid foundation but contains 6 critical gaps that would prevent proper functionality.

**Architectural Review Verdict:** ✅ **On the right track** - requires critical fixes to be enterprise-grade

---

## Background

### Current State
- Multi-stage Dockerfile (base, development, production)
- Services: PHP-FPM, Nginx, PostgreSQL, Redis, Mailpit, Adminer
- Good security practices (non-root user, no hardcoded secrets)
- Proper health checks and dependency management

### Issues Identified
The code-architect agent identified:
- **6 P0 (Critical) issues** - Must fix immediately
- **10 P1 (Important) improvements** - Should fix soon
- **8 P2 (Nice to have) optimizations** - Can do later

### User Requirements
Based on user answers:
- ✅ Use **Laravel Horizon** for queue management
- ✅ Enable **Inertia SSR** support
- ✅ Remove **Laravel Sail** dependency
- ✅ Implement **P0 fixes only** in this session

---

## Critical Issues (P0) - This Session

### 1. Missing .dockerignore File

**Problem:** Entire project context (node_modules, vendor, .git) sent to Docker daemon during build

**Impact:**
- Build times: Several minutes instead of seconds
- Security: Risk of copying .env files into image layers
- Image size: Unnecessarily large images

**Solution:** Create comprehensive .dockerignore file

**Files to create:**
- `.dockerignore`

---

### 2. Missing PHP-FPM Health Check Script

**Problem:** Health check references `php-fpm-healthcheck` script that doesn't exist

**Location:** `docker.compose.yaml:40`

```yaml
healthcheck:
  test: ["CMD-SHELL", "php-fpm-healthcheck || exit 1"]
```

**Impact:** Container marked unhealthy, misleading health status

**Solution:** Add health check script installation to Dockerfile

**Files to modify:**
- `.docker/app/Dockerfile`

---

### 3. Incorrect Nginx Configuration Path

**Problem:** Compose file references wrong path for nginx config

**Location:** `docker.compose.yaml:59`

```yaml
# WRONG
- ./.docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro

# CORRECT
- ./.docker/server/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
```

**Impact:** Nginx container fails to start or uses default config

**Solution:** Fix path in docker.compose.yaml

**Files to modify:**
- `docker.compose.yaml`

---

### 4. Missing Queue Worker Service

**Problem:** No dedicated container for processing queued jobs

**Impact:**
- Queued jobs won't process automatically
- Developers must manually run queue workers
- Production parity broken

**Solution:** Add Laravel Horizon service (per user requirement)

**Benefits of Horizon:**
- Beautiful dashboard at http://localhost:8888
- Real-time queue monitoring
- Metrics and insights
- Failed job management
- Auto-scaling workers

**Files to modify:**
- `docker.compose.yaml`

---

### 5. Missing Laravel Scheduler Service

**Problem:** No cron/scheduler container for running scheduled tasks

**Impact:** Scheduled tasks defined in application won't execute

**Solution:** Add dedicated scheduler service that runs `php artisan schedule:run` every minute

**Files to modify:**
- `docker.compose.yaml`

---

### 6. Environment Variable Mismatch

**Problem:** `.env.example` doesn't match Docker environment

**Current issues:**
- Uses `DB_CONNECTION=sqlite` but Docker has PostgreSQL
- Redis password mismatch (`null` vs `churchease`)
- Database host not set for Docker (`127.0.0.1` vs `postgres`)

**Impact:** New developers copy .env.example and get broken configuration

**Solution:** Update .env.example with Docker-specific defaults

**Files to modify:**
- `.env.example`

---

## Additional Requirements

### 7. Inertia SSR Support

**Requirement:** Add Server-Side Rendering for Inertia.js

**Why SSR:**
- Better SEO (search engines see rendered content)
- Faster initial page load
- Social media sharing with proper meta tags

**Implementation:**
- Add Node.js to production stage in Dockerfile
- Create dedicated SSR server service
- Configure Inertia SSR in compose file

**Files to modify:**
- `.docker/app/Dockerfile`
- `docker.compose.yaml`

---

### 8. Remove Laravel Sail

**Requirement:** Remove laravel/sail package dependency

**Reasoning:**
- Custom Docker setup is more comprehensive than Sail
- Avoids confusion about which setup to use
- Prevents port conflicts between setups

**Implementation:**
- Run `composer remove laravel/sail --dev`

**Files to modify:**
- `composer.json` (via composer command)

---

### 9. Entrypoint Script Bug

**Problem:** Script validates for password "flock" but compose uses "churchease"

**Location:** `.docker/app/docker-entrypoint.sh:27-35`

```bash
# WRONG
if grep -q "DB_PASSWORD=flock" .env 2>/dev/null; then

# CORRECT
if grep -q "DB_PASSWORD=churchease" .env 2>/dev/null; then
```

**Impact:** Password validation doesn't work, misleading warnings

**Solution:** Fix password string in validation

**Files to modify:**
- `.docker/app/docker-entrypoint.sh`

---

## Implementation Details

### File 1: .dockerignore

**Purpose:** Exclude unnecessary files from Docker build context

**Contents:**
```dockerignore
# Dependencies
node_modules/
vendor/

# Environment & secrets
.env
.env.*
!.env.example

# Version control
.git/
.github/
.gitignore
.gitattributes

# Docker files
docker.compose.yaml
docker-compose.yml
.docker/
Dockerfile*
.dockerignore

# IDE
.idea/
.vscode/
*.swp
*.swo
*~

# OS files
.DS_Store
Thumbs.db

# Laravel specific
storage/framework/cache/**
storage/framework/sessions/**
storage/framework/views/**
storage/logs/**
storage/app/public/**
bootstrap/cache/**
!storage/framework/.gitignore
!storage/logs/.gitignore
!bootstrap/cache/.gitignore

# Build artifacts
public/build/
public/hot

# Testing
.phpunit.result.cache
coverage/

# Temporary files
*.log
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# Documentation (don't copy into image)
*.md
!README.md
```

---

### File 2: .docker/app/Dockerfile

**Changes:**

1. **Add PHP-FPM health check script** (in base stage after libfcgi-bin installation)

```dockerfile
# Install PHP-FPM health check script
RUN echo '#!/bin/bash\n\
SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \\\n\
cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q "pong"' \
    > /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck
```

2. **Add Node.js to production stage** (for SSR support)

```dockerfile
# Production Stage
FROM base AS production

# Install Node.js for SSR
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# ... rest of production stage
```

---

### File 3: docker.compose.yaml

**Changes:**

1. **Fix Nginx config path**
```yaml
nginx:
  volumes:
    - ./.docker/server/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro  # FIXED PATH
```

2. **Add Laravel Horizon service**
```yaml
###########################################
# Laravel Horizon (Queue Management)
###########################################
horizon:
  build:
    context: .
    dockerfile: ./.docker/app/Dockerfile
    target: development
    args:
      UID: ${UID:-1000}
      GID: ${GID:-1000}
  container_name: churchease_horizon
  restart: unless-stopped
  command: php artisan horizon
  environment:
    TZ: ${TZ:-UTC}
    APP_TIMEZONE: ${APP_TIMEZONE:-Asia/Manila}
  volumes:
    - .:/var/www/app:cached
  networks:
    - churchease-network
  depends_on:
    postgres:
      condition: service_healthy
    redis:
      condition: service_healthy
  ports:
    - "${HORIZON_PORT:-8888}:8888"
  deploy:
    resources:
      reservations:
        cpus: '0.5'
        memory: 512M
  healthcheck:
    test: ["CMD-SHELL", "ps aux | grep -q '[h]orizon' || exit 1"]
    interval: 30s
    timeout: 3s
    retries: 3
    start_period: 40s
```

3. **Add Laravel Scheduler service**
```yaml
###########################################
# Laravel Scheduler
###########################################
scheduler:
  build:
    context: .
    dockerfile: ./.docker/app/Dockerfile
    target: development
    args:
      UID: ${UID:-1000}
      GID: ${GID:-1000}
  container_name: churchease_scheduler
  restart: unless-stopped
  command: >
    sh -c "while true; do
      php artisan schedule:run --verbose --no-interaction &
      sleep 60
    done"
  environment:
    TZ: ${TZ:-UTC}
    APP_TIMEZONE: ${APP_TIMEZONE:-Asia/Manila}
  volumes:
    - .:/var/www/app:cached
  networks:
    - churchease-network
  depends_on:
    postgres:
      condition: service_healthy
    redis:
      condition: service_healthy
  deploy:
    resources:
      reservations:
        cpus: '0.1'
        memory: 256M
  healthcheck:
    test: ["CMD-SHELL", "ps aux | grep -q '[s]chedule:run' || exit 1"]
    interval: 60s
    timeout: 3s
    retries: 2
```

4. **Add Inertia SSR service**
```yaml
###########################################
# Inertia SSR Server
###########################################
ssr:
  build:
    context: .
    dockerfile: ./.docker/app/Dockerfile
    target: development
    args:
      UID: ${UID:-1000}
      GID: ${GID:-1000}
  container_name: churchease_ssr
  restart: unless-stopped
  command: node bootstrap/ssr/ssr.js
  environment:
    NODE_ENV: development
  volumes:
    - .:/var/www/app:cached
  networks:
    - churchease-network
  ports:
    - "${SSR_PORT:-13714}:13714"
  deploy:
    resources:
      reservations:
        cpus: '0.25'
        memory: 256M
  healthcheck:
    test: ["CMD-SHELL", "curl -f http://localhost:13714/healthz || exit 1"]
    interval: 30s
    timeout: 3s
    retries: 3
    start_period: 10s
```

---

### File 4: .env.example

**Changes:** Update database and Redis configuration for Docker

**Before:**
```env
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**After:**
```env
# Database (Docker PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=churchease
DB_USERNAME=churchease
DB_PASSWORD=churchease

# Redis (Docker)
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=churchease
REDIS_PORT=6379

# Cache & Queue Configuration
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail (Docker Mailpit)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

---

### File 5: .docker/app/docker-entrypoint.sh

**Change:** Fix password validation

**Before (line 27):**
```bash
if grep -q "DB_PASSWORD=flock" .env 2>/dev/null; then
```

**After:**
```bash
if grep -q "DB_PASSWORD=churchease" .env 2>/dev/null; then
```

---

### File 6: .env.docker (New Template)

**Purpose:** Complete reference for Docker-specific environment variables

**Contents:**
```env
###########################################
# ChurchEase - Docker Environment
###########################################
# Copy this file to .env and update as needed
# This template is specifically for Docker development

###########################################
# Application
###########################################
APP_NAME=ChurchEase
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Manila

###########################################
# Database (Docker PostgreSQL)
###########################################
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=churchease
DB_USERNAME=churchease
DB_PASSWORD=churchease

###########################################
# Redis (Docker)
###########################################
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=churchease
REDIS_PORT=6379

###########################################
# Cache & Session
###########################################
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

###########################################
# Queue
###########################################
QUEUE_CONNECTION=redis

###########################################
# Mail (Docker Mailpit)
###########################################
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@churchease.local"
MAIL_FROM_NAME="${APP_NAME}"

###########################################
# Docker Service Ports
###########################################
# These ports are exposed on your host machine
NGINX_PORT=8000
MAILPIT_HTTP_PORT=8025
MAILPIT_SMTP_PORT=1025
ADMINER_PORT=8080
HORIZON_PORT=8888
SSR_PORT=13714

###########################################
# Docker Build Arguments
###########################################
UID=1000
GID=1000
TZ=UTC

###########################################
# Inertia SSR
###########################################
INERTIA_SSR_ENABLED=true
INERTIA_SSR_URL=http://ssr:13714

###########################################
# Broadcasting (if needed)
###########################################
BROADCAST_CONNECTION=log
# BROADCAST_CONNECTION=reverb
# REVERB_APP_ID=
# REVERB_APP_KEY=
# REVERB_APP_SECRET=
# REVERB_HOST=localhost
# REVERB_PORT=8080
# REVERB_SCHEME=http
```

---

## Composer Command

**Action:** Remove Laravel Sail dependency

```bash
composer remove laravel/sail --dev
```

**Why:** Custom Docker setup is more comprehensive; Sail would cause conflicts

---

## Testing Plan

### 1. Pre-flight Checks
```bash
# Verify files exist
ls -la .dockerignore
ls -la .env.docker
cat .env.example | grep "DB_CONNECTION=pgsql"

# Check Dockerfile has health check script
grep "php-fpm-healthcheck" .docker/app/Dockerfile
```

### 2. Build Containers
```bash
# Stop any running containers
docker compose down -v

# Rebuild from scratch (no cache)
docker compose build --no-cache

# Expected: All stages build successfully
```

### 3. Start Services
```bash
# Start in detached mode
docker compose up -d

# Watch logs
docker compose logs -f
```

### 4. Verify Service Health
```bash
# Check all services are healthy
docker compose ps

# Expected output:
# NAME                    STATUS
# churchease_app          Up (healthy)
# churchease_nginx        Up (healthy)
# churchease_postgres     Up (healthy)
# churchease_redis        Up (healthy)
# churchease_mailpit      Up (healthy)
# churchease_adminer      Up (healthy)
# churchease_horizon      Up (healthy)
# churchease_scheduler    Up (healthy)
# churchease_ssr          Up (healthy)
```

### 5. Test Application
```bash
# Test main application
curl -I http://localhost:8000

# Test Horizon dashboard
curl -I http://localhost:8888

# Test SSR server
curl http://localhost:13714/healthz

# Test Mailpit UI
curl -I http://localhost:8025

# Test Adminer
curl -I http://localhost:8080
```

### 6. Test Database Connection
```bash
# Shell into app container
docker compose exec app bash

# Run migrations
php artisan migrate

# Expected: Migrations run successfully
```

### 7. Test Queue Processing
```bash
# Dispatch a test job
php artisan tinker
>>> dispatch(function() { info('Queue test'); });

# Check Horizon dashboard
# Go to http://localhost:8888
# Should see job processed
```

### 8. Test Scheduler
```bash
# Check scheduler logs
docker compose logs scheduler

# Should see schedule:run output every minute
```

---

## Rollback Plan

If issues occur during implementation:

```bash
# Stop all containers
docker compose down -v

# Restore from git (if needed)
git checkout docker.compose.yaml .docker/app/Dockerfile .env.example

# Remove new files
rm -f .dockerignore .env.docker

# Rebuild old setup
docker compose up -d
```

---

## Success Criteria

✅ All 9 services start successfully
✅ All health checks pass
✅ Application accessible at http://localhost:8000
✅ Horizon dashboard accessible at http://localhost:8888
✅ SSR server responds on port 13714
✅ Database migrations run successfully
✅ Queue jobs process through Horizon
✅ Scheduler runs tasks every minute
✅ No build warnings or errors
✅ Build time reduced (due to .dockerignore)

---

## Post-Implementation Notes

### Horizon Setup
If Horizon is not installed yet:
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

### SSR Setup
If SSR is not configured yet:
```bash
php artisan inertia:start-ssr
```

### Documentation Updates Needed
After successful implementation, update:
1. README.md - Add Docker setup instructions
2. CLAUDE.md - Document Docker-specific development workflow
3. Create DOCKER_SETUP.md - Comprehensive Docker guide

---

## Future Improvements (Not in this session)

**P1 (Important) - Next Sprint:**
- Add Xdebug for debugging
- Add missing PHP extensions (intl, gmp, imagick)
- Add Composer/npm cache volumes
- Improve entrypoint script

**P2 (Nice to have) - Future:**
- Create Makefile for common commands
- Add backup/restore scripts
- Add monitoring (Prometheus/Grafana)
- Add SSL/TLS for local HTTPS
- Add multi-environment support

---

## References

- [Laravel Horizon Documentation](https://laravel.com/docs/12.x/horizon)
- [Inertia SSR Documentation](https://inertiajs.com/server-side-rendering)
- [Docker Multi-stage Builds](https://docs.docker.com/build/building/multi-stage/)
- [Docker Compose Health Checks](https://docs.docker.com/compose/compose-file/05-services/#healthcheck)
- [Laravel Boost Guidelines](/CLAUDE.md)

---

## Timeline

**Total Estimated Time:** 30-45 minutes

- Create files: 10 minutes
- Update existing files: 15 minutes
- Test and verify: 10 minutes
- Fix any issues: 10 minutes buffer

---

**Plan Created:** 2025-11-08
**Implementation Status:** Ready to execute
**Approved By:** User
**Next Action:** Execute implementation
