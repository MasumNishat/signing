# DocuSign eSignature REST API - Complete Feature List

## Overview
This document lists all **419 endpoints** across **21 categories** available in the DocuSign eSignature REST API v2.1.

---

## API Statistics

- **Total Endpoints:** 419
- **Categories:** 21
- **API Version:** v2.1
- **Authentication:** OAuth 2.0 / JWT
- **Architecture:** RESTful

---

## Implementation Priority

### Phase 1: Foundation (Weeks 1-6)
- Authentication & Authorization
- Accounts Management
- Users Management
- Diagnostics & Monitoring

### Phase 2: Core Features (Weeks 7-16) ⭐ CRITICAL
- **Envelopes** (125 endpoints) - THE CORE FEATURE
- Recipients Management
- Document Operations
- Tabs & Fields

### Phase 3: Templates & Bulk Operations (Weeks 17-24)
- **Templates** (50 endpoints)
- BulkEnvelopes
- PowerForms

### Phase 4: Advanced Features (Weeks 25-32)
- Connect (Webhooks)
- SigningGroups
- UserGroups
- Folders

### Phase 5: Specialized Features (Weeks 33-40)
- Billing & Payments
- Notary
- CloudStorage
- Workspaces
- EmailArchive
- CustomTabs

---

## Feature Categories

### 1. Envelopes
**Endpoints:** 125 | **Purpose:** Create, send, manage, and track envelopes with documents for signing

**Operations:** DELETE: 23 | GET: 43 | POST: 26 | PUT: 33

