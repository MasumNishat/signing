# Claude Code Prompts - DocuSign eSignature API Implementation

## Purpose
This document provides ready-to-use prompts for Claude Code to implement tasks efficiently with full context. Each prompt includes references to specific documentation files and line numbers.

---

## How to Use These Prompts

### For Developers
1. Copy the prompt for the task you want to implement
2. Paste it directly into Claude Code
3. Claude will read the referenced files and implement the task
4. Review and test the implementation

### For Claude Code
Each prompt contains:
- Task description
- Documentation references with line numbers
- Expected deliverables
- Testing requirements
- Success criteria

---

## Phase 1: Project Foundation & Core Infrastructure

### Session Starter Prompt

```
I'm starting Phase 1 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to understand project status
2. Read docs/02-TASK-LIST.md lines 15-90 for Phase 1 overview
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md lines 45-400 for detailed task information
4. Read docs/05-IMPLEMENTATION-GUIDELINES.md sections 1-3 for architecture and coding standards
5. Confirm you understand Phase 1 objectives and are ready to start with task T1.1.1

Technology stack: Laravel 12+, PostgreSQL 16+, Redis, Horizon
```

---

### T1.1.1: Initialize Laravel 12+ Project

```
Implement task T1.1.1: Initialize Laravel 12+ Project

Context:
- Read docs/03-DETAILED-TASK-BREAKDOWN.md lines 52-70
- Read docs/05-IMPLEMENTATION-GUIDELINES.md section 1.2 (Directory Structure)

Requirements:
1. Create new Laravel 12 project named "signing-api"
2. Install Laravel Horizon: composer require laravel/horizon
3. Install Laravel Passport: composer require laravel/passport
4. Setup custom directory structure as specified in guidelines
5. Create .env.example with all required variables
6. Initialize git repository with proper .gitignore

Deliverables:
- Laravel 12 installation
- Composer dependencies installed
- Custom directory structure created
- .env.example configured
- Git repository initialized

After completion:
1. Run: composer install
2. Run: php artisan key:generate
3. Verify: php artisan --version shows Laravel 12.x
4. Update CLAUDE.md marking T1.1.1 as complete
```

---

### T1.1.2: Configure PostgreSQL Database Connection

```
Implement task T1.1.2: Configure PostgreSQL Database Connection

Context:
- Read docs/03-DETAILED-TASK-BREAKDOWN.md lines 72-90
- Read docs/04-DATABASE-SCHEMA.dbml lines 1-20 for database overview
- Depends on: T1.1.1 completed

Requirements:
1. Update config/database.php with PostgreSQL configuration
2. Configure .env with PostgreSQL credentials:
   - DB_CONNECTION=pgsql
   - DB_HOST=127.0.0.1
   - DB_PORT=5432
   - DB_DATABASE=signing_api
   - DB_USERNAME=postgres
   - DB_PASSWORD=
3. Create database: signing_api
4. Test connection with: php artisan migrate

Deliverables:
- config/database.php configured
- .env updated with PostgreSQL settings
- Database created and connection tested

Testing:
1. Run: php artisan migrate
2. Verify no connection errors
3. Check database exists in PostgreSQL

After completion, update CLAUDE.md marking T1.1.2 as complete
```

---

### T1.1.3: Setup Laravel Horizon

```
Implement task T1.1.3: Setup Laravel Horizon for Queue Management

Context:
- Read docs/03-DETAILED-TASK-BREAKDOWN.md lines 92-115
- Read docs/05-IMPLEMENTATION-GUIDELINES.md section 1.1 (Architecture)
- Depends on: T1.1.1, T1.1.2 completed

Requirements:
1. Publish Horizon assets: php artisan horizon:install
2. Configure config/horizon.php with these queues:
   - default
   - notifications
   - billing
   - document-processing
3. Setup Redis connection in .env:
   - QUEUE_CONNECTION=redis
   - REDIS_HOST=127.0.0.1
   - REDIS_PORT=6379
4. Create supervisord configuration for production
5. Configure different environments (local, staging, production)

Deliverables:
- config/horizon.php configured
- Queue workers configured
- Supervisord config created
- Documentation for running Horizon

Testing:
1. Run: php artisan horizon
2. Dispatch test job: Job::dispatch()
3. Verify job processes in Horizon dashboard
4. Check localhost/horizon accessible

After completion, update CLAUDE.md marking T1.1.3 as complete
```

---

### T1.2.1: Design Complete Database Schema

