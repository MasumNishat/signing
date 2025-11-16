# Complete Task List - Option 4: Full API Implementation (100%)

**Goal:** Implement all remaining 61 endpoints to achieve 100% API completion
**Current Status:** 358/419 endpoints (85.4%)
**Target Status:** 419/419 endpoints (100%)
**Estimated Duration:** 9 weeks (220 hours)
**Team Size:** 1-2 developers

---

## Overview

### Implementation Phases
- **Phase 1:** Quick Wins (2 weeks, 80 hours) - 27 endpoints
- **Phase 2:** Envelope/Template Parity (3 weeks, 120 hours) - 65 endpoints
- **Phase 3:** Specialized Features (4 weeks, 160 hours) - 16 endpoints

### Deliverables Summary
- **Total Endpoints:** 61 new endpoints
- **Controllers:** 8 new controllers
- **Services:** 6 new services
- **Models:** 4 new models (CustomTab, NotarySession, CloudProvider, EmailArchive)
- **Migrations:** 8 new migrations
- **Frontend Pages:** 12 new pages
- **Frontend Components:** 25 new components
- **Tests:** 180+ new tests

---

## Phase 1: Quick Wins (2 weeks, 80 hours)

**Goal:** Implement high-value features with immediate user impact
**Endpoints:** 27 (Template tabs: 8 + Document visibility: 6 + Captive recipients: 5 + Custom tabs: 8)
**Completion After Phase:** 385/419 (91.9%)

---

### Phase 1.1: Template Tab Management (3 days, 24 hours)

**Objective:** Enable per-recipient tab assignment in templates (like envelopes)

#### Backend Tasks (2 days, 16 hours)

