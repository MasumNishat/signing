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
2. Read docs/02-TASK-LIST.md for Phase 1 overview (Foundation & Core Infrastructure)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md for detailed task information
4. Read docs/05-IMPLEMENTATION-GUIDELINES.md sections 1-3 for architecture and coding standards
5. Confirm you understand Phase 1 objectives and are ready to start with task T1.1.1

Technology stack: Laravel 12+, PostgreSQL 16+, Redis, Horizon

IMPORTANT CONTEXT:
- Total project scope: 419 API endpoints
- Total duration: 68-80 weeks solo (or 15-20 weeks with team of 5)
- Phase 1 is foundational for all subsequent phases
- Phase 2 (Envelopes) is THE MOST CRITICAL module with 125 endpoints
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

## Phase 2: Envelopes Module ⭐ MOST CRITICAL

### Session Starter Prompt

```
I'm starting Phase 2 of the DocuSign eSignature API project - THE MOST CRITICAL MODULE. Please:

1. Read CLAUDE.md to verify Phase 1 is complete
2. Read docs/02-TASK-LIST.md Phase 2 section (Envelopes Module)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 2 section for Envelope implementation details
4. Read docs/04-DATABASE-SCHEMA.dbml envelope-related tables (13 tables)
5. Read docs/01-FEATURE-LIST.md Envelopes category (125 endpoints)
6. Confirm you understand Phase 2 objectives and are ready to start with task T2.1.1

CRITICAL IMPORTANCE:
- Phase 2 implements 125 endpoints (30% of entire API)
- This is THE CORE FEATURE of DocuSign - creating and managing documents for digital signatures
- Most other modules depend on Envelopes being implemented
- Estimated duration: 14 weeks (560 hours)
- Must be completed before Templates, Bulk Envelopes, and many other features

Phase 2 focuses on:
- Envelope CRUD operations (create, read, update, delete, list)
- Envelope Documents (upload, convert, store, retrieve)
- Envelope Recipients (signers, CC, certified delivery, routing)
- Envelope Tabs (30+ tab types: signatures, text, dates, checkboxes, etc.)
- Envelope Workflows (routing, conditional logic, notifications)
- Envelope Status Management (sent, delivered, completed, voided)
- Envelope Audit Trail
```

---

### T2.1.1: Create Envelope Model and Relationships

```
Implement task T2.1.1: Create Envelope Model and Relationships

Context:
- Read docs/04-DATABASE-SCHEMA.dbml envelope-related tables (13 tables including envelopes, envelope_documents, envelope_recipients, envelope_tabs, etc.)
- Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 2 section for T2.1.1 details
- Read docs/05-IMPLEMENTATION-GUIDELINES.md section 4.2 (Model Best Practices)
- Depends on: Phase 1 completed

Requirements:
1. Create Envelope model: php artisan make:model Envelope
2. Create related models:
   - EnvelopeDocument
   - EnvelopeRecipient
   - EnvelopeTab
   - EnvelopeCustomField
   - EnvelopeAuditEvent
   - EnvelopeWorkflow
   - EnvelopeAttachment
3. Add relationships to Envelope model:
   - hasMany: documents, recipients, customFields, auditEvents, attachments
   - hasOne: workflow, lock
   - belongsTo: account, sender (user)
   - hasManyThrough: tabs (through recipients)
4. Add soft deletes
5. Add fillable fields from DBML schema
6. Add casts for:
   - status => EnvelopeStatusEnum
   - sent_date_time, completed_date_time, etc. => datetime
   - is_signature_provided => boolean
7. Add scopes: byStatus(), bySender(), byDateRange(), active()
8. Add accessors: getIsCompleteAttribute(), getCanEditAttribute()
9. Implement model events:
   - creating: generate envelope_id
   - updated: create audit event
   - statusChanged: trigger notifications

Deliverables:
- app/Models/Envelope.php created
- app/Models/EnvelopeDocument.php created
- app/Models/EnvelopeRecipient.php created
- app/Models/EnvelopeTab.php created
- Additional envelope-related models created
- All relationships defined
- Status enum created
- Model events implemented

Testing:
1. Create tests/Unit/Models/EnvelopeTest.php
2. Test all relationships
3. Test status transitions
4. Test scopes and accessors
5. Test model events
6. Test envelope_id generation

After completion, update CLAUDE.md marking T2.1.1 as complete
```