```
Implement task T1.2.1: Create Initial Database Migrations

Context:
- Read docs/04-DATABASE-SCHEMA.dbml (entire file) for complete schema
- Read docs/05-IMPLEMENTATION-GUIDELINES.md section 4 (Database Guidelines)
- Depends on: T1.1.2 completed

Requirements:
1. Create migrations for core tables in this order:
   - plans table
   - accounts table
   - users table
   - permission_profiles table
   - user_authorizations table
2. Follow DBML schema exactly (docs/04-DATABASE-SCHEMA.dbml)
3. Add proper foreign keys with cascade rules
4. Add indexes as specified in DBML
5. Include soft deletes where specified
6. Add timestamps to all tables

Deliverables:
- Migration files created (5+ files)
- Proper foreign key constraints
- Indexes added for performance
- Soft deletes configured

Testing:
1. Run: php artisan migrate
2. Verify all tables created
3. Check foreign keys: \\d accounts in psql
4. Verify indexes exist

After completion, update CLAUDE.md marking T1.2.1 as complete
```

---

### T1.3.1: Implement OAuth 2.0 Authentication

```
Implement task T1.3.1: Implement OAuth 2.0 Authentication

Context:
- Read docs/03-DETAILED-TASK-BREAKDOWN.md lines 200-225
- Read docs/05-IMPLEMENTATION-GUIDELINES.md section 6.1 (Authentication)
- Read docs/04-DATABASE-SCHEMA.dbml lines 200-250 (OAuth tables)
- Depends on: T1.2.1 completed

Requirements:
1. Install Laravel Passport: php artisan passport:install
2. Configure Passport in app/Providers/AuthServiceProvider.php
3. Add Passport middleware to api routes
4. Create OAuth client management endpoints
5. Implement these grant types:
   - Authorization Code (primary)
   - Client Credentials (server-to-server)
   - Refresh Token
6. Configure token expiration:
   - Access token: 1 hour
   - Refresh token: 2 weeks

Deliverables:
- Passport installed and configured
- OAuth endpoints working
- Token generation and validation working
- API documentation for OAuth flow

Testing:
1. Create test client
2. Request access token
3. Use token in API request
4. Refresh token successfully
5. Test token expiration

Write tests in tests/Feature/Auth/OAuthTest.php

After completion, update CLAUDE.md marking T1.3.1 as complete
```

---

## Phase 2: Account Management Module

### Session Starter Prompt

```
I'm starting Phase 2 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 1 is complete
2. Read docs/02-TASK-LIST.md lines 91-180 for Phase 2 overview
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md lines 650-900 for Account Management details
4. Read docs/01-FEATURE-LIST.md lines 30-90 for Account Management features
5. Confirm you understand Phase 2 objectives and are ready to start with task T2.1.1

Phase 2 focuses on: Account CRUD operations, Settings Management, Password Rules, Tab Settings
```

---

### T2.1.1: Create Account Model and Migrations

```
Implement task T2.1.1: Create Account Model and Migrations

Context:
- Read docs/04-DATABASE-SCHEMA.dbml lines 15-80 (accounts table)
- Read docs/05-IMPLEMENTATION-GUIDELINES.md section 4.2 (Model Best Practices)
- Depends on: Phase 1 completed

Requirements:
1. Create Account model: php artisan make:model Account
2. Implement model based on DBML schema
3. Add these relationships:
   - hasMany: users
   - belongsTo: plan
   - hasOne: accountSettings
   - hasMany: brands
4. Add soft deletes
5. Add fillable fields
6. Add casts for boolean and datetime fields
7. Add scopes: active(), suspended(), closed()
8. Add accessors: getIsActiveAttribute()
9. Implement model events in boot()

Deliverables:
- app/Models/Account.php created
- All relationships defined
- Proper attributes configuration
- Model events implemented

Testing:
1. Create tests/Unit/Models/AccountTest.php
2. Test relationships
3. Test scopes
4. Test accessors
5. Test model events

After completion, update CLAUDE.md marking T2.1.1 as complete
```

---

### T2.1.2: Implement POST /v2.1/accounts