#### Endpoints:
- `POST   /v2.1/accounts/{accountId}/chunked_uploads` - Initiate a new ChunkedUpload.
- `DELETE /v2.1/accounts/{accountId}/chunked_uploads/{chunkedUploadId}` - Delete an existing ChunkedUpload.
- `GET    /v2.1/accounts/{accountId}/chunked_uploads/{chunkedUploadId}` - Retrieves the current metadata of a ChunkedUpload.
- `PUT    /v2.1/accounts/{accountId}/chunked_uploads/{chunkedUploadId}` - Integrity-Check and Commit a ChunkedUpload, readying it for use elsewhere.
- `PUT    /v2.1/accounts/{accountId}/chunked_uploads/{chunkedUploadId}/{chunkedUploadPartSeq}` - Add a chunk, a chunk 'part', to an existing ChunkedUpload.
- `POST   /v2.1/accounts/{accountId}/connect/envelopes/publish/historical` - Submits a batch of historical envelopes for republish to an adhoc config.
- `GET    /v2.1/accounts/{accountId}/envelopes` - Gets status changes for one or more envelopes.
- `POST   /v2.1/accounts/{accountId}/envelopes` - Creates an envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/status` - Gets the envelope status for the specified envelopes.
- `GET    /v2.1/accounts/{accountId}/envelopes/transfer_rules` - Returns a list of envelope transfer rules in the specified account.
- `POST   /v2.1/accounts/{accountId}/envelopes/transfer_rules` - Add envelope transfer rules to an account.
- `PUT    /v2.1/accounts/{accountId}/envelopes/transfer_rules` - Update envelope transfer rules for an account.
- `DELETE /v2.1/accounts/{accountId}/envelopes/transfer_rules/{envelopeTransferRuleId}` - Delete envelope transfer rules for an account.
- `PUT    /v2.1/accounts/{accountId}/envelopes/transfer_rules/{envelopeTransferRuleId}` - Update an envelope transfer rule for an account.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}` - Gets the status of a envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}` - Send Draft Envelope/Void Envelope/Move/Purge Envelope/Modify draft
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/attachments` - Delete one or more attachments from a DRAFT envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/attachments` - Returns a list of attachments associated with the specified envelope
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/attachments` - Add one or more attachments to a DRAFT or IN-PROCESS envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/attachments/{attachmentId}` - Retrieves an attachment from the envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/attachments/{attachmentId}` - Add an attachment to a DRAFT or IN-PROCESS envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/audit_events` - Gets the envelope audit events for an envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/comments/transcript` - Gets comment transcript for envelope and user
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/custom_fields` - Deletes envelope custom fields for draft and in-process envelopes.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/custom_fields` - Gets the custom field information for the specified envelope.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/custom_fields` - Updates envelope custom fields for an envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/custom_fields` - Updates envelope custom fields in an envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/docGenFormFields` - Returns formfields for an envelope
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/docGenFormFields` - Updates formfields for an envelope
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents` - Deletes documents from a draft envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents` - Gets a list of envelope documents.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents` - Adds one or more documents to an existing envelope document.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}` - Gets a document from an envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}` - Adds a document to an existing draft envelope.
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields` - Deletes custom document fields from an existing envelope document.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields` - Gets the custom document fields from an  existing envelope document.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields` - Creates custom document fields in an existing envelope document.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields` - Updates existing custom document fields in an existing envelope document.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/html_definitions` - Get the Original HTML Definition used to generate the Responsive HTML for a give
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages` - Returns document page image(s) based on input.
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}` - Deletes a page from a document in an envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/page_image` - Gets a page image from an envelope for display.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/page_image` - Rotates page image from an envelope for display.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/tabs` - Returns tabs on the specified page.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/responsive_html_preview` - Get Responsive HTML Preview for a document in an envelope.
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/tabs` - Deletes tabs from an envelope document
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/tabs` - Returns tabs on the document.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/tabs` - Adds the tabs to an envelope document
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/tabs` - Updates the tabs for an envelope document
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/templates` - Gets the templates associated with a document in an existing envelope.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/templates` - Adds templates to a document in an  envelope.
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/templates/{templateId}` - Deletes a template from a document in an existing envelope.
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/email_settings` - Deletes the email setting overrides for an envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/email_settings` - Gets the email setting overrides for an envelope.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/email_settings` - Adds email setting overrides to an envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/email_settings` - Updates the email setting overrides for an envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/form_data` - Returns envelope form data for an existing envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/html_definitions` - Get the Original HTML Definition used to generate the Responsive HTML for the en
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/lock` - Deletes an envelope lock.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/lock` - Gets envelope lock information.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/lock` - Lock an envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/lock` - Updates an envelope lock.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/notification` - Gets envelope notification information.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/notification` - Sets envelope notification (Reminders/Expirations) structure for an existing env
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients` - Deletes recipients from an envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients` - Gets the status of recipients for an envelope.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients` - Adds one or more recipients to an envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients` - Updates recipients in a draft envelope or corrects recipient information for an 
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/document_visibility` - Updates document visibility for the recipients
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}` - Deletes a recipient from an envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/consumer_disclosure` - Gets the Electronic Record and Signature Disclosure associated with the account.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/consumer_disclosure/{langCode}` - Reserved: Gets the Electronic Record and Signature Disclosure associated with th
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/document_visibility` - Returns document visibility for the recipients
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/document_visibility` - Updates document visibility for the recipients
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/identity_proof_token` - Returns a resource token to get access to the identity events stored in the proo
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/initials_image` - Gets the initials image for a user.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/initials_image` - Sets the initials image for an accountless signer.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/signature` - Gets signature information for a signer or sign-in-person recipient.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/signature_image` - Retrieve signature image information for a signer/sign-in-person recipient.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/signature_image` - Sets the signature image for an accountless signer.
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs` - Deletes the tabs associated with a recipient.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs` - Gets the tabs information for a signer or sign-in-person recipient in an envelop
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs` - Adds tabs for a recipient.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs` - Updates the tabs for a recipient.


- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/views/identity_manual_review` - Provides a link to access the Identity manual review related to a recipient.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/responsive_html_preview` - Get Responsive HTML Preview for all documents in an envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/tabs_blob` - Get encrypted tabs for envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/tabs_blob` - Update encrypted tabs for envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/templates` - Get List of Templates used in an Envelope
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/templates` - Adds templates to an envelope.
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/views/correct` - Revokes the correction view URL to the Envelope UI
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/views/correct` - Returns a URL to the envelope correction UI.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/views/edit` - Returns a URL to the edit view UI.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/views/recipient` - Returns a URL to the recipient view UI.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/views/recipient_preview` - Provides a URL to start a recipient view of the Envelope UI
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/views/sender` - Returns a URL to the sender view UI.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/views/shared` - Provides a URL to start a shared recipient view of the Envelope UI
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow` - Delete the workflow definition for an envelope.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow` - Returns the workflow definition for an envelope.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow` - Updates the envelope workflow definition for an envelope.
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/scheduledSending` - Deletes the scheduled sending rules for the envelope's workflow.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/scheduledSending` - Returns the scheduled sending rules for an envelope's workflow definition.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/scheduledSending` - Updates the scheduled sending rules for an envelope's workflow definition.
- `POST   /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/steps` - Creates and adds a new workflow step definition for an envelope's workflow
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/steps/{workflowStepId}` - Deletes the envelope workflow step definition for an envelope's workflow by step
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/steps/{workflowStepId}` - Returns the workflow step definition for an envelope by step id.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/steps/{workflowStepId}` - Updates the envelope workflow step definition for an envelope.
- `DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/steps/{workflowStepId}/delayedRouting` - Deletes the delayed routing rules for the specified envelope workflow step.
- `GET    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/steps/{workflowStepId}/delayedRouting` - Returns the delayed routing rules for an envelope's workflow step definition.
- `PUT    /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/steps/{workflowStepId}/delayedRouting` - Updates the delayed routing rules for an envelope's workflow step definition.
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/workflow` - Delete the workflow definition for a template.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/workflow` - Returns the workflow definition for a template.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/workflow` - Updates the workflow definition for a template.
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/workflow/scheduledSending` - Deletes the scheduled sending rules for the template's workflow.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/workflow/scheduledSending` - Returns the scheduled sending rules for a template's workflow definition.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/workflow/scheduledSending` - Updates the scheduled sending rules for a template's workflow definition.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/workflow/steps` - Creates and adds a new workflow step definition for a template's workflow
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/workflow/steps/{workflowStepId}` - Deletes the workflow step definition for an template's workflow by step id.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/workflow/steps/{workflowStepId}` - Returns the workflow step definition for a template by step id.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/workflow/steps/{workflowStepId}` - Updates the template workflow step definition for an envelope.
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/workflow/steps/{workflowStepId}/delayedRouting` - Deletes the delayed routing rules for the specified template workflow step.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/workflow/steps/{workflowStepId}/delayedRouting` - Returns the delayed routing rules for a template's workflow step definition.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/workflow/steps/{workflowStepId}/delayedRouting` - Updates the delayed routing rules for a template's workflow step definition.
- `POST   /v2.1/accounts/{accountId}/views/console` - Returns a URL to the authentication view UI.
- `GET    /v2.1/current_user/notary/journals` - Get notary jurisdictions for a user

---

### 2. Templates
**Endpoints:** 50 | **Purpose:** Manage reusable envelope templates for common document workflows

**Operations:** DELETE: 10 | GET: 16 | POST: 10 | PUT: 14

