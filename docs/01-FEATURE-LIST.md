# DocuSign eSignature API - Feature List

## Overview
This document lists all features and endpoints available in the DocuSign eSignature REST API v2.1.

## Feature Categories

### 1. Diagnostics & System Information
**Purpose:** Monitor system health, manage API logs, and retrieve version information

#### Endpoints:
- `GET /service_information` - Retrieves available REST API versions
- `GET /v2.1/diagnostics/request_logs` - Gets API request logging log files
- `DELETE /v2.1/diagnostics/request_logs` - Deletes request log files
- `GET /v2.1/diagnostics/request_logs/{requestLogId}` - Gets a specific request logging log file
- `GET /v2.1/diagnostics/settings` - Gets API request logging settings
- `PUT /v2.1/diagnostics/settings` - Enables or disables API request logging

---

### 2. Account Management
**Purpose:** Create, manage, and configure DocuSign accounts

#### Core Account Operations:
- `POST /v2.1/accounts` - Creates new DocuSign accounts
- `GET /v2.1/accounts/{accountId}` - Retrieves account information
- `DELETE /v2.1/accounts/{accountId}` - Deletes the specified account
- `GET /v2.1/accounts/{accountId}/billing_charges` - Gets billing charges
- `GET /v2.1/accounts/provisioning` - Retrieves account provisioning information

#### Account Settings:
- `GET /v2.1/accounts/{accountId}/settings` - Gets account settings
- `PUT /v2.1/accounts/{accountId}/settings` - Updates account settings
- `GET /v2.1/accounts/{accountId}/settings/enote_configuration` - Gets eNote eOriginal integration config
- `PUT /v2.1/accounts/{accountId}/settings/enote_configuration` - Updates eNote config
- `DELETE /v2.1/accounts/{accountId}/settings/enote_configuration` - Deletes eNote config
- `GET /v2.1/accounts/{accountId}/settings/envelope_purge_configuration` - Gets envelope purge config
- `PUT /v2.1/accounts/{accountId}/settings/envelope_purge_configuration` - Updates envelope purge config
- `GET /v2.1/accounts/{accountId}/settings/notification_defaults` - Gets default notification settings
- `PUT /v2.1/accounts/{accountId}/settings/notification_defaults` - Updates notification settings
- `GET /v2.1/accounts/{accountId}/settings/password_rules` - Gets password rules
- `PUT /v2.1/accounts/{accountId}/settings/password_rules` - Updates password rules
- `GET /v2.1/accounts/{accountId}/settings/tabs` - Gets tab settings
- `PUT /v2.1/accounts/{accountId}/settings/tabs` - Updates tab settings

---

### 3. Branding Management
**Purpose:** Manage brand profiles, logos, and resources for white-labeling

#### Brand Operations:
- `GET /v2.1/accounts/{accountId}/brands` - Gets list of brand profiles
- `POST /v2.1/accounts/{accountId}/brands` - Creates brand profile files
- `DELETE /v2.1/accounts/{accountId}/brands` - Deletes brand profiles
- `GET /v2.1/accounts/{accountId}/brands/{brandId}` - Gets specific brand info
- `PUT /v2.1/accounts/{accountId}/brands/{brandId}` - Updates existing brand
- `DELETE /v2.1/accounts/{accountId}/brands/{brandId}` - Removes a brand
- `GET /v2.1/accounts/{accountId}/brands/{brandId}/file` - Export brand to XML
- `GET /v2.1/accounts/{accountId}/brands/{brandId}/logos/{logoType}` - Gets brand logo
- `PUT /v2.1/accounts/{accountId}/brands/{brandId}/logos/{logoType}` - Updates brand logo
- `DELETE /v2.1/accounts/{accountId}/brands/{brandId}/logos/{logoType}` - Deletes brand logo
- `GET /v2.1/accounts/{accountId}/brands/{brandId}/resources` - Gets branding resources metadata
- `GET /v2.1/accounts/{accountId}/brands/{brandId}/resources/{resourceContentType}` - Gets branding resource file
- `PUT /v2.1/accounts/{accountId}/brands/{brandId}/resources/{resourceContentType}` - Uploads branding resource file