**1.1.1 Create TemplateTabController** (4 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/TemplateTabController.php`
- [ ] Implement 8 methods:
  - `getDocumentTabs()` - GET /templates/{id}/documents/{docId}/tabs
  - `addDocumentTabs()` - POST /templates/{id}/documents/{docId}/tabs
  - `updateDocumentTabs()` - PUT /templates/{id}/documents/{docId}/tabs
  - `deleteDocumentTabs()` - DELETE /templates/{id}/documents/{docId}/tabs
  - `getRecipientTabs()` - GET /templates/{id}/recipients/{recipId}/tabs
  - `addRecipientTabs()` - POST /templates/{id}/recipients/{recipId}/tabs
  - `updateRecipientTabs()` - PUT /templates/{id}/recipients/{recipId}/tabs
  - `deleteRecipientTabs()` - DELETE /templates/{id}/recipients/{recipId}/tabs

**1.1.2 Update TemplateService** (4 hours)
- [ ] Add `getDocumentTabs(templateId, documentId)` method
- [ ] Add `updateDocumentTabs(templateId, documentId, tabs)` method
- [ ] Add `getRecipientTabs(templateId, recipientId)` method
- [ ] Add `updateRecipientTabs(templateId, recipientId, tabs)` method
- [ ] Add validation for tab types (27 types)
- [ ] Add positioning logic (absolute vs anchor)

**1.1.3 Create Routes** (2 hours)
- [ ] Add 8 routes to `routes/api/v2.1/templates.php`
- [ ] Add middleware: `auth:api`, `check.account.access`, `check.permission:templates.update`
- [ ] Test route registration with `php artisan route:list`

**1.1.4 Write Tests** (6 hours)
- [ ] Unit tests: TemplateTabControllerTest.php (8 tests)
- [ ] Feature tests: TemplateTabManagementTest.php (12 tests)
- [ ] Test all 27 tab types
- [ ] Test validation, permissions, not found cases

#### Frontend Tasks (1 day, 8 hours)

**1.1.5 Extend Template Editor** (6 hours)
- [ ] Update `resources/views/templates/edit.blade.php`
- [ ] Add "Assign Fields" button per recipient
- [ ] Add tab assignment modal with Alpine.js
- [ ] Implement drag-and-drop field assignment
- [ ] Add tab grouping by recipient
- [ ] Show assigned tabs per recipient in summary

**1.1.6 Add Tab Assignment Component** (2 hours)
- [ ] Create Alpine.js component `templateTabAssignment`
- [ ] Implement `assignTabToRecipient(tabId, recipientId)` method
- [ ] Implement `removeTabFromRecipient(tabId, recipientId)` method
- [ ] Add visual indicators for assigned tabs

**Deliverables:**
- ✅ 8 new API endpoints
- ✅ TemplateTabController (320 lines)
- ✅ TemplateService (+180 lines)
- ✅ 8 new routes
- ✅ 20 tests
- ✅ Template editor with tab assignment UI

---

### Phase 1.2: Document Visibility Controls (2 days, 16 hours)

**Objective:** Allow hiding specific documents from specific recipients

#### Backend Tasks (1 day, 8 hours)

**1.2.1 Create DocumentVisibilityController** (3 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/DocumentVisibilityController.php`
- [ ] Implement 6 methods:
  - `getEnvelopeVisibility()` - GET /envelopes/{id}/recipients/document_visibility
  - `updateEnvelopeVisibility()` - PUT /envelopes/{id}/recipients/document_visibility
  - `getEnvelopeRecipientVisibility()` - GET /envelopes/{id}/recipients/{recipId}/document_visibility
  - `updateEnvelopeRecipientVisibility()` - PUT /envelopes/{id}/recipients/{recipId}/document_visibility
  - `getTemplateRecipientVisibility()` - GET /templates/{id}/recipients/{recipId}/document_visibility
  - `updateTemplateRecipientVisibility()` - PUT /templates/{id}/recipients/{recipId}/document_visibility

**1.2.2 Add Migration for Visibility Field** (1 hour)
- [ ] Create migration: `add_document_visibility_to_envelope_recipients_table`
- [ ] Add JSONB column: `document_visibility` (array of document IDs)
- [ ] Add index on `document_visibility` for performance
- [ ] Run migration and test

**1.2.3 Update Models** (2 hours)
- [ ] Update `EnvelopeRecipient` model - add `$casts` for document_visibility
- [ ] Add helper method: `canViewDocument($documentId)`
- [ ] Add scope: `scopeWithDocumentAccess($query, $documentId)`

**1.2.4 Write Tests** (2 hours)
- [ ] Unit tests: DocumentVisibilityControllerTest.php (6 tests)
- [ ] Feature tests: DocumentVisibilityTest.php (8 tests)
- [ ] Test visibility enforcement during signing

#### Frontend Tasks (1 day, 8 hours)

**1.2.5 Add Visibility Controls to Recipient Editor** (5 hours)
- [ ] Update `resources/views/envelopes/edit.blade.php`
- [ ] Add "Document Access" section per recipient
- [ ] Add checkboxes for each document
- [ ] Default: All documents visible
- [ ] Show icon for restricted access

**1.2.6 Add Visibility Controls to Template Editor** (3 hours)
- [ ] Update `resources/views/templates/edit.blade.php`
- [ ] Same UI as envelope editor
- [ ] Save visibility settings with template

**Deliverables:**
- ✅ 6 new API endpoints
- ✅ DocumentVisibilityController (280 lines)
- ✅ 1 migration
- ✅ Model updates
- ✅ 14 tests
- ✅ Visibility controls in envelope/template editors

---

### Phase 1.3: Captive Recipients (2 days, 16 hours)

**Objective:** Enable in-person signing workflows

#### Backend Tasks (1.5 days, 12 hours)

**1.3.1 Enhance CaptiveRecipient Model** (2 hours)
- [ ] Update `app/Models/CaptiveRecipient.php`
- [ ] Add fields: `client_user_id`, `embedded_recipient_start_url`
- [ ] Add helper methods: `isEmbedded()`, `generateStartUrl()`
- [ ] Add validation for email vs embedded

**1.3.2 Create CaptiveRecipientService** (4 hours)
- [ ] Create `app/Services/CaptiveRecipientService.php`
- [ ] Implement 5 methods:
  - `createCaptive($envelopeId, $data)` - Create captive recipient
  - `updateCaptive($captiveId, $data)` - Update captive recipient
  - `getCaptives($envelopeId)` - List captive recipients
  - `deleteCaptive($captiveId)` - Delete captive recipient
  - `generateEmbeddedUrl($captiveId, $returnUrl)` - Generate signing URL

**1.3.3 Update RecipientController** (3 hours)
- [ ] Add 5 new methods to `RecipientController`
- [ ] Implement captive-specific validation
- [ ] Add routes to `routes/api/v2.1/recipients.php`

**1.3.4 Write Tests** (3 hours)
- [ ] Unit tests: CaptiveRecipientServiceTest.php (10 tests)
- [ ] Feature tests: CaptiveRecipientTest.php (12 tests)
- [ ] Test embedded URL generation
- [ ] Test in-person signing flow

#### Frontend Tasks (0.5 days, 4 hours)

**1.3.5 Add Captive Recipient UI** (4 hours)
- [ ] Update `resources/views/recipients/create.blade.php`
- [ ] Add "Recipient Type" radio buttons: Remote / In-Person (Captive)
- [ ] For captive: Show "Client User ID" field instead of email
- [ ] Add "Generate Signing Link" button
- [ ] Show embedded signing URL with copy button

**Deliverables:**
- ✅ 5 new API endpoints
- ✅ CaptiveRecipientService (220 lines)
- ✅ RecipientController (+120 lines)
- ✅ 22 tests
- ✅ Captive recipient UI in envelope editor

---

### Phase 1.4: Custom Tabs Module (3 days, 24 hours)

**Objective:** Create reusable custom field library

#### Backend Tasks (2 days, 16 hours)

**1.4.1 Create CustomTab Model & Migration** (3 hours)
- [ ] Create migration: `create_custom_tabs_table`
- [ ] Fields: id, account_id, name, type, label, required, shared, configuration (JSONB)
- [ ] Create `app/Models/CustomTab.php`
- [ ] Add relationships: belongsTo(Account)
- [ ] Add validation rules

**1.4.2 Create CustomTabService** (5 hours)
- [ ] Create `app/Services/CustomTabService.php`
- [ ] Implement CRUD methods:
  - `list($accountId, $filters)` - List custom tabs with filtering
  - `get($customTabId)` - Get specific custom tab
  - `create($accountId, $data)` - Create custom tab
  - `update($customTabId, $data)` - Update custom tab
  - `delete($customTabId)` - Delete custom tab
- [ ] Add `instantiate($customTabId)` - Convert to envelope tab
- [ ] Add validation for 27 tab types

**1.4.3 Create CustomTabController** (4 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/CustomTabController.php`
- [ ] Implement 8 methods:
  - `index()` - List custom tabs
  - `store()` - Create custom tab
  - `show()` - Get custom tab
  - `update()` - Update custom tab
  - `destroy()` - Delete custom tab
  - `instantiate()` - Use in envelope/template
  - `share()` - Share with account users
  - `unshare()` - Remove sharing

**1.4.4 Create Routes** (1 hour)
- [ ] Create `routes/api/v2.1/custom_tabs.php`
- [ ] Add 8 routes with middleware
- [ ] Register in routes/api.php

**1.4.5 Write Tests** (3 hours)
- [ ] Unit tests: CustomTabServiceTest.php (15 tests)
- [ ] Feature tests: CustomTabTest.php (18 tests)
- [ ] Test instantiation into envelopes/templates
- [ ] Test sharing permissions

#### Frontend Tasks (1 day, 8 hours)

**1.4.6 Create Custom Tab Library Page** (5 hours)
- [ ] Create `resources/views/custom_tabs/index.blade.php`
- [ ] Grid layout with tab cards
- [ ] Search and filter (type, shared, created by)
- [ ] CRUD buttons per tab
- [ ] Preview tab configuration

**1.4.7 Create Custom Tab CRUD Modals** (3 hours)
- [ ] Create modal component for create/edit
- [ ] Tab type selector (27 types)
- [ ] Configuration form (label, required, validation, etc.)
- [ ] Preview pane showing how tab will appear
- [ ] Share toggle and user selection

**Deliverables:**
- ✅ 8 new API endpoints
- ✅ CustomTab model (120 lines)
- ✅ CustomTabService (380 lines)
- ✅ CustomTabController (340 lines)
- ✅ 8 routes
- ✅ 33 tests
- ✅ Custom tab library page
- ✅ 1 migration

---

### Phase 1 Summary

**Endpoints Added:** 27
- Template tabs: 8
- Document visibility: 6
- Captive recipients: 5
- Custom tabs: 8

**Code Deliverables:**
- Controllers: 3 new (TemplateTab, DocumentVisibility, CustomTab)
- Services: 2 new (CaptiveRecipient, CustomTab)
- Models: 1 new (CustomTab)
- Migrations: 2 new
- Routes: 27 new
- Tests: 89 new tests
- Frontend: 4 enhanced pages, 1 new page

**Platform Status After Phase 1:** 385/419 (91.9%)

---

## Phase 2: Envelope/Template Parity (3 weeks, 120 hours)

**Goal:** Complete all document and recipient operations for envelopes/templates
**Endpoints:** 65 (Document ops: 30 + Recipient ops: 25 + Bulk ops: 10)
**Completion After Phase:** 450/419 (107%) - Over-deliver due to enhanced operations

---

### Phase 2.1: Envelope Document Operations (1 week, 40 hours)

**Objective:** Add advanced document editing within sent envelopes

#### Phase 2.1.1: Document Fields (2 days, 16 hours)

**2.1.1.1 Create DocumentFieldController** (5 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/DocumentFieldController.php`
- [ ] Implement 4 methods:
  - `getFields()` - GET /envelopes/{id}/documents/{docId}/fields
  - `createFields()` - POST /envelopes/{id}/documents/{docId}/fields
  - `updateFields()` - PUT /envelopes/{id}/documents/{docId}/fields
  - `deleteFields()` - DELETE /envelopes/{id}/documents/{docId}/fields

**2.1.1.2 Create DocumentFieldService** (6 hours)
- [ ] Create `app/Services/DocumentFieldService.php`
- [ ] Implement CRUD for document-level custom fields
- [ ] Add validation (name, value, type)
- [ ] Support field types: text, date, number, list
- [ ] Add field merging for PDF generation

**2.1.1.3 Add Routes & Tests** (5 hours)
- [ ] Add 4 routes to `routes/api/v2.1/documents.php`
- [ ] Unit tests: DocumentFieldServiceTest.php (8 tests)
- [ ] Feature tests: DocumentFieldTest.php (10 tests)

#### Phase 2.1.2: Document Pages (2 days, 16 hours)

**2.1.2.1 Create DocumentPageController** (5 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/DocumentPageController.php`
- [ ] Implement 8 methods:
  - `getPages()` - GET /envelopes/{id}/documents/{docId}/pages
  - `getPage()` - GET /envelopes/{id}/documents/{docId}/pages/{pageNum}
  - `deletePage()` - DELETE /envelopes/{id}/documents/{docId}/pages/{pageNum}
  - `getPageImage()` - GET /envelopes/{id}/documents/{docId}/pages/{pageNum}/page_image
  - `rotatePageImage()` - PUT /envelopes/{id}/documents/{docId}/pages/{pageNum}/page_image
  - `getPageTabs()` - GET /envelopes/{id}/documents/{docId}/pages/{pageNum}/tabs
  - `movePage()` - POST /envelopes/{id}/documents/{docId}/pages/{pageNum}/move
  - `insertPage()` - POST /envelopes/{id}/documents/{docId}/pages/insert

**2.1.2.2 Create DocumentPageService** (6 hours)
- [ ] Create `app/Services/DocumentPageService.php`
- [ ] Implement PDF page manipulation (requires PDF library)
- [ ] Add page rotation (90°, 180°, 270°)
- [ ] Add page deletion with tab reassignment
- [ ] Add page ordering
- [ ] Generate page thumbnails

**2.1.2.3 Add Routes & Tests** (5 hours)
- [ ] Add 8 routes
- [ ] Unit tests (12 tests)
- [ ] Feature tests (15 tests)

#### Phase 2.1.3: Document Tabs (1 day, 8 hours)

**2.1.3.1 Extend DocumentController** (4 hours)
- [ ] Add 4 methods to DocumentController:
  - `getDocumentTabs()` - GET /envelopes/{id}/documents/{docId}/tabs
  - `addDocumentTabs()` - POST /envelopes/{id}/documents/{docId}/tabs
  - `updateDocumentTabs()` - PUT /envelopes/{id}/documents/{docId}/tabs
  - `deleteDocumentTabs()` - DELETE /envelopes/{id}/documents/{docId}/tabs

**2.1.3.2 Update TabService** (2 hours)
- [ ] Add document-level tab management
- [ ] Support all 27 tab types
- [ ] Add tab grouping by document

**2.1.3.3 Add Routes & Tests** (2 hours)
- [ ] Add 4 routes
- [ ] Tests: 10 tests

#### Phase 2.1.4: HTML Definitions & Preview (2 days, 16 hours)

**2.1.4.1 Create HtmlDefinitionController** (5 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/HtmlDefinitionController.php`
- [ ] Implement 6 methods:
  - `getEnvelopeHtmlDef()` - GET /envelopes/{id}/documents/{docId}/html_definitions
  - `getTemplateHtmlDef()` - GET /templates/{id}/documents/{docId}/html_definitions
  - `getEnvelopeGlobalHtmlDef()` - GET /envelopes/{id}/html_definitions
  - `getTemplateGlobalHtmlDef()` - GET /templates/{id}/html_definitions
  - `previewEnvelopeResponsive()` - POST /envelopes/{id}/documents/{docId}/responsive_html_preview
  - `previewTemplateResponsive()` - POST /templates/{id}/documents/{docId}/responsive_html_preview

**2.1.4.2 Create HtmlDefinitionService** (6 hours)
- [ ] Create `app/Services/HtmlDefinitionService.php`
- [ ] Parse PDF to HTML conversion metadata
- [ ] Generate responsive HTML from definition
- [ ] Support custom CSS styling
- [ ] Add mobile preview rendering

**2.1.4.3 Add Routes & Tests** (5 hours)
- [ ] Add 6 routes
- [ ] Tests: 12 tests

**Phase 2.1 Deliverables:**
- ✅ 30 new API endpoints
- ✅ 3 new controllers (DocumentField, DocumentPage, HtmlDefinition)
- ✅ 3 new services
- ✅ 30 routes
- ✅ 67 tests

---

### Phase 2.2: Envelope Recipient Operations (1 week, 40 hours)

**Objective:** Advanced recipient-specific operations

#### Phase 2.2.1: Recipient Tabs (2 days, 16 hours)

**2.2.1.1 Create RecipientTabController** (6 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/RecipientTabController.php`
- [ ] Implement 8 methods for envelope + template recipient tabs
- [ ] Support all 27 tab types
- [ ] Add tab assignment per recipient

**2.2.1.2 Update TabService** (5 hours)
- [ ] Add recipient-specific tab logic
- [ ] Add tab inheritance (document → recipient)
- [ ] Add tab visibility per recipient

**2.2.1.3 Add Routes & Tests** (5 hours)
- [ ] Add 8 routes
- [ ] Tests: 16 tests

#### Phase 2.2.2: Carbon Copy & Agent Recipients (2 days, 16 hours)

**2.2.2.1 Enhance Recipient Model** (4 hours)
- [ ] Add carbon copy recipient type
- [ ] Add agent recipient type
- [ ] Add delegation logic
- [ ] Update routing order logic

**2.2.2.2 Update RecipientService** (6 hours)
- [ ] Add carbon copy notification logic
- [ ] Add agent delegation workflow
- [ ] Add recipient substitution
- [ ] Add offline recipient handling

**2.2.2.3 Add Routes & Tests** (6 hours)
- [ ] Add 6 routes for CC and agent operations
- [ ] Tests: 14 tests

#### Phase 2.2.3: Recipient Views & Embeds (1 day, 8 hours)

**2.2.3.1 Create RecipientViewController** (4 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/RecipientViewController.php`
- [ ] Generate recipient signing URLs with tokens
- [ ] Generate preview URLs
- [ ] Generate console view URLs

**2.2.3.2 Add Token Generation** (2 hours)
- [ ] HMAC-SHA256 token generation
- [ ] URL expiration (configurable)
- [ ] Return URL support

**2.2.3.3 Add Routes & Tests** (2 hours)
- [ ] Add 5 routes
- [ ] Tests: 8 tests

#### Phase 2.2.4: Recipient Document Visibility (2 days, 16 hours)

**2.2.4.1 Extend DocumentVisibilityController** (6 hours)
- [ ] Add envelope bulk visibility: PUT /envelopes/{id}/recipients/document_visibility
- [ ] Add per-recipient visibility for templates
- [ ] Add visibility inheritance from templates to envelopes

**2.2.4.2 Update Signing Interface** (6 hours)
- [ ] Enforce document visibility during signing
- [ ] Show only visible documents to recipient
- [ ] Add visibility indicator in UI

**2.2.4.3 Add Routes & Tests** (4 hours)
- [ ] Add 6 routes
- [ ] Tests: 12 tests

**Phase 2.2 Deliverables:**
- ✅ 25 new API endpoints
- ✅ 2 new controllers (RecipientTab, RecipientView)
- ✅ Service updates
- ✅ 25 routes
- ✅ 50 tests

---

### Phase 2.3: Bulk Operations (1 week, 40 hours)

**Objective:** Efficient bulk envelope management

#### Phase 2.3.1: Bulk Status Updates (2 days, 16 hours)

**2.3.1.1 Create BulkEnvelopeController** (6 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/BulkEnvelopeController.php`
- [ ] Implement:
  - `bulkStatusUpdate()` - PUT /envelopes/status (bulk)
  - `bulkVoid()` - POST /envelopes/bulk_void
  - `bulkResend()` - POST /envelopes/bulk_resend

**2.3.1.2 Create BulkEnvelopeService** (6 hours)
- [ ] Create `app/Services/BulkEnvelopeService.php`
- [ ] Implement queue-based bulk processing
- [ ] Add batch job: `ProcessBulkStatusUpdate`
- [ ] Add progress tracking
- [ ] Add error handling and rollback

**2.3.1.3 Add Routes & Tests** (4 hours)
- [ ] Add 3 routes
- [ ] Tests: 10 tests

#### Phase 2.3.2: Bulk Recipient Updates (2 days, 16 hours)

**2.3.2.1 Extend BulkEnvelopeController** (6 hours)
- [ ] Add methods:
  - `bulkRecipientUpdate()` - PUT /envelopes/bulk_recipients
  - `bulkRecipientResend()` - POST /envelopes/bulk_recipients/resend
  - `bulkRecipientRemove()` - DELETE /envelopes/bulk_recipients

**2.3.2.2 Update BulkEnvelopeService** (6 hours)
- [ ] Add bulk recipient logic
- [ ] Add queue job: `ProcessBulkRecipientUpdate`
- [ ] Handle routing order updates
- [ ] Add notifications

**2.3.2.3 Add Routes & Tests** (4 hours)
- [ ] Add 3 routes
- [ ] Tests: 10 tests

#### Phase 2.3.3: Bulk Document Operations (1 day, 8 hours)

**2.3.3.1 Extend BulkEnvelopeController** (4 hours)
- [ ] Add methods:
  - `bulkDocumentAdd()` - POST /envelopes/bulk_documents
  - `bulkDocumentReplace()` - PUT /envelopes/bulk_documents
  - `bulkDocumentDelete()` - DELETE /envelopes/bulk_documents
  - `bulkDocumentDownload()` - POST /envelopes/bulk_download

**2.3.3.2 Add Routes & Tests** (4 hours)
- [ ] Add 4 routes
- [ ] Tests: 8 tests

**Phase 2.3 Deliverables:**
- ✅ 10 new API endpoints
- ✅ 1 new controller (BulkEnvelope)
- ✅ 1 new service (BulkEnvelope)
- ✅ 2 queue jobs
- ✅ 10 routes
- ✅ 28 tests

---

### Phase 2 Summary

**Endpoints Added:** 65
- Document operations: 30
- Recipient operations: 25
- Bulk operations: 10

**Code Deliverables:**
- Controllers: 6 new
- Services: 5 new
- Queue Jobs: 2 new
- Routes: 65 new
- Tests: 145 new tests

**Platform Status After Phase 2:** 423/419 (100.95%)

---

## Phase 3: Specialized Features (4 weeks, 160 hours)

**Goal:** Implement specialized modules for enterprise/niche use cases
**Endpoints:** 16 (Notary: 8 + CloudStorage: 5 + EmailArchive: 3)
**Completion After Phase:** 439/419 (104.8%)

---

### Phase 3.1: Notary Module (2 weeks, 80 hours)

**Objective:** Enable electronic notarization workflows

#### Phase 3.1.1: Notary Infrastructure (1 week, 40 hours)

**3.1.1.1 Create NotarySession Model & Migration** (4 hours)
- [ ] Create migration: `create_notary_sessions_table`
- [ ] Fields: id, envelope_id, notary_user_id, signer_user_id, status, session_type, started_at, completed_at, journal_entry_id
- [ ] Create `app/Models/NotarySession.php`
- [ ] Add relationships: belongsTo(Envelope, User as notary, User as signer)
- [ ] Add status constants: pending, in_progress, completed, cancelled

**3.1.1.2 Create NotaryJournal Model & Migration** (4 hours)
- [ ] Create migration: `create_notary_journals_table`
- [ ] Fields: id, account_id, notary_session_id, document_type, signer_name, notarization_date, notary_seal, metadata (JSONB)
- [ ] Create `app/Models/NotaryJournal.php`
- [ ] Add compliance fields (required by state law)
- [ ] Add PDF generation for journal entries

**3.1.1.3 Create NotaryConfiguration Model** (3 hours)
- [ ] Create migration: `create_notary_configurations_table`
- [ ] Fields: id, account_id, enabled, provider, credentials (encrypted), settings (JSONB)
- [ ] Create `app/Models/NotaryConfiguration.php`
- [ ] Support providers: In-Person, Remote Online Notary (RON)

**3.1.1.4 Create NotaryService** (12 hours)
- [ ] Create `app/Services/NotaryService.php`
- [ ] Implement:
  - `createSession($envelopeId, $notaryUserId, $signerUserId)` - Start notary session
  - `getSession($sessionId)` - Get session details
  - `updateSession($sessionId, $data)` - Update session
  - `completeSession($sessionId, $journalData)` - Complete and journal
  - `cancelSession($sessionId, $reason)` - Cancel session
- [ ] Add identity verification (ID check, KBA)
- [ ] Add video recording integration (RON)
- [ ] Add electronic seal application

**3.1.1.5 Integrate with Third-Party Providers** (10 hours)
- [ ] Research RON providers (NotaryCam, Notarize, Proof)
- [ ] Create provider interface: `NotaryProviderInterface`
- [ ] Implement mock provider for testing
- [ ] Add webhook handlers for provider callbacks
- [ ] Add credential encryption

**3.1.1.6 Write Tests** (7 hours)
- [ ] Unit tests: NotaryServiceTest.php (18 tests)
- [ ] Feature tests: NotarySessionTest.php (20 tests)
- [ ] Test compliance requirements
- [ ] Test journal generation

#### Phase 3.1.2: Notary API & Frontend (1 week, 40 hours)

**3.1.2.1 Create NotaryController** (8 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/NotaryController.php`
- [ ] Implement 8 methods:
  - `getConfiguration()` - GET /accounts/{id}/notary/configuration
  - `updateConfiguration()` - PUT /accounts/{id}/notary/configuration
  - `createSession()` - POST /envelopes/{id}/notary/sessions
  - `getSession()` - GET /envelopes/{id}/notary/sessions/{sessionId}
  - `updateSession()` - PUT /envelopes/{id}/notary/sessions/{sessionId}
  - `completeSession()` - POST /envelopes/{id}/notary/sessions/{sessionId}/complete
  - `getJournal()` - GET /accounts/{id}/notary/journal
  - `downloadJournalPdf()` - GET /accounts/{id}/notary/journal/pdf

**3.1.2.2 Create Routes** (2 hours)
- [ ] Create `routes/api/v2.1/notary.php`
- [ ] Add 8 routes with middleware
- [ ] Add notary permission: `notary.sessions.manage`

**3.1.2.3 Create Notary Configuration Page** (10 hours)
- [ ] Create `resources/views/notary/configuration.blade.php`
- [ ] Enable/disable notary features
- [ ] Configure provider (In-Person or RON)
- [ ] Add provider credentials form
- [ ] Test connection to provider

**3.1.2.4 Create Notary Session UI** (12 hours)
- [ ] Create `resources/views/notary/session.blade.php`
- [ ] Start notary session button in envelope
- [ ] Identity verification flow (ID upload, selfie, KBA)
- [ ] Video call interface (if RON)
- [ ] Session recording indicator
- [ ] Complete session with journal entry

**3.1.2.5 Create Notary Journal Page** (6 hours)
- [ ] Create `resources/views/notary/journal.blade.php`
- [ ] Table of all notary sessions
- [ ] Filter by date, notary, document type
- [ ] View journal entry details
- [ ] Export journal to PDF

**3.1.2.6 Write Frontend Tests** (2 hours)
- [ ] Playwright tests: Notary configuration (5 tests)
- [ ] Playwright tests: Notary session flow (8 tests)

**Phase 3.1 Deliverables:**
- ✅ 8 new API endpoints
- ✅ 3 new models (NotarySession, NotaryJournal, NotaryConfiguration)
- ✅ 1 new service (NotaryService)
- ✅ 1 new controller (NotaryController)
- ✅ 3 migrations
- ✅ 8 routes
- ✅ 38 tests
- ✅ 3 frontend pages

---

### Phase 3.2: CloudStorage Module (1 week, 40 hours)

**Objective:** Integration with Google Drive, OneDrive, Dropbox

#### Phase 3.2.1: CloudStorage Infrastructure (3 days, 24 hours)

**3.2.1.1 Create CloudProvider Model & Migration** (3 hours)
- [ ] Create migration: `create_cloud_providers_table`
- [ ] Fields: id, user_id, provider_type, access_token (encrypted), refresh_token (encrypted), expires_at, folder_id
- [ ] Create `app/Models/CloudProvider.php`
- [ ] Add provider types: google_drive, onedrive, dropbox
- [ ] Add token refresh logic

**3.2.1.2 Create CloudStorageService** (10 hours)
- [ ] Create `app/Services/CloudStorageService.php`
- [ ] Implement provider interfaces:
  - `GoogleDriveProvider` - Google Drive API integration
  - `OneDriveProvider` - Microsoft Graph API integration
  - `DropboxProvider` - Dropbox API integration
- [ ] Implement methods:
  - `connect($userId, $provider, $authCode)` - OAuth connection
  - `disconnect($providerId)` - Remove connection
  - `listProviders($userId)` - List connected providers
  - `uploadDocument($providerId, $documentPath)` - Upload to cloud
  - `downloadDocument($providerId, $fileId)` - Download from cloud
- [ ] Add OAuth 2.0 flows for each provider
- [ ] Add automatic token refresh

**3.2.1.3 Write Tests** (5 hours)
- [ ] Unit tests: CloudStorageServiceTest.php (15 tests)
- [ ] Mock provider responses
- [ ] Test token refresh logic

**3.2.1.4 Setup Provider Apps** (6 hours)
- [ ] Create Google Cloud project and OAuth app
- [ ] Create Microsoft Azure app registration
- [ ] Create Dropbox app
- [ ] Configure redirect URIs
- [ ] Store credentials in .env

#### Phase 3.2.2: CloudStorage API & Frontend (2 days, 16 hours)

**3.2.2.1 Create CloudStorageController** (5 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/CloudStorageController.php`
- [ ] Implement 5 methods:
  - `listProviders()` - GET /users/{id}/cloud_storage
  - `connect()` - POST /users/{id}/cloud_storage/connect
  - `disconnect()` - DELETE /users/{id}/cloud_storage/{providerId}
  - `uploadToCloud()` - POST /envelopes/{id}/documents/{docId}/cloud_upload
  - `importFromCloud()` - POST /envelopes/{id}/documents/cloud_import

**3.2.2.2 Create Routes** (1 hour)
- [ ] Create `routes/api/v2.1/cloud_storage.php`
- [ ] Add 5 routes

**3.2.2.3 Create Cloud Storage Settings Page** (6 hours)
- [ ] Create `resources/views/settings/cloud_storage.blade.php`
- [ ] List connected providers with status indicators
- [ ] "Connect" buttons for each provider
- [ ] OAuth flow handling (redirect to provider, handle callback)
- [ ] Disconnect button with confirmation

**3.2.2.4 Add Cloud Upload to Document Uploader** (4 hours)
- [ ] Update `resources/views/documents/upload.blade.php`
- [ ] Add "Upload to Cloud" checkbox
- [ ] Provider selection dropdown
- [ ] Automatic upload after envelope sent

**Phase 3.2 Deliverables:**
- ✅ 5 new API endpoints
- ✅ 1 new model (CloudProvider)
- ✅ 1 new service (CloudStorageService with 3 provider implementations)
- ✅ 1 new controller (CloudStorageController)
- ✅ 1 migration
- ✅ 5 routes
- ✅ 15 tests
- ✅ 2 frontend pages

---

### Phase 3.3: EmailArchive Module (1 week, 40 hours)

**Objective:** Archive email notifications for compliance

#### Phase 3.3.1: EmailArchive Infrastructure (3 days, 24 hours)

**3.3.3.1 Create EmailArchive Model & Migration** (3 hours)
- [ ] Create migration: `create_email_archives_table`
- [ ] Fields: id, account_id, envelope_id, recipient_email, subject, body_html, body_text, sent_at, archived_at, metadata (JSONB)
- [ ] Create `app/Models/EmailArchive.php`
- [ ] Add relationships: belongsTo(Account, Envelope)
- [ ] Add search indexes

**3.3.3.2 Create EmailArchiveService** (8 hours)
- [ ] Create `app/Services/EmailArchiveService.php`
- [ ] Implement:
  - `archiveEmail($emailData)` - Archive sent email
  - `getArchive($accountId, $filters)` - Search archived emails
  - `exportArchive($accountId, $filters, $format)` - Export (CSV, PDF)
  - `purgeOldArchives($retentionDays)` - Cleanup old archives
- [ ] Add automatic archiving on email send
- [ ] Add retention policy enforcement
- [ ] Add encryption for email body

**3.3.3.3 Integrate with Email Sending** (8 hours)
- [ ] Update notification system to archive all emails
- [ ] Add queue job: `ArchiveEmailJob`
- [ ] Add listener: `EmailSentListener` → archive email
- [ ] Add configurable archiving (on/off per account)

**3.3.3.4 Write Tests** (5 hours)
- [ ] Unit tests: EmailArchiveServiceTest.php (10 tests)
- [ ] Feature tests: EmailArchiveTest.php (12 tests)
- [ ] Test archiving on email send
- [ ] Test retention policy

#### Phase 3.3.2: EmailArchive API & Frontend (2 days, 16 hours)

**3.3.3.5 Create EmailArchiveController** (4 hours)
- [ ] Create `app/Http/Controllers/Api/V2_1/EmailArchiveController.php`
- [ ] Implement 3 methods:
  - `getConfiguration()` - GET /accounts/{id}/email_archive/configuration
  - `updateConfiguration()` - PUT /accounts/{id}/email_archive/configuration
  - `search()` - GET /accounts/{id}/email_archive

**3.3.3.6 Create Routes** (1 hour)
- [ ] Create `routes/api/v2.1/email_archive.php`
- [ ] Add 3 routes

**3.3.3.7 Create Email Archive Configuration Page** (5 hours)
- [ ] Create `resources/views/settings/email_archive.blade.php`
- [ ] Enable/disable archiving toggle
- [ ] Retention period setting (days)
- [ ] Archive storage location
- [ ] Archive size and count statistics

**3.3.3.8 Create Email Archive Search Page** (6 hours)
- [ ] Create `resources/views/diagnostics/email_archive.blade.php`
- [ ] Search form (date range, recipient, subject, envelope)
- [ ] Results table with email preview
- [ ] View full email modal (HTML + text)
- [ ] Export to CSV/PDF

**Phase 3.3 Deliverables:**
- ✅ 3 new API endpoints
- ✅ 1 new model (EmailArchive)
- ✅ 1 new service (EmailArchiveService)
- ✅ 1 new controller (EmailArchiveController)
- ✅ 1 queue job
- ✅ 1 event listener
- ✅ 1 migration
- ✅ 3 routes
- ✅ 22 tests
- ✅ 2 frontend pages

---

### Phase 3 Summary

**Endpoints Added:** 16
- Notary: 8
- CloudStorage: 5
- EmailArchive: 3

**Code Deliverables:**
- Controllers: 3 new (Notary, CloudStorage, EmailArchive)
- Services: 3 new + 3 provider implementations
- Models: 5 new (NotarySession, NotaryJournal, NotaryConfiguration, CloudProvider, EmailArchive)
- Queue Jobs: 1 new
- Event Listeners: 1 new
- Migrations: 5 new
- Routes: 16 new
- Tests: 75 new tests
- Frontend Pages: 7 new pages

**Platform Status After Phase 3:** 439/419 (104.8%) - Complete!

---

## Final Deliverables Summary

### API Completion
- **Starting:** 358/419 endpoints (85.4%)
- **After Phase 1:** 385/419 endpoints (91.9%)
- **After Phase 2:** 423/419 endpoints (100.9%)
- **After Phase 3:** 439/419 endpoints (104.8%)
- **Total Added:** 81 new endpoints (61 from spec + 20 enhanced operations)

### Code Statistics
**Controllers Created:** 14
- Phase 1: TemplateTabController, DocumentVisibilityController, CustomTabController
- Phase 2: DocumentFieldController, DocumentPageController, HtmlDefinitionController, RecipientTabController, RecipientViewController, BulkEnvelopeController
- Phase 3: NotaryController, CloudStorageController, EmailArchiveController

**Services Created:** 11
- Phase 1: CaptiveRecipientService, CustomTabService
- Phase 2: DocumentFieldService, DocumentPageService, HtmlDefinitionService, BulkEnvelopeService
- Phase 3: NotaryService, CloudStorageService, EmailArchiveService

**Models Created:** 9
- Phase 1: CustomTab
- Phase 3: NotarySession, NotaryJournal, NotaryConfiguration, CloudProvider, EmailArchive

**Migrations Created:** 11

**Routes Created:** 81

**Tests Created:** 331
- Phase 1: 89 tests
- Phase 2: 145 tests
- Phase 3: 97 tests

**Frontend Pages:** 12 new pages
- Phase 1: Custom tab library (1 page)
- Phase 3: Notary configuration, notary session, notary journal, cloud storage settings, email archive configuration, email archive search (7 pages)

**Frontend Enhancements:** 4 pages enhanced
- Template editor (tab assignment, visibility)
- Envelope editor (captive recipients, visibility)
- Recipient editor (captive UI)
- Document uploader (cloud upload)

---

## Implementation Schedule (9 weeks)

### Week 1-2: Phase 1 - Quick Wins
**Week 1:**
- Days 1-3: Template tab management (24h)
- Days 4-5: Document visibility (16h)

**Week 2:**
- Days 1-2: Captive recipients (16h)
- Days 3-5: Custom tabs module (24h)

### Week 3-5: Phase 2 - Envelope/Template Parity
**Week 3:**
- Days 1-5: Document operations (40h)

**Week 4:**
- Days 1-5: Recipient operations (40h)

**Week 5:**
- Days 1-5: Bulk operations (40h)

### Week 6-9: Phase 3 - Specialized Features
**Week 6-7:**
- Days 1-10: Notary module (80h)

**Week 8:**
- Days 1-5: CloudStorage module (40h)

**Week 9:**
- Days 1-5: EmailArchive module (40h)

---

## Success Criteria

### Phase 1 Success Criteria
- [ ] All 27 endpoints return 200 OK with valid data
- [ ] Template editor has per-recipient tab assignment
- [ ] Document visibility controls work for envelopes and templates
- [ ] Captive recipient flow works end-to-end
- [ ] Custom tab library page functional
- [ ] All 89 tests passing

### Phase 2 Success Criteria
- [ ] All 65 endpoints return 200 OK
- [ ] Envelope document operations match template operations
- [ ] Recipient tabs work for all 27 types
- [ ] Bulk operations process 1000+ envelopes without errors
- [ ] All 145 tests passing

### Phase 3 Success Criteria
- [ ] All 16 endpoints return 200 OK
- [ ] Notary session completes with journal entry
- [ ] Cloud storage uploads work for all 3 providers
- [ ] Email archiving captures all sent emails
- [ ] All 97 tests passing

### Overall Success Criteria
- [ ] 419+ endpoints implemented (100%+)
- [ ] All 331 tests passing (100% pass rate)
- [ ] Code coverage ≥85% for new code
- [ ] Performance: API response time <500ms (p95)
- [ ] Zero critical bugs in production
- [ ] Documentation updated (OpenAPI spec, README, CHANGELOG)

---

## Risk Mitigation

### Technical Risks

**Risk 1: Third-Party Provider Integration Complexity**
- **Impact:** HIGH (Notary, CloudStorage)
- **Mitigation:**
  - Use mock providers for initial development
  - Test with provider sandboxes before production
  - Have fallback to manual processes

**Risk 2: PDF Manipulation Performance**
- **Impact:** MEDIUM (DocumentPage operations)
- **Mitigation:**
  - Use efficient PDF libraries (setasign/fpdf, spatie/pdf-to-image)
  - Implement caching for page thumbnails
  - Queue heavy operations

**Risk 3: Bulk Operations Scalability**
- **Impact:** MEDIUM (Bulk envelope operations)
- **Mitigation:**
  - Use Laravel queues with batching
  - Implement progress tracking
  - Add timeout protection

### Schedule Risks

**Risk 1: Scope Creep**
- **Impact:** HIGH
- **Mitigation:**
  - Strict scope definition per phase
  - Weekly progress reviews
  - Defer non-critical features to future releases

**Risk 2: Resource Availability**
- **Impact:** MEDIUM
- **Mitigation:**
  - Clear task breakdown for parallel work
  - Daily standups for blockers
  - Cross-training team members

---

## Testing Strategy

### Unit Tests (150 tests)
- Every service method has 2-3 unit tests
- Mock external dependencies
- Test edge cases and error handling

### Feature Tests (130 tests)
- API endpoint integration tests
- Database transactions and rollbacks
- Permission and authorization checks

### Playwright E2E Tests (51 tests)
- User flows for each feature
- Cross-browser testing
- Mobile responsive testing

### Performance Tests
- Load test: 1000 concurrent requests
- Bulk operations: 10,000 envelopes
- Document upload: 100MB files

---

## Dependencies

### External Services
- **Notary Providers:** NotaryCam, Notarize, Proof (API keys required)
- **Cloud Providers:** Google Cloud, Microsoft Azure, Dropbox (OAuth apps required)
- **PDF Library:** Spatie PDF library or similar

### Infrastructure
- **Queue Workers:** 3+ Laravel Horizon workers for background jobs
- **Storage:** 50GB+ for document storage and email archives
- **Database:** PostgreSQL with JSONB support

---

## Post-Implementation Tasks

### Documentation
- [ ] Update OpenAPI spec with new 61 endpoints
- [ ] Update PLATFORM-INVENTORY.md
- [ ] Update FRONTEND-IMPLEMENTATION-PLAN.md
- [ ] Create user guides for new features
- [ ] Update API client SDKs

### Deployment
- [ ] Run database migrations
- [ ] Update .env with new provider credentials
- [ ] Deploy to staging
- [ ] QA testing
- [ ] Deploy to production
- [ ] Monitor error rates

### Monitoring
- [ ] Add Sentry alerts for new endpoints
- [ ] Create Grafana dashboards for new features
- [ ] Set up CloudWatch alarms for queue delays
- [ ] Monitor third-party provider uptime

---

**Document Version:** 1.0
**Created:** 2025-11-16
**Status:** Ready for implementation
**Next Steps:** Begin Phase 1.1 - Template Tab Management