#### Endpoints:
- `GET    /v2.1/accounts/{accountId}/templates` - Gets the definition of a template.
- `POST   /v2.1/accounts/{accountId}/templates` - Creates an envelope from a template.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}` - Gets a list of templates for a specified account.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}` - Updates an existing template.
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/custom_fields` - Deletes envelope custom fields in a template.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/custom_fields` - Gets the custom document fields from a template.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/custom_fields` - Creates custom document fields in an existing template document.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/custom_fields` - Updates envelope custom fields in a template.
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/documents` - Deletes documents from a template.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/documents` - Gets a list of documents associated with a template.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/documents` - Adds documents to a template document.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}` - Gets PDF documents from a template.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}` - Adds a document to a template document.
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/fields` - Deletes custom document fields from an existing template document.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/fields` - Gets the custom document fields for a an existing template document.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/fields` - Creates custom document fields in an existing template document.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/fields` - Updates existing custom document fields in an existing template document.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/html_definitions` - Get the Original HTML Definition used to generate the Responsive HTML for a give
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/pages/{pageNumber}` - Deletes a page from a document in an template.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/pages/{pageNumber}/page_image` - Gets a page image from a template for display.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/pages/{pageNumber}/page_image` - Rotates page image from a template for display.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/pages/{pageNumber}/tabs` - Returns tabs on the specified page.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/responsive_html_preview` - Post Responsive HTML Preview for a document in a template.
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/tabs` - Deletes tabs from an envelope document
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/tabs` - Returns tabs on the document.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/tabs` - Adds the tabs to a tempate
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/tabs` - Updates the tabs for a template
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/html_definitions` - Get the Original HTML Definition used to generate the Responsive HTML for the te
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/lock` - Deletes a template lock.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/lock` - Gets template lock information.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/lock` - Lock a template.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/lock` - Updates a template lock.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/notification` - Gets template notification information.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/notification` - Updates the notification  structure for an existing template.
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/recipients` - Deletes recipients from a template.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/recipients` - Gets recipient information from a template.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/recipients` - Adds tabs for a recipient.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/recipients` - Updates recipients in a template.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/recipients/document_visibility` - Updates document visibility for the recipients
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}` - Deletes the specified recipient file from a template.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/document_visibility` - Returns document visibility for the recipients
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/document_visibility` - Updates document visibility for the recipients
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/tabs` - Deletes the tabs associated with a recipient in a template.
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/tabs` - Gets the tabs information for a signer or sign-in-person recipient in a template
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/tabs` - Adds tabs for a recipient.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/tabs` - Updates the tabs for a recipient.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/responsive_html_preview` - Get Responsive HTML Preview for all documents in a template.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/views/recipient_preview` - Provides a URL to start a recipient view of the Envelope UI
- `DELETE /v2.1/accounts/{accountId}/templates/{templateId}/{templatePart}` - Removes a member group's sharing permissions for a template.
- `PUT    /v2.1/accounts/{accountId}/templates/{templateId}/{templatePart}` - Shares a template with a group

---

### 3. Accounts
**Endpoints:** 76 | **Purpose:** Create and manage DocuSign accounts, settings, and branding

**Operations:** DELETE: 13 | GET: 36 | POST: 7 | PUT: 20