---

### 4. Custom Fields Management
**Purpose:** Create and manage custom fields for envelopes

#### Custom Fields Operations:
- `GET /v2.1/accounts/{accountId}/custom_fields` - Gets list of custom fields
- `POST /v2.1/accounts/{accountId}/custom_fields` - Creates account custom field
- `PUT /v2.1/accounts/{accountId}/custom_fields/{customFieldId}` - Updates existing custom field
- `DELETE /v2.1/accounts/{accountId}/custom_fields/{customFieldId}` - Deletes custom field

---

### 5. Permission Profiles
**Purpose:** Manage user permissions and access control

#### Permission Operations:
- `GET /v2.1/accounts/{accountId}/permission_profiles` - Gets list of permission profiles
- `POST /v2.1/accounts/{accountId}/permission_profiles` - Creates new permission profile
- `GET /v2.1/accounts/{accountId}/permission_profiles/{permissionProfileId}` - Gets specific permission profile
- `PUT /v2.1/accounts/{accountId}/permission_profiles/{permissionProfileId}` - Updates permission profile
- `DELETE /v2.1/accounts/{accountId}/permission_profiles/{permissionProfileId}` - Deletes permission profile

---

### 6. Consumer Disclosure
**Purpose:** Manage Electronic Record and Signature Disclosure

#### Disclosure Operations:
- `GET /v2.1/accounts/{accountId}/consumer_disclosure` - Gets Electronic Record disclosure
- `GET /v2.1/accounts/{accountId}/consumer_disclosure/{langCode}` - Gets disclosure by language
- `PUT /v2.1/accounts/{accountId}/consumer_disclosure/{langCode}` - Updates consumer disclosure

---

### 7. Identity Verification
**Purpose:** Manage identity verification workflows for recipients

#### Identity Verification Operations:
- `GET /v2.1/accounts/{accountId}/identity_verification` - Gets list of identity verification options

---

### 8. Signatures Management
**Purpose:** Manage account-level signatures, initials, and stamps

#### Signature Operations:
- `GET /v2.1/accounts/{accountId}/signatures` - Gets managed signature definitions
- `PUT /v2.1/accounts/{accountId}/signatures` - Updates account signature
- `POST /v2.1/accounts/{accountId}/signatures` - Adds/updates account signatures
- `GET /v2.1/accounts/{accountId}/signatures/{signatureId}` - Gets specific signature info
- `PUT /v2.1/accounts/{accountId}/signatures/{signatureId}` - Updates specific signature
- `DELETE /v2.1/accounts/{accountId}/signatures/{signatureId}` - Closes signature by ID
- `GET /v2.1/accounts/{accountId}/signatures/{signatureId}/{imageType}` - Gets signature image
- `PUT /v2.1/accounts/{accountId}/signatures/{signatureId}/{imageType}` - Sets signature image
- `DELETE /v2.1/accounts/{accountId}/signatures/{signatureId}/{imageType}` - Deletes signature image

---

### 9. Seals Management
**Purpose:** Manage electronic seals for automated signing

#### Seals Operations:
- `GET /v2.1/accounts/{accountId}/seals` - Gets available seals for account

---

### 10. Template Management
**Purpose:** Manage reusable templates for envelopes

#### Template Operations:
- `GET /v2.1/accounts/{accountId}/favorite_templates` - Gets favorited templates list
- `PUT /v2.1/accounts/{accountId}/favorite_templates` - Favorites a template
- `DELETE /v2.1/accounts/{accountId}/favorite_templates` - Unfavorites a template

---

### 11. Recipients Management
**Purpose:** Manage recipients and captive signing

#### Recipient Operations:
- `DELETE /v2.1/accounts/{accountId}/captive_recipients/{recipientPart}` - Deletes captive recipient signatures
- `GET /v2.1/accounts/{accountId}/recipient_names` - Gets recipient names by email

---