---

### T2.1.2: Implement POST /v2.1/accounts/{accountId}/envelopes (Create Envelope)

```
Implement task T2.1.2: Implement POST /v2.1/accounts/{accountId}/envelopes (Create Envelope)

Context:
- Read docs/openapi.json search for "POST.*envelopes" endpoint specification
- Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 2, T2.1.2 section
- Read docs/05-IMPLEMENTATION-GUIDELINES.md section 5 (API Design Principles)
- Read docs/04-DATABASE-SCHEMA.dbml envelope tables
- Depends on: T2.1.1 completed

CRITICAL: This is THE MOST IMPORTANT endpoint in the entire API - creating envelopes with documents and recipients for signing.

Requirements:
1. Create EnvelopeController: php artisan make:controller Api/V2_1/EnvelopeController
2. Create CreateEnvelopeRequest for validation:
   - Validate emailSubject, emailBlurb
   - Validate documents array (documentId, name, documentBase64 or fileExtension + remoteUrl)
   - Validate recipients object (signers, carbonCopies, certifiedDeliveries, etc.)
   - Validate tabs for each recipient
   - Validate status (created or sent)
3. Create EnvelopeCreationService in app/Services/Envelope/
4. Create EnvelopeRepository in app/Repositories/Eloquent/
5. Implement envelope creation workflow:
   a. Validate request data
   b. Generate unique envelope_id
   c. Create envelope record with status='created'
   d. Process and store documents:
      - Decode base64 documents
      - Store in configured storage (S3 or local)
      - Convert non-PDF documents to PDF (queue job)
      - Store document hashes for integrity
   e. Create recipient records:
      - Extract signers, carbonCopies, etc.
      - Set routing_order
      - Set authentication requirements
   f. Create tab records for each recipient:
      - signHereTabs, initialHereTabs, dateSignedTabs
      - textTabs, checkboxTabs, radioGroupTabs
      - Calculate and validate tab positions
   g. If status='sent':
      - Change envelope status to 'sent'
      - Queue notification emails to recipients
      - Set sent_date_time
   h. Create audit event
   i. Return envelope resource
6. Create EnvelopeResource for API response
7. Create DocumentStorageService for file handling
8. Create RecipientNotificationJob for email sending

Request Example:
```json
{
  "emailSubject": "Please sign this document",
  "emailBlurb": "Attached is the contract for your review and signature",
  "status": "sent",
  "documents": [
    {
      "documentId": "1",
      "name": "Contract.pdf",
      "documentBase64": "JVBERi0xLjQKJeLj..."
    }
  ],
  "recipients": {
    "signers": [
      {
        "email": "john.doe@example.com",
        "name": "John Doe",
        "recipientId": "1",
        "routingOrder": "1",
        "tabs": {
          "signHereTabs": [
            {
              "xPosition": "100",
              "yPosition": "150",
              "documentId": "1",
              "pageNumber": "1"
            }
          ]
        }
      }
    ]
  }
}
```

Deliverables:
- app/Http/Controllers/Api/V2_1/EnvelopeController.php
- app/Http/Requests/Api/V2_1/CreateEnvelopeRequest.php
- app/Services/Envelope/EnvelopeCreationService.php
- app/Services/Envelope/DocumentStorageService.php
- app/Repositories/Eloquent/EnvelopeRepository.php
- app/Http/Resources/EnvelopeResource.php
- app/Jobs/ConvertDocumentToPdfJob.php
- app/Jobs/SendRecipientNotificationJob.php
- Route registered in routes/api/v2.1/envelopes.php

Testing:
1. Create tests/Feature/Api/V2_1/EnvelopeControllerTest.php
2. Test successful envelope creation with status='created'
3. Test successful envelope creation with status='sent' (verify emails queued)
4. Test with multiple documents
5. Test with multiple recipients (signers, CC)
6. Test with multiple tab types
7. Test validation errors:
   - Missing required fields
   - Invalid document format
   - Invalid tab positions
   - Invalid recipient email
8. Test permission checks (user must belong to account)
9. Test database transactions (rollback on failure)
10. Test document storage (verify files saved)
11. Test document conversion queue (for non-PDF files)

Expected test coverage: 95%+

After completion, update CLAUDE.md marking T2.1.2 as complete

IMPLEMENTATION NOTES:
- Use database transactions to ensure atomicity
- Validate document size limits (configurable, default 25MB per document)
- Support multiple document formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX
- Generate unique envelope_id in format: "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
- Store documents with encryption at rest
- Log all envelope creation events for audit trail
- Handle errors gracefully with proper rollback
```