#### Endpoints:
- `POST   /v2.1/accounts` - Creates new accounts.
- `GET    /v2.1/accounts/provisioning` - Retrieves the account provisioning information for the account.
- `DELETE /v2.1/accounts/{accountId}` - Deletes the specified account.
- `GET    /v2.1/accounts/{accountId}` - Retrieves the account information for the specified account.
- `GET    /v2.1/accounts/{accountId}/billing_charges` - Gets list of recurring and usage charges for the account.
- `DELETE /v2.1/accounts/{accountId}/brands` - Deletes one or more brand profiles.
- `GET    /v2.1/accounts/{accountId}/brands` - Gets a list of brand profiles.
- `POST   /v2.1/accounts/{accountId}/brands` - Creates one or more brand profile files for the account.
- `DELETE /v2.1/accounts/{accountId}/brands/{brandId}` - Removes a brand.
- `GET    /v2.1/accounts/{accountId}/brands/{brandId}` - Get information for a specific brand.
- `PUT    /v2.1/accounts/{accountId}/brands/{brandId}` - Updates an existing brand.
- `GET    /v2.1/accounts/{accountId}/brands/{brandId}/file` - Export a specific brand.
- `DELETE /v2.1/accounts/{accountId}/brands/{brandId}/logos/{logoType}` - Delete one branding logo.
- `GET    /v2.1/accounts/{accountId}/brands/{brandId}/logos/{logoType}` - Obtains the specified image for a brand.
- `PUT    /v2.1/accounts/{accountId}/brands/{brandId}/logos/{logoType}` - Put one branding logo.
- `GET    /v2.1/accounts/{accountId}/brands/{brandId}/resources` - Returns the specified account's list of branding resources (metadata).
- `GET    /v2.1/accounts/{accountId}/brands/{brandId}/resources/{resourceContentType}` - Returns the specified branding resource file.
- `PUT    /v2.1/accounts/{accountId}/brands/{brandId}/resources/{resourceContentType}` - Uploads a branding resource file.
- `DELETE /v2.1/accounts/{accountId}/captive_recipients/{recipientPart}` - Deletes the signature for one or more captive recipient records.
- `GET    /v2.1/accounts/{accountId}/consumer_disclosure` - Gets the Electronic Record and Signature Disclosure for the account.
- `GET    /v2.1/accounts/{accountId}/consumer_disclosure/{langCode}` - Gets the Electronic Record and Signature Disclosure.
- `PUT    /v2.1/accounts/{accountId}/consumer_disclosure/{langCode}` - Update Consumer Disclosure.
- `GET    /v2.1/accounts/{accountId}/custom_fields` - Gets a list of custom fields associated with the account.
- `POST   /v2.1/accounts/{accountId}/custom_fields` - Creates an acount custom field.
- `DELETE /v2.1/accounts/{accountId}/custom_fields/{customFieldId}` - Delete an existing account custom field.
- `PUT    /v2.1/accounts/{accountId}/custom_fields/{customFieldId}` - Updates an existing account custom field.
- `DELETE /v2.1/accounts/{accountId}/favorite_templates` - Unfavorite a template
- `GET    /v2.1/accounts/{accountId}/favorite_templates` - Retrieves the list of favorited templates for this caller
- `PUT    /v2.1/accounts/{accountId}/favorite_templates` - Favorites a template
- `GET    /v2.1/accounts/{accountId}/identity_verification` - Get the list of identity verification options for an account
- `GET    /v2.1/accounts/{accountId}/permission_profiles` - Gets a list of permission profiles.
- `POST   /v2.1/accounts/{accountId}/permission_profiles` - Creates a new permission profile in the specified account.
- `DELETE /v2.1/accounts/{accountId}/permission_profiles/{permissionProfileId}` - Deletes a permissions profile within the specified account.
- `GET    /v2.1/accounts/{accountId}/permission_profiles/{permissionProfileId}` - Returns a permissions profile in the specified account.
- `PUT    /v2.1/accounts/{accountId}/permission_profiles/{permissionProfileId}` - Updates a permission profile within the specified account.
- `GET    /v2.1/accounts/{accountId}/recipient_names` - Gets recipient names associated with an email address.
- `GET    /v2.1/accounts/{accountId}/seals` - Returns Account available seals for specified account.
- `GET    /v2.1/accounts/{accountId}/settings` - Gets account settings information.
- `PUT    /v2.1/accounts/{accountId}/settings` - Updates the account settings for an account.
- `DELETE /v2.1/accounts/{accountId}/settings/enote_configuration` - Deletes configuration information for the eNote eOriginal integration.
- `GET    /v2.1/accounts/{accountId}/settings/enote_configuration` - Returns the configuration information for the eNote eOriginal integration.
- `PUT    /v2.1/accounts/{accountId}/settings/enote_configuration` - Updates configuration information for the eNote eOriginal integration.
- `GET    /v2.1/accounts/{accountId}/settings/envelope_purge_configuration` - Select envelope purge configuration.
- `PUT    /v2.1/accounts/{accountId}/settings/envelope_purge_configuration` - Updates envelope purge configuration.
- `GET    /v2.1/accounts/{accountId}/settings/notification_defaults` - Returns default user level settings for a specified account
- `PUT    /v2.1/accounts/{accountId}/settings/notification_defaults` - Updates default user level settings for a specified account
- `GET    /v2.1/accounts/{accountId}/settings/password_rules` - Get the password rules
- `PUT    /v2.1/accounts/{accountId}/settings/password_rules` - Update the password rules
- `GET    /v2.1/accounts/{accountId}/settings/tabs` - Returns tab settings list for specified account
- `PUT    /v2.1/accounts/{accountId}/settings/tabs` - Modifies tab settings for specified account
- `GET    /v2.1/accounts/{accountId}/shared_access` - Reserved: Gets the shared item status for one or more users.
- `PUT    /v2.1/accounts/{accountId}/shared_access` - Reserved: Sets the shared access information for users.
- `GET    /v2.1/accounts/{accountId}/signatureProviders` - Returns Account available signature providers for specified account.
- `GET    /v2.1/accounts/{accountId}/signatures` - Returns the managed signature definitions for the account
- `POST   /v2.1/accounts/{accountId}/signatures` - Adds/updates one or more account signatures. This request may include images in 
- `PUT    /v2.1/accounts/{accountId}/signatures` - Updates a account signature.
- `DELETE /v2.1/accounts/{accountId}/signatures/{signatureId}` - Close the specified signature by Id.
- `GET    /v2.1/accounts/{accountId}/signatures/{signatureId}` - Returns information about a single signature by specifed signatureId.
- `PUT    /v2.1/accounts/{accountId}/signatures/{signatureId}` - Updates a account signature.
- `DELETE /v2.1/accounts/{accountId}/signatures/{signatureId}/{imageType}` - Deletes a signature, initials, or stamps image.
- `GET    /v2.1/accounts/{accountId}/signatures/{signatureId}/{imageType}` - Returns a signature, initials, or stamps image.
- `PUT    /v2.1/accounts/{accountId}/signatures/{signatureId}/{imageType}` - Sets a signature, initials, or stamps image.
- `GET    /v2.1/accounts/{accountId}/supported_languages` - Gets list of supported languages for recipient language setting.
- `GET    /v2.1/accounts/{accountId}/unsupported_file_types` - Gets a list of unsupported file types.
- `POST   /v2.1/accounts/{accountId}/users/{userId}/authorization` - Creates the user authorization
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/authorization/{authorizationId}` - Deletes the user authorization
- `GET    /v2.1/accounts/{accountId}/users/{userId}/authorization/{authorizationId}` - Returns the user authorization for a given authorization id
- `PUT    /v2.1/accounts/{accountId}/users/{userId}/authorization/{authorizationId}` - Updates the user authorization
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/authorizations` - Creates ot updates user authorizations
- `GET    /v2.1/accounts/{accountId}/users/{userId}/authorizations` - Returns the principal user authorizations
- `POST   /v2.1/accounts/{accountId}/users/{userId}/authorizations` - Creates ot updates user authorizations
- `GET    /v2.1/accounts/{accountId}/users/{userId}/authorizations/agent` - Returns the agent user authorizations
- `GET    /v2.1/accounts/{accountId}/watermark` - Get watermark information.
- `PUT    /v2.1/accounts/{accountId}/watermark` - Update watermark information.
- `PUT    /v2.1/accounts/{accountId}/watermark/preview` - Get watermark preview.
- `GET    /v2.1/current_user/password_rules` - Get membership account password rules