```
Implement task T2.1.2: Implement POST /v2.1/accounts (Create Account)

Context:
- Read docs/openapi.json lines 172-1398 for endpoint specification
- Read docs/03-DETAILED-TASK-BREAKDOWN.md lines 700-750
- Read docs/05-IMPLEMENTATION-GUIDELINES.md section 5 (API Design Principles)
- Depends on: T2.1.1 completed

Requirements:
1. Create AccountController: php artisan make:controller Api/V2_1/AccountController
2. Create CreateAccountRequest for validation
3. Create AccountCreationService in app/Services/Account/
4. Create AccountRepository in app/Repositories/Eloquent/
5. Implement account creation workflow:
   - Validate request data
   - Create account record
   - Create initial user
   - Assign admin permission profile
   - Setup default settings
   - Send welcome email (queue job)
   - Return account resource
6. Create AccountResource for API response

Deliverables:
- app/Http/Controllers/Api/V2_1/AccountController.php
- app/Http/Requests/Api/V2_1/CreateAccountRequest.php
- app/Services/Account/AccountCreationService.php
- app/Repositories/Eloquent/AccountRepository.php
- app/Http/Resources/AccountResource.php
- Route registered in routes/api/v2.1/accounts.php

Testing:
1. Create tests/Feature/Api/V2_1/AccountControllerTest.php
2. Test successful account creation
3. Test validation errors
4. Test duplicate email
5. Test permission checks
6. Test database transactions

Expected test coverage: 95%+

After completion, update CLAUDE.md marking T2.1.2 as complete
```

---

## Phase 3: Permission & Authorization Module

### Session Starter Prompt

```
I'm starting Phase 3 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 2 is complete
2. Read docs/02-TASK-LIST.md lines 181-230 for Phase 3 overview
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md lines 950-1100 for Permission details
4. Read docs/04-DATABASE-SCHEMA.dbml lines 280-350 for permission tables
5. Confirm you understand Phase 3 objectives

Phase 3 focuses on: Permission Profiles, User Authorization, Shared Access Management
```

---

## Phase 4: Branding Module

### Session Starter Prompt

```
I'm starting Phase 4 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 3 is complete
2. Read docs/02-TASK-LIST.md lines 231-280 for Phase 4 overview
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md lines 1100-1250 for Branding details
4. Read docs/04-DATABASE-SCHEMA.dbml lines 500-650 for branding tables
5. Read docs/01-FEATURE-LIST.md lines 100-150 for Branding features

Phase 4 focuses on: Brand Profiles, Brand Logos, Brand Resources, Watermarks
```

---

## Generic Task Prompt Template

```
Implement task [TASK_ID]: [TASK_NAME]

Context:
- Read docs/03-DETAILED-TASK-BREAKDOWN.md lines [START]-[END]
- Read docs/04-DATABASE-SCHEMA.dbml lines [START]-[END] (if applicable)
- Read docs/05-IMPLEMENTATION-GUIDELINES.md section [SECTION]
- Depends on: [DEPENDENCY_TASKS]

Requirements:
1. [Requirement 1]
2. [Requirement 2]
3. [Requirement 3]

Deliverables:
- [Deliverable 1]
- [Deliverable 2]

Testing:
1. [Test case 1]
2. [Test case 2]

Expected test coverage: [COVERAGE]%

After completion, update CLAUDE.md marking [TASK_ID] as complete
```

---

## Debugging and Issue Resolution Prompts

### When Tests Fail

```
Tests are failing for [FEATURE]. Please:

1. Read the test failure output
2. Read docs/05-IMPLEMENTATION-GUIDELINES.md section 7 (Testing Strategy)
3. Read the relevant test file: tests/[PATH]
4. Identify the issue
5. Fix the implementation
6. Re-run tests and verify pass
7. Explain what was wrong and how it was fixed
```

---

### When API Endpoint Doesn't Match Spec

```
The implemented endpoint doesn't match the OpenAPI specification. Please:

1. Read docs/openapi.json lines [START]-[END] for the spec
2. Compare with current implementation in [CONTROLLER_PATH]
3. Identify discrepancies:
   - Request validation
   - Response structure
   - Status codes
   - Error handling
4. Update implementation to match spec exactly
5. Update tests to match new implementation
6. Verify with tests
```

---

### When Database Schema Issues Occur

```
There's an issue with the database schema for [TABLE]. Please:

1. Read docs/04-DATABASE-SCHEMA.dbml lines [START]-[END]
2. Review the migration file: database/migrations/[FILE]
3. Check for:
   - Missing columns
   - Wrong data types
   - Missing indexes
   - Missing foreign keys
4. Create a new migration to fix the issue
5. Test the migration on fresh database
6. Update documentation if schema changed
```

---

## Code Review and Optimization Prompts

### Code Review Prompt