---

## Phase 3: Templates Module

### Session Starter Prompt

```
I'm starting Phase 3 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 2 (Envelopes) is complete
2. Read docs/02-TASK-LIST.md Phase 3 section (Templates Module)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 3 section
4. Read docs/04-DATABASE-SCHEMA.dbml template-related tables
5. Read docs/01-FEATURE-LIST.md Templates category (50 endpoints)
6. Confirm you understand Phase 3 objectives

Phase 3 Overview:
- Duration: 7 weeks (280 hours)
- Endpoints: 50
- Dependencies: Phase 2 (Envelopes) must be complete

Phase 3 focuses on:
- Template CRUD operations (create, read, update, delete, list)
- Template Documents (reusable document definitions)
- Template Recipients (roles, not specific people)
- Template Tabs (pre-positioned fields)
- Creating Envelopes from Templates
- Template matching and bulk sending
- Template locking and version control

KEY CONCEPT: Templates are reusable envelope definitions. Instead of specifying actual recipients,
templates define ROLES (e.g., "Signer 1", "Manager", "HR Representative"). When creating an envelope
from a template, users map these roles to actual people.
```

---

## Phase 4: Bulk Envelopes

### Session Starter Prompt

```
I'm starting Phase 4 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 2 (Envelopes) is complete
2. Read docs/02-TASK-LIST.md Phase 4 section (Bulk Envelopes)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 4 section
4. Read docs/04-DATABASE-SCHEMA.dbml bulk send tables
5. Read docs/01-FEATURE-LIST.md BulkEnvelopes category (12 endpoints)

Phase 4 Overview:
- Duration: 3 weeks (120 hours)
- Endpoints: 12
- Dependencies: Phase 2 (Envelopes) must be complete

Phase 4 focuses on:
- Bulk Send Lists (CSV upload of recipients)
- Bulk Send Batches (send same envelope to many recipients)
- Bulk Send Status tracking
- Bulk recipient validation
- Queued bulk send jobs
```

---

## Phase 5: Connect (Webhooks)

### Session Starter Prompt

```
I'm starting Phase 5 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 2 (Envelopes) is complete
2. Read docs/02-TASK-LIST.md Phase 5 section (Connect/Webhooks)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 5 section
4. Read docs/04-DATABASE-SCHEMA.dbml connect-related tables
5. Read docs/01-FEATURE-LIST.md Connect category (19 endpoints)

Phase 5 Overview:
- Duration: 4 weeks (160 hours)
- Endpoints: 19
- Dependencies: Phase 2 (Envelopes) must be complete

Phase 5 focuses on:
- Webhook Configuration (Connect configs)
- Event Publishing (envelope events trigger webhooks)
- Webhook Delivery (reliable delivery with retries)
- Event Logs and Monitoring
- OAuth configuration for webhooks
- Failure handling and retry logic
```