---

### 4. Users
**Endpoints:** 31 | **Purpose:** Manage users, profiles, signatures, and settings within accounts

**Operations:** DELETE: 7 | GET: 10 | POST: 4 | PUT: 10

#### Endpoints:
- `DELETE /v2.1/accounts/{accountId}/contacts` - Delete contacts associated with an account for the DocuSign service.
- `POST   /v2.1/accounts/{accountId}/contacts` - Imports multiple new contacts into the contacts collection from CSV, JSON, or XM
- `PUT    /v2.1/accounts/{accountId}/contacts` - Replaces contacts associated with an account for the DocuSign service.
- `DELETE /v2.1/accounts/{accountId}/contacts/{contactId}` - Replaces a particular contact associated with an account for the DocuSign servic
- `GET    /v2.1/accounts/{accountId}/contacts/{contactId}` - Gets a particular contact associated with the user's account.
- `POST   /v2.1/accounts/{accountId}/templates/{templateId}/views/edit` - Provides a URL to start an edit view of the Template UI
- `DELETE /v2.1/accounts/{accountId}/users` - Removes users account privileges.
- `GET    /v2.1/accounts/{accountId}/users` - Retrieves the list of users for the specified account.
- `POST   /v2.1/accounts/{accountId}/users` - Adds news user to the specified account.
- `PUT    /v2.1/accounts/{accountId}/users` - Change one or more user in the specified account.
- `GET    /v2.1/accounts/{accountId}/users/{userId}` - Gets the user information for a specified user.
- `PUT    /v2.1/accounts/{accountId}/users/{userId}` - Updates the specified user information.
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/custom_settings` - Deletes custom user settings for a specified user.
- `GET    /v2.1/accounts/{accountId}/users/{userId}/custom_settings` - Retrieves the custom user settings for a specified user.
- `PUT    /v2.1/accounts/{accountId}/users/{userId}/custom_settings` - Adds or updates custom user settings for the specified user.
- `GET    /v2.1/accounts/{accountId}/users/{userId}/profile` - Retrieves the user profile for a specified user.
- `PUT    /v2.1/accounts/{accountId}/users/{userId}/profile` - Updates the user profile information for the specified user.
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/profile/image` - Deletes the user profile image for the specified user.
- `GET    /v2.1/accounts/{accountId}/users/{userId}/profile/image` - Retrieves the user profile image for the specified user.
- `PUT    /v2.1/accounts/{accountId}/users/{userId}/profile/image` - Updates the user profile image for a specified user.
- `GET    /v2.1/accounts/{accountId}/users/{userId}/settings` - Gets the user account settings for a specified user.
- `PUT    /v2.1/accounts/{accountId}/users/{userId}/settings` - Updates the user account settings for a specified user.
- `GET    /v2.1/accounts/{accountId}/users/{userId}/signatures` - Retrieves a list of user signature definitions for a specified user.
- `POST   /v2.1/accounts/{accountId}/users/{userId}/signatures` - Adds user Signature and initials images to a Signature.
- `PUT    /v2.1/accounts/{accountId}/users/{userId}/signatures` - Adds/updates a user signature.
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}` - Removes removes signature information for the specified user.
- `GET    /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}` - Gets the user signature information for the specified user.
- `PUT    /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}` - Updates the user signature for a specified user.
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}/{imageType}` - Deletes the user initials image or the  user signature image for the specified u
- `GET    /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}/{imageType}` - Retrieves the user initials image or the  user signature image for the specified
- `PUT    /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}/{imageType}` - Updates the user signature image or user initials image for the specified user.

---

### 5. Connect
**Endpoints:** 19 | **Purpose:** Configure webhooks and integrations for real-time event notifications

**Operations:** DELETE: 5 | GET: 8 | POST: 2 | PUT: 4

#### Endpoints:
- `GET    /v2.1/accounts/{accountId}/connect` - Get Connect Configuration Information
- `POST   /v2.1/accounts/{accountId}/connect` - Creates a connect configuration for the specified account.
- `PUT    /v2.1/accounts/{accountId}/connect` - Updates a specified Connect configuration.
- `PUT    /v2.1/accounts/{accountId}/connect/envelopes/retry_queue` - Republishes Connect information for multiple envelopes.
- `PUT    /v2.1/accounts/{accountId}/connect/envelopes/{envelopeId}/retry_queue` - Republishes Connect information for the specified envelope.
- `GET    /v2.1/accounts/{accountId}/connect/failures` - Gets the Connect failure log information.
- `DELETE /v2.1/accounts/{accountId}/connect/failures/{failureId}` - Deletes a Connect failure log entry.
- `DELETE /v2.1/accounts/{accountId}/connect/logs` - Gets a list of Connect log entries.
- `GET    /v2.1/accounts/{accountId}/connect/logs` - Gets the Connect log.
- `DELETE /v2.1/accounts/{accountId}/connect/logs/{logId}` - Deletes a specified Connect log entry.
- `GET    /v2.1/accounts/{accountId}/connect/logs/{logId}` - Get the specified Connect log entry.
- `DELETE /v2.1/accounts/{accountId}/connect/oauth` - Sets the Connect OAuth Config for the account.
- `GET    /v2.1/accounts/{accountId}/connect/oauth` - Sets the Connect OAuth Config for the account.
- `POST   /v2.1/accounts/{accountId}/connect/oauth` - Sets the Connect OAuth Config for the account.
- `PUT    /v2.1/accounts/{accountId}/connect/oauth` - Updates the existing Connect OAuth Config for the account.
- `DELETE /v2.1/accounts/{accountId}/connect/{connectId}` - Deletes the specified connect configuration.
- `GET    /v2.1/accounts/{accountId}/connect/{connectId}` - Get a Connect Configuration Information
- `GET    /v2.1/accounts/{accountId}/connect/{connectId}/all/users` - Returns all users from the configured Connect service.
- `GET    /v2.1/accounts/{accountId}/connect/{connectId}/users` - Returns users from the configured Connect service.