```
Please review the implementation of [FEATURE]. Check:

1. Read docs/05-IMPLEMENTATION-GUIDELINES.md section 3 (Coding Standards)
2. Review files:
   - [FILE_1]
   - [FILE_2]
3. Check for:
   - PSR-12 compliance
   - Type hints on all methods
   - Proper doc blocks
   - Error handling
   - Security issues
   - Performance issues
4. Provide detailed feedback with code examples
5. Suggest improvements
```

---

### Performance Optimization Prompt

```
Optimize performance for [FEATURE]. Please:

1. Read docs/05-IMPLEMENTATION-GUIDELINES.md section 8 (Performance)
2. Analyze current implementation in [PATH]
3. Check for:
   - N+1 query problems
   - Missing eager loading
   - Missing indexes
   - Missing caching
   - Inefficient queries
4. Implement optimizations
5. Add performance tests
6. Measure improvement
```

---

## Documentation Update Prompts

### Update API Documentation

```
Update API documentation for newly implemented endpoints in [FEATURE]:

1. Read docs/openapi.json to find relevant sections
2. Review implemented endpoints in routes/api/v2.1/[FILE]
3. Update OpenAPI spec with:
   - Request/response examples
   - All query parameters
   - All error responses
   - Authorization requirements
4. Verify documentation accuracy
5. Generate Swagger UI if needed
```

---

### Update CLAUDE.md After Phase Completion

```
Phase [NUMBER] is complete. Please:

1. Read CLAUDE.md
2. Move current phase to "Completed Phases" section
3. Update with:
   - Completion date
   - All completed tasks (checked)
   - All deliverables (with ✅)
   - Any important notes or deviations
4. Update "Current Phase" to next phase
5. Update "Project Statistics"
6. Add session log entry
7. Save and confirm update
```

---

## Emergency Prompts

### Rollback Changes

```
Need to rollback changes for [FEATURE]. Please:

1. Identify git commit before changes: git log
2. Review what changed: git diff [COMMIT]
3. Create new branch: git checkout -b rollback/[FEATURE]
4. Revert specific commits: git revert [COMMIT]
5. Test that system still works
6. Update CLAUDE.md noting the rollback
```

---

### Fix Production Issue

```
URGENT: Production issue with [FEATURE]. Please:

1. Read docs/05-IMPLEMENTATION-GUIDELINES.md section 9 (Error Handling)
2. Analyze the error: [ERROR_MESSAGE]
3. Review relevant code: [FILE_PATH]
4. Identify root cause
5. Create hotfix branch: hotfix/[ISSUE]
6. Implement fix with tests
7. Verify fix works
8. Document the issue and resolution
```

---

## Helpful Context Prompts

### Understanding Current State

```
What's the current state of the project? Please:

1. Read CLAUDE.md
2. Summarize:
   - Current phase and progress
   - Completed phases
   - Next tasks to be done
   - Any blockers or issues
3. Recommend next steps
```

---

### Planning Next Session

```
Plan the next development session. Please:

1. Read CLAUDE.md for current progress
2. Read docs/03-DETAILED-TASK-BREAKDOWN.md for task dependencies
3. Identify next 3-5 tasks that can be done
4. Check all dependencies are met
5. Estimate time for each task
6. Provide step-by-step plan for session
```

---

## Best Practices for Using These Prompts

### 1. Always Start with Context
Include references to specific documentation files and line numbers

### 2. Be Specific About Deliverables
List exactly what files should be created or modified

### 3. Include Testing Requirements
Specify test coverage and what should be tested

### 4. Update CLAUDE.md After Each Task
Keep the task tracker current

### 5. Reference the OpenAPI Spec
Always check the original specification for accuracy

### 6. Follow Implementation Guidelines
Reference docs/05-IMPLEMENTATION-GUIDELINES.md frequently

---

## Quick Reference Commands

### View Task Details
```
Read docs/03-DETAILED-TASK-BREAKDOWN.md lines [START]-[END] for task [TASK_ID]
```

### Check Database Schema
```
Read docs/04-DATABASE-SCHEMA.dbml lines [START]-[END] for [TABLE] structure
```

### Verify OpenAPI Spec
```
Read docs/openapi.json lines [START]-[END] for endpoint [ENDPOINT] specification
```

### Check Implementation Guidelines
```
Read docs/05-IMPLEMENTATION-GUIDELINES.md section [SECTION] for [TOPIC]
```

---

**Document Version:** 1.0
**Last Updated:** 2025-11-14
**Total Prompts:** 25+
**Coverage:** All 12 project phases