### 12. Shared Access
**Purpose:** Manage shared access to envelopes and templates

#### Shared Access Operations:
- `GET /v2.1/accounts/{accountId}/shared_access` - Gets shared item status
- `PUT /v2.1/accounts/{accountId}/shared_access` - Sets shared access information

---

### 13. User Authorization
**Purpose:** Manage user authorization and agent relationships

#### Authorization Operations:
- `POST /v2.1/accounts/{accountId}/users/{userId}/authorization` - Creates user authorization
- `GET /v2.1/accounts/{accountId}/users/{userId}/authorization/{authorizationId}` - Gets specific authorization
- `PUT /v2.1/accounts/{accountId}/users/{userId}/authorization/{authorizationId}` - Updates authorization
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/authorization/{authorizationId}` - Deletes authorization
- `GET /v2.1/accounts/{accountId}/users/{userId}/authorizations` - Gets principal user authorizations
- `POST /v2.1/accounts/{accountId}/users/{userId}/authorizations` - Creates/updates user authorizations
- `DELETE /v2.1/accounts/{accountId}/users/{userId}/authorizations` - Deletes user authorizations
- `GET /v2.1/accounts/{accountId}/users/{userId}/authorizations/agent` - Gets agent user authorizations

---

### 14. Watermark Management
**Purpose:** Manage document watermarks

#### Watermark Operations:
- `GET /v2.1/accounts/{accountId}/watermark` - Gets watermark information
- `PUT /v2.1/accounts/{accountId}/watermark` - Updates watermark information
- `PUT /v2.1/accounts/{accountId}/watermark/preview` - Gets watermark preview

---

### 15. Billing Management
**Purpose:** Manage billing, invoices, and payments

#### Billing Operations:
- `GET /v2.1/accounts/{accountId}/billing_invoices` - Gets list of billing invoices
- `GET /v2.1/accounts/{accountId}/billing_invoices/{invoiceId}` - Gets specific billing invoice
- `GET /v2.1/accounts/{accountId}/billing_invoices_past_due` - Gets past due invoices
- `GET /v2.1/accounts/{accountId}/billing_payments` - Gets payment information
- `POST /v2.1/accounts/{accountId}/billing_payments` - Posts payment to past due invoice

---

### 16. Signature Providers
**Purpose:** Manage third-party signature provider integrations

#### Signature Provider Operations:
- `GET /v2.1/accounts/{accountId}/signatureProviders` - Gets available signature providers

---

### 17. Supported Languages
**Purpose:** Manage localization and language support

#### Language Operations:
- `GET /v2.1/accounts/{accountId}/supported_languages` - Gets supported languages list

---

### 18. File Types Management
**Purpose:** Manage supported and unsupported file types

#### File Type Operations:
- `GET /v2.1/accounts/{accountId}/unsupported_file_types` - Gets unsupported file types list

---

### 19. Password Management
**Purpose:** Manage password policies and rules

#### Password Operations:
- `GET /v2.1/current_user/password_rules` - Gets membership account password rules

---

## Feature Summary Statistics

- **Total Feature Categories:** 19
- **Total Endpoints:** ~90+
- **HTTP Methods:** GET, POST, PUT, DELETE
- **Authentication:** OAuth 2.0 / JWT
- **API Version:** v2.1

## Implementation Priority

### Phase 1 - Core Foundation (Weeks 1-4)
1. Account Management
2. Diagnostics & System Information
3. Permission Profiles
4. User Authorization

### Phase 2 - Document Management (Weeks 5-8)
1. Signatures Management
2. Seals Management
3. Custom Fields Management
4. Template Management

### Phase 3 - Branding & Customization (Weeks 9-12)
1. Branding Management
2. Watermark Management
3. Consumer Disclosure
4. Supported Languages

### Phase 4 - Advanced Features (Weeks 13-16)
1. Identity Verification
2. Recipients Management
3. Shared Access
4. Billing Management

### Phase 5 - Administration (Weeks 17-20)
1. File Types Management
2. Password Management
3. Signature Providers
4. Account Settings (advanced)