---

### 6. BulkEnvelopes
**Endpoints:** 12 | **Purpose:** Send envelopes in bulk to multiple recipients efficiently

**Operations:** DELETE: 1 | GET: 5 | POST: 3 | PUT: 3

#### Endpoints:
- `GET    /v2.1/accounts/{accountId}/bulk_send_batch` - Returns a list of bulk send batch satuses initiated by account.
- `GET    /v2.1/accounts/{accountId}/bulk_send_batch/{bulkSendBatchId}` - Gets a specific bulk send batch status
- `PUT    /v2.1/accounts/{accountId}/bulk_send_batch/{bulkSendBatchId}` - Put/Update a specific bulk send batch status
- `GET    /v2.1/accounts/{accountId}/bulk_send_batch/{bulkSendBatchId}/envelopes` - Gets envelopes from a specific bulk send batch
- `PUT    /v2.1/accounts/{accountId}/bulk_send_batch/{bulkSendBatchId}/{bulkAction}` - Initiate a specific bulk send batch action
- `GET    /v2.1/accounts/{accountId}/bulk_send_lists` - Lists top-level details for all bulk send lists visible to the current user
- `POST   /v2.1/accounts/{accountId}/bulk_send_lists` - Creates a new bulk send list
- `DELETE /v2.1/accounts/{accountId}/bulk_send_lists/{bulkSendListId}` - Deletes an existing bulk send list
- `GET    /v2.1/accounts/{accountId}/bulk_send_lists/{bulkSendListId}` - Gets a specific bulk send list
- `PUT    /v2.1/accounts/{accountId}/bulk_send_lists/{bulkSendListId}` - Updates an existing bulk send list.  If send_envelope query string value is prov
- `POST   /v2.1/accounts/{accountId}/bulk_send_lists/{bulkSendListId}/send` - Uses the specified bulk send list to send the envelope specified in the payload
- `POST   /v2.1/accounts/{accountId}/bulk_send_lists/{bulkSendListId}/test` - Tests whether the specified bulk sending list can be used to send an envelope

---

### 7. Billing
**Endpoints:** 14 | **Purpose:** Manage billing plans, invoices, payments, and charges

**Operations:** GET: 10 | POST: 1 | PUT: 3

#### Endpoints:
- `GET    /v2.1/accounts/{accountId}/billing_invoices` - Get a List of Billing Invoices
- `GET    /v2.1/accounts/{accountId}/billing_invoices/{invoiceId}` - Retrieves a billing invoice.
- `GET    /v2.1/accounts/{accountId}/billing_invoices_past_due` - Get a list of past due invoices.
- `GET    /v2.1/accounts/{accountId}/billing_payments` - Gets payment information for one or more payments.
- `POST   /v2.1/accounts/{accountId}/billing_payments` - Posts a payment to a past due invoice.
- `GET    /v2.1/accounts/{accountId}/billing_payments/{paymentId}` - Gets billing payment information for a specific payment.
- `GET    /v2.1/accounts/{accountId}/billing_plan` - Get Account Billing Plan
- `PUT    /v2.1/accounts/{accountId}/billing_plan` - Updates the account billing plan.
- `GET    /v2.1/accounts/{accountId}/billing_plan/credit_card` - Get metadata for a given credit card.
- `GET    /v2.1/accounts/{accountId}/billing_plan/downgrade` - Returns downgrade plan information for the specified account.
- `PUT    /v2.1/accounts/{accountId}/billing_plan/downgrade` - Queues downgrade billing plan request for an account.
- `PUT    /v2.1/accounts/{accountId}/billing_plan/purchased_envelopes` - Reserverd: Purchase additional envelopes.
- `GET    /v2.1/billing_plans` - Gets the list of available billing plans.
- `GET    /v2.1/billing_plans/{billingPlanId}` - Get the billing plan details.

---

### 8. SigningGroups
**Endpoints:** 9 | **Purpose:** Create and manage groups of signers for routing flexibility

**Operations:** DELETE: 2 | GET: 3 | POST: 1 | PUT: 3

#### Endpoints:
- `DELETE /v2.1/accounts/{accountId}/signing_groups` - Deletes one or more signing groups.
- `GET    /v2.1/accounts/{accountId}/signing_groups` - Gets a list of the Signing Groups in an account.
- `POST   /v2.1/accounts/{accountId}/signing_groups` - Creates a signing group. 
- `PUT    /v2.1/accounts/{accountId}/signing_groups` - Updates signing group names.
- `GET    /v2.1/accounts/{accountId}/signing_groups/{signingGroupId}` - Gets information about a signing group. 
- `PUT    /v2.1/accounts/{accountId}/signing_groups/{signingGroupId}` - Updates a signing group. 
- `DELETE /v2.1/accounts/{accountId}/signing_groups/{signingGroupId}/users` - Deletes  one or more members from a signing group.
- `GET    /v2.1/accounts/{accountId}/signing_groups/{signingGroupId}/users` - Gets a list of members in a Signing Group.
- `PUT    /v2.1/accounts/{accountId}/signing_groups/{signingGroupId}/users` - Adds members to a signing group. 