---

## Phase 6: Branding

### Session Starter Prompt

```
I'm starting Phase 6 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 1 is complete
2. Read docs/02-TASK-LIST.md Phase 6 section (Branding)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 6 section
4. Read docs/04-DATABASE-SCHEMA.dbml branding tables
5. Read docs/01-FEATURE-LIST.md Branding category (17 endpoints)

Phase 6 Overview:
- Duration: 4 weeks (160 hours)
- Endpoints: 17
- Dependencies: Phase 1 complete

Phase 6 focuses on:
- Brand Profiles (company branding)
- Brand Logos (custom logos for emails and envelopes)
- Brand Resources (images, colors, fonts)
- Email Branding (custom email templates)
- Watermarks
```

---

## Phase 7: Billing Module

### Session Starter Prompt

```
I'm starting Phase 7 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 1 is complete
2. Read docs/02-TASK-LIST.md Phase 7 section (Billing)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 7 section
4. Read docs/04-DATABASE-SCHEMA.dbml billing tables
5. Read docs/01-FEATURE-LIST.md Billing category (26 endpoints)

Phase 7 Overview:
- Duration: 5 weeks (200 hours)
- Endpoints: 26
- Dependencies: Phase 1 complete

Phase 7 focuses on:
- Invoice Management (generate, list, retrieve)
- Billing Plans (subscription management)
- Payment Processing (payment methods, charges)
- Usage Tracking (API calls, envelopes sent, storage)
- Billing History and Reports
```

---

## Phase 8: Workspaces & Folders

### Session Starter Prompt

```
I'm starting Phase 8 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 2 (Envelopes) is complete
2. Read docs/02-TASK-LIST.md Phase 8 section (Workspaces & Folders)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 8 section
4. Read docs/04-DATABASE-SCHEMA.dbml workspace and folder tables
5. Read docs/01-FEATURE-LIST.md Workspaces (16 endpoints) and Folders (15 endpoints)

Phase 8 Overview:
- Duration: 4 weeks (160 hours)
- Endpoints: 31 (Workspaces: 16, Folders: 15)
- Dependencies: Phase 2 complete

Phase 8 focuses on:
- Workspace Management (collaborative document workspaces)
- Workspace Folders (organize files within workspaces)
- Workspace Files (upload, download, version control)
- Envelope Folders (organize envelopes)
- Folder permissions and sharing
```

---

## Phase 9: PowerForms

### Session Starter Prompt

```
I'm starting Phase 9 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 2 & 3 (Envelopes & Templates) are complete
2. Read docs/02-TASK-LIST.md Phase 9 section (PowerForms)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 9 section
4. Read docs/04-DATABASE-SCHEMA.dbml powerform tables
5. Read docs/01-FEATURE-LIST.md PowerForms category (8 endpoints)

Phase 9 Overview:
- Duration: 3 weeks (120 hours)
- Endpoints: 8
- Dependencies: Phase 2 & 3 complete

Phase 9 focuses on:
- PowerForm Management (public forms for signing)
- PowerForm Templates (base templates for forms)
- PowerForm Submissions (track form submissions)
- Anonymous signing via public links
- PowerForm analytics
```

---

## Phase 10: Advanced Features

### Session Starter Prompt

```
I'm starting Phase 10 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify previous phases are complete
2. Read docs/02-TASK-LIST.md Phase 10 section (Advanced Features)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 10 section
4. Read docs/04-DATABASE-SCHEMA.dbml relevant tables
5. Read docs/01-FEATURE-LIST.md for multiple categories

Phase 10 Overview:
- Duration: 6 weeks (240 hours)
- Endpoints: 45+
- Multiple categories

Phase 10 focuses on:
- Custom Tabs (user-defined tab types)
- Notary (electronic notarization features)
- Seals & Signatures (signature management, signature providers)
- ChunkedUploads (large file upload support)
- Groups (user groups and permissions)
- Organization Settings (org-wide configurations)
```