---

### 9. UserGroups
**Endpoints:** 10 | **Purpose:** Organize users into groups for permission management

**Operations:** DELETE: 3 | GET: 3 | POST: 1 | PUT: 3

#### Endpoints:
- `DELETE /v2.1/accounts/{accountId}/groups` - Deletes an existing user group.
- `GET    /v2.1/accounts/{accountId}/groups` - Gets information about groups associated with the account.
- `POST   /v2.1/accounts/{accountId}/groups` - Creates one or more groups for the account.
- `PUT    /v2.1/accounts/{accountId}/groups` - Updates the group information for a group.
- `DELETE /v2.1/accounts/{accountId}/groups/{groupId}/brands` - Deletes brand information from the requested group.
- `GET    /v2.1/accounts/{accountId}/groups/{groupId}/brands` - Gets group brand ID Information.

- `PUT    /v2.1/accounts/{accountId}/groups/{groupId}/brands` - Adds group brand ID information to a group.
- `DELETE /v2.1/accounts/{accountId}/groups/{groupId}/users` - Deletes one or more users from a gro
- `GET    /v2.1/accounts/{accountId}/groups/{groupId}/users` - Gets a list of users in a group.
- `PUT    /v2.1/accounts/{accountId}/groups/{groupId}/users` - Adds one or more users to an existing group.

---

### 10. PowerForms
**Endpoints:** 8 | **Purpose:** Create reusable forms that can be signed by multiple people

**Operations:** DELETE: 2 | GET: 4 | POST: 1 | PUT: 1

#### Endpoints:
- `DELETE /v2.1/accounts/{accountId}/powerforms` - Deletes one or more PowerForms
- `GET    /v2.1/accounts/{accountId}/powerforms` - Returns the list of PowerForms available to the user.
- `POST   /v2.1/accounts/{accountId}/powerforms` - Creates a new PowerForm.
- `GET    /v2.1/accounts/{accountId}/powerforms/senders` - Returns the list of PowerForms available to the user.
- `DELETE /v2.1/accounts/{accountId}/powerforms/{powerFormId}` - Delete a PowerForm.
- `GET    /v2.1/accounts/{accountId}/powerforms/{powerFormId}` - Returns a single PowerForm.
- `PUT    /v2.1/accounts/{accountId}/powerforms/{powerFormId}` - Creates a new PowerForm.
- `GET    /v2.1/accounts/{accountId}/powerforms/{powerFormId}/form_data` - Returns the form data associated with the usage of a PowerForm.

---

### 11. Folders
**Endpoints:** 4 | **Purpose:** Organize and manage envelopes in folders

**Operations:** GET: 3 | PUT: 1

#### Endpoints:
- `GET    /v2.1/accounts/{accountId}/folders` - Gets a list of the folders for the account.
- `GET    /v2.1/accounts/{accountId}/folders/{folderId}` - Gets a list of the envelopes in the specified folder.
- `PUT    /v2.1/accounts/{accountId}/folders/{folderId}` - Moves an envelope from its current folder to the specified folder.
- `GET    /v2.1/accounts/{accountId}/search_folders/{searchFolderId}` - Gets a list of envelopes in folders matching the specified criteria.

---

### 12. CustomTabs
**Endpoints:** 5 | **Purpose:** Create custom tab types for document fields

**Operations:** DELETE: 1 | GET: 2 | POST: 1 | PUT: 1

#### Endpoints:
- `GET    /v2.1/accounts/{accountId}/tab_definitions` - Gets a list of all account tabs.
- `POST   /v2.1/accounts/{accountId}/tab_definitions` - Creates a custom tab.
- `DELETE /v2.1/accounts/{accountId}/tab_definitions/{customTabId}` - Deletes custom tab information.
- `GET    /v2.1/accounts/{accountId}/tab_definitions/{customTabId}` - Gets custom tab information.
- `PUT    /v2.1/accounts/{accountId}/tab_definitions/{customTabId}` - Updates custom tab information.



---

### 13. Notary
**Endpoints:** 8 | **Purpose:** Manage notary settings and jurisdictions for notarial acts

**Operations:** DELETE: 1 | GET: 3 | POST: 2 | PUT: 2

#### Endpoints:
- `GET    /v2.1/current_user/notary` - Get notary settings for a user
- `POST   /v2.1/current_user/notary` - Add a notary to the system
- `PUT    /v2.1/current_user/notary` - Update a notary
- `GET    /v2.1/current_user/notary/jurisdictions` - Get notary jurisdictions for a user
- `POST   /v2.1/current_user/notary/jurisdictions` - Add a notary jurisdiction to the system
- `DELETE /v2.1/current_user/notary/jurisdictions/{jurisdictionId}` - Delete a notary jurisdiction a specified user.
- `GET    /v2.1/current_user/notary/jurisdictions/{jurisdictionId}` - Get notary a jurisdiction for a user
- `PUT    /v2.1/current_user/notary/jurisdictions/{jurisdictionId}` - Update a notary jurisdiction

---

### 14. CloudStorage
**Endpoints:** 7 | **Purpose:** Integrate with cloud storage providers (Box, Dropbox, etc.)

**Operations:** DELETE: 2 | GET: 4 | POST: 1

#### Endpoints:
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/cloud_storage` - Deletes the user authentication information for one or more cloud storage provid
- `GET    /v2.1/accounts/{accountId}/users/{userId}/cloud_storage` - Get the Cloud Storage Provider configuration for the specified user.
- `POST   /v2.1/accounts/{accountId}/users/{userId}/cloud_storage` - Configures the redirect URL information  for one or more cloud storage providers
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/cloud_storage/{serviceId}` - Deletes the user authentication information for the specified cloud storage prov
- `GET    /v2.1/accounts/{accountId}/users/{userId}/cloud_storage/{serviceId}` - Gets the specified Cloud Storage Provider configuration for the User.
- `GET    /v2.1/accounts/{accountId}/users/{userId}/cloud_storage/{serviceId}/folders` - Retrieves a list of all the items in a specified folder from the specified cloud
- `GET    /v2.1/accounts/{accountId}/users/{userId}/cloud_storage/{serviceId}/folders/{folderId}` - Gets a list of all the items from the specified cloud storage provider.

---

### 15. Workspaces
**Endpoints:** 11 | **Purpose:** Collaborative workspaces for document management

**Operations:** DELETE: 2 | GET: 5 | POST: 2 | PUT: 2

#### Endpoints:
- `GET    /v2.1/accounts/{accountId}/workspaces` - List Workspaces
- `POST   /v2.1/accounts/{accountId}/workspaces` - Create a Workspace
- `DELETE /v2.1/accounts/{accountId}/workspaces/{workspaceId}` - Delete Workspace
- `GET    /v2.1/accounts/{accountId}/workspaces/{workspaceId}` - Get Workspace
- `PUT    /v2.1/accounts/{accountId}/workspaces/{workspaceId}` - Update Workspace
- `DELETE /v2.1/accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}` - Deletes workspace one or more specific files/folders from the given folder or ro
- `GET    /v2.1/accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}` - List Workspace Folder Contents
- `POST   /v2.1/accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}/files` - Creates a workspace file.
- `GET    /v2.1/accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}/files/{fileId}` - Get Workspace File
- `PUT    /v2.1/accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}/files/{fileId}` - Update Workspace File Metadata
- `GET    /v2.1/accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}/files/{fileId}/pages` - List File Pages

---

### 16. EmailArchive
**Endpoints:** 4 | **Purpose:** Configure BCC email archiving for compliance

**Operations:** DELETE: 1 | GET: 2 | POST: 1

#### Endpoints:
- `GET    /v2.1/accounts/{accountId}/settings/bcc_email_archives` - Get the blind carbon copy email archive entries owned by the specified account
- `POST   /v2.1/accounts/{accountId}/settings/bcc_email_archives` - Creates a blind carbon copy email archive entry
- `DELETE /v2.1/accounts/{accountId}/settings/bcc_email_archives/{bccEmailArchiveId}` - Delete a blind carbon copy email archive for an account.
- `GET    /v2.1/accounts/{accountId}/settings/bcc_email_archives/{bccEmailArchiveId}` - Get the blind carbon copy email archive history entries for the specified archiv

---

### 17. Payments
**Endpoints:** 1 | **Purpose:** Manage payment gateway integrations

**Operations:** GET: 1

#### Endpoints:
- `GET    /v2.1/accounts/{accountId}/payment_gateway_accounts` - Get all payment gateway account for the provided accountId

---

### 18. Authentication
**Endpoints:** 2 | **Purpose:** OAuth 2.0 authentication and user information

**Operations:** GET: 1 | POST: 1

#### Endpoints:
- `POST   /oauth/token` - 01 Authorize Code Grant Access Token
- `GET    /oauth/userinfo` - 04 Get User Info

---

### 19. Diagnostics
**Endpoints:** 6 | **Purpose:** API logging, debugging, and version information

**Operations:** DELETE: 1 | GET: 4 | PUT: 1

#### Endpoints:
- `GET    /service_information` - Retrieves the available REST API versions.
- `DELETE /v2.1/diagnostics/request_logs` - Deletes the request log files.
- `GET    /v2.1/diagnostics/request_logs` - Gets the API request logging log files.
- `GET    /v2.1/diagnostics/request_logs/{requestLogId}` - Gets a request logging log file.
- `GET    /v2.1/diagnostics/settings` - Gets the API request logging settings.
- `PUT    /v2.1/diagnostics/settings` - Enables or disables API request logging for troubleshooting.

---

### 20. Examples
**Endpoints:** 15 | **Purpose:** Example endpoints for testing and learning

**Operations:** GET: 9 | POST: 5 | PUT: 1

#### Endpoints:
- `POST   /{apiVersion}/accounts/{accountId}/envelopes` - 1. Create an Envelope
- `POST   /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId_draft}/views/recipient_preview` - 14. Created Embedded View
- `GET    /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}` - 3. Get Envelope Status
- `GET    /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}/attachments` - 8. List Attachments in an Envelope
- `PUT    /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}/attachments` - 7. Add an Attachment to Envelope
- `GET    /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}/documents` - 9. Lists the Documents in an Envelope
- `GET    /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}` - 10. Get a Document from Envelope
- `GET    /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}/lock` - 12. Lists the Existing Locks on an Envelope
- `POST   /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}/lock` - 11. Lock the Envelope
- `GET    /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}/recipients` - 4. Envelope Recipients
- `GET    /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs` - 6. Get Tabs in an Envelope
- `POST   /{apiVersion}/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs` - 5. Add Tab to Envelope
- `GET    /{apiVersion}/accounts/{accountId}/templates` - 16. Get Account Templates
- `POST   /{apiVersion}/accounts/{accountId}/templates` - 15. Create an Account Template
- `GET    /{apiVersion}/accounts/{accountId}/templates/{templateId}` - 17. Get Template Definition

---

### 21. Untagged
**Endpoints:** 2 | **Purpose:** Miscellaneous endpoints

**Operations:** GET: 2

#### Endpoints:
- `GET    /v2.1` - Lists resources for REST version specified
- `GET    /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/pages` - Returns document page image(s) based on input.

---