---

## Phase 11: Reporting & Logs

### Session Starter Prompt

```
I'm starting Phase 11 of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify Phase 1 is complete
2. Read docs/02-TASK-LIST.md Phase 11 section (Reporting & Logs)
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 11 section
4. Read docs/04-DATABASE-SCHEMA.dbml log and settings tables
5. Read docs/01-FEATURE-LIST.md Diagnostics, Accounts (settings), Users (settings)

Phase 11 Overview:
- Duration: 4 weeks (160 hours)
- Endpoints: 15+
- Dependencies: Phase 1 complete

Phase 11 focuses on:
- Diagnostics & Health Checks
- Request Logs (API request logging and analysis)
- Account Settings Management (100+ settings)
- User Settings Management
- System monitoring and alerting
```

---

## Phase 12: Testing, Optimization & Deployment

### Session Starter Prompt

```
I'm starting Phase 12 - the FINAL phase of the DocuSign eSignature API project. Please:

1. Read CLAUDE.md to verify all previous phases (1-11) are complete
2. Read docs/02-TASK-LIST.md Phase 12 section
3. Read docs/03-DETAILED-TASK-BREAKDOWN.md Phase 12 section
4. Read docs/05-IMPLEMENTATION-GUIDELINES.md all sections

Phase 12 Overview:
- Duration: 8 weeks (320 hours)
- Focus: Quality, Performance, Security, Deployment
- This is the final phase before production

Phase 12 focuses on:
1. Comprehensive Testing (4 weeks)
   - End-to-end integration tests
   - Load testing and stress testing
   - Security penetration testing
   - User acceptance testing
   - Test all 419 endpoints
   - Achieve 85%+ overall code coverage

2. Performance Optimization (2 weeks)
   - Database query optimization
   - Caching strategy implementation
   - CDN configuration
   - API response time optimization
   - Queue optimization

3. Security Audit (1 week)
   - Security review of all endpoints
   - Vulnerability scanning
   - OAuth security review
   - Data encryption audit
   - Compliance verification (SOC 2, GDPR)

4. Deployment & Documentation (1 week)
   - Production environment setup
   - CI/CD pipeline finalization
   - API documentation generation
   - User guides and tutorials
   - Monitoring and alerting setup
   - Disaster recovery procedures
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

**Document Version:** 2.0
**Last Updated:** 2025-11-14
**Total Prompts:** 40+ (covering all 12 phases)
**Coverage:** Complete scope - 419 API endpoints
**Project Duration:** 68-80 weeks solo (15-20 weeks with team of 5)

## Summary of Updates (v2.0)

### Scope Correction
- Updated from incomplete 90-endpoint scope to complete 419-endpoint scope
- Phase 2 now correctly focuses on Envelopes (125 endpoints) - THE MOST CRITICAL MODULE
- All phase descriptions updated with accurate endpoint counts and durations

### Key Changes
1. **Phase 1:** Maintained as Foundation (220 hours, 5.5 weeks)
2. **Phase 2:** Changed from "Account Management" to "Envelopes Module" - THE CORE FEATURE
   - 125 endpoints (30% of entire API)
   - 560 hours (14 weeks)
   - Detailed prompts for envelope creation, documents, recipients, tabs
3. **Phase 3:** Changed to "Templates Module" (50 endpoints, 7 weeks)
4. **Phase 4:** "Bulk Envelopes" (12 endpoints, 3 weeks)
5. **Phase 5:** "Connect/Webhooks" (19 endpoints, 4 weeks)
6. **Phase 6-12:** Added comprehensive prompts for all remaining phases

### Critical Context Added
All prompts now include:
- Correct endpoint counts per phase
- Accurate time estimates (68-80 weeks solo)
- Phase dependencies
- References to correct documentation sections
- Emphasis on Envelopes as the most critical module
