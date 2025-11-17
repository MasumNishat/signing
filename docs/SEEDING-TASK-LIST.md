# Database Seeding Task List - DocuSign Clone

**Project:** Laravel Signing API
**Database:** PostgreSQL
**Total Models:** 70
**Total Tables:** 94 (66 custom + 3 Laravel + 5 Passport + 20 pivot/junction)
**Status:** Planning Phase

---

## Overview

This document provides a complete task breakdown for creating database seeders and factories for the entire application. The seeding strategy follows a dependency-based approach to ensure referential integrity.

### Seeding Goals

1. **Development Environment**: Realistic data for local development
2. **Testing Environment**: Consistent test data with edge cases
3. **Demo Environment**: Professional demo data for presentations
4. **Production Ready**: Minimal essential data for production deployment

### Current Status

**Existing Seeders (8):**
- ✅ FileTypeSeeder (23 file types)
- ✅ SupportedLanguageSeeder (20 languages)
- ✅ SignatureProviderSeeder (3 providers)
- ✅ PlanSeeder (4 plans)
- ✅ AccountSeeder (2 accounts)
- ✅ PermissionProfileSeeder (3 profiles)
- ✅ UserSeeder (3 users)
- ✅ DatabaseSeeder (orchestrator)

**Existing Factories (4):**
- ✅ AccountFactory
- ✅ UserFactory
- ✅ PermissionProfileFactory
- ✅ ApiKeyFactory

**Missing:** 66 factories, 55 seeders

---

## Phase Overview

| Phase | Category | Models | Priority | Est. Hours |
|-------|----------|--------|----------|------------|
| S1 | Reference Data | 8 | HIGH | 8h |
| S2 | Core Infrastructure | 12 | CRITICAL | 16h |
| S3 | Envelopes Module | 14 | CRITICAL | 24h |
| S4 | Templates & Documents | 6 | HIGH | 10h |
| S5 | Recipients & Routing | 5 | HIGH | 8h |
| S6 | Billing & Payments | 5 | MEDIUM | 8h |
| S7 | Branding & Customization | 8 | MEDIUM | 10h |
| S8 | Bulk Operations | 3 | MEDIUM | 6h |
| S9 | Connect & Webhooks | 4 | LOW | 6h |
| S10 | Workspaces & Folders | 3 | LOW | 4h |
| S11 | PowerForms | 2 | LOW | 3h |
| **TOTAL** | **11 Phases** | **70** | - | **103h** |

---

## Phase S1: Reference Data & Configuration (8 models)

**Priority:** HIGH
**Duration:** 8 hours
**Dependencies:** None

### Tasks

#### S1.1: File Types & Languages ✅
- [x] FileTypeSeeder (existing - 23 types)
- [x] SupportedLanguageSeeder (existing - 20 languages)

#### S1.2: Signature Providers ✅
- [x] SignatureProviderSeeder (existing - 3 providers)

#### S1.3: Identity Verification Workflows
- [ ] IdentityVerificationWorkflowFactory
- [ ] IdentityVerificationWorkflowSeeder
- **Data:** ID Check, Phone Auth, SMS Auth, KBA, ID Lookup (5 workflows)

#### S1.4: Tab Settings
- [ ] TabSettingFactory
- [ ] TabSettingSeeder
- **Data:** Default tab configurations (27 tab types with capabilities)

#### S1.5: Notification Defaults
- [ ] NotificationDefaultFactory
- [ ] NotificationDefaultSeeder
- **Data:** Email notification templates (10 notification types)

#### S1.6: Password Rules
- [ ] PasswordRuleFactory
- [ ] PasswordRuleSeeder
- **Data:** Default password policies (2 profiles: standard, strict)

#### S1.7: Account Settings Defaults
- [ ] AccountSettingsFactory
- [ ] AccountSettingsSeeder
- **Data:** Default account settings template

#### S1.8: Envelope Purge Configurations
- [ ] EnvelopePurgeConfigurationFactory
- [ ] EnvelopePurgeConfigurationSeeder
- **Data:** Document retention policies (3 policies)

**Deliverables:**
- 7 new factories
- 7 new seeders
- Reference data CSV/JSON files (optional)

---

## Phase S2: Core Infrastructure (12 models)

**Priority:** CRITICAL
**Duration:** 16 hours
**Dependencies:** Phase S1

### Tasks

#### S2.1: Plans & Billing Plans ✅
- [x] PlanSeeder (existing - 4 plans)
- [ ] BillingPlanFactory
- [ ] BillingPlanSeeder
- **Data:** Free, Basic, Professional, Enterprise (with pricing)

#### S2.2: Accounts ✅
- [x] AccountFactory (existing)
- [x] AccountSeeder (existing - 2 accounts)
- **Enhancement:** Add 5 more demo accounts (7 total)

#### S2.3: Permission Profiles ✅
- [x] PermissionProfileFactory (existing)
- [x] PermissionProfileSeeder (existing - 3 profiles)

#### S2.4: Users ✅
- [x] UserFactory (existing)
- [x] UserSeeder (existing - 3 users)
- **Enhancement:** Add 20 more users (23 total across accounts)

#### S2.5: User Related Data
- [ ] UserAddressFactory
- [ ] UserAddressSeeder
- [ ] UserProfileFactory
- [ ] UserProfileSeeder
- [ ] UserSettingFactory
- [ ] UserSettingSeeder
- [ ] UserCustomSettingFactory
- [ ] UserCustomSettingSeeder
- [ ] ContactFactory
- [ ] ContactSeeder
- **Data:** 50 contacts across users

#### S2.6: API Keys ✅
- [x] ApiKeyFactory (existing)
- [ ] ApiKeySeeder
- **Data:** 10 API keys (5 active, 3 expired, 2 revoked)

#### S2.7: User Authorizations
- [ ] UserAuthorizationFactory
- [ ] UserAuthorizationSeeder
- **Data:** 15 authorization records

**Deliverables:**
- 8 new factories
- 8 new seeders
- Enhanced existing seeders (Account, User)

---

## Phase S3: Envelopes Module (14 models)

**Priority:** CRITICAL
**Duration:** 24 hours
**Dependencies:** Phase S2

### Tasks

#### S3.1: Envelopes
- [ ] EnvelopeFactory
- [ ] EnvelopeSeeder
- **Data:** 100 envelopes (statuses: draft 20, sent 30, delivered 20, completed 25, voided 5)
- **States:** withDocuments(), withRecipients(), withTabs(), draft(), sent(), completed(), voided()

#### S3.2: Envelope Documents
- [ ] EnvelopeDocumentFactory
- [ ] EnvelopeDocumentSeeder
- **Data:** 250 documents (avg 2.5 docs per envelope)
- **Types:** PDF (60%), Word (20%), Excel (10%), Images (10%)

#### S3.3: Envelope Recipients
- [ ] EnvelopeRecipientFactory
- [ ] EnvelopeRecipientSeeder
- **Data:** 400 recipients (avg 4 per envelope)
- **Types:** Signer (70%), Approver (15%), Viewer (10%), CC (5%)

#### S3.4: Envelope Tabs
- [ ] EnvelopeTabFactory
- [ ] EnvelopeTabSeeder
- **Data:** 1000 tabs (avg 10 per envelope)
- **Types:** All 27 tab types (signature, initial, text, date, etc.)

#### S3.5: Envelope Custom Fields
- [ ] EnvelopeCustomFieldFactory
- [ ] EnvelopeCustomFieldSeeder
- **Data:** 200 custom fields (2 per envelope)

#### S3.6: Envelope Attachments
- [ ] EnvelopeAttachmentFactory
- [ ] EnvelopeAttachmentSeeder
- **Data:** 50 attachments

#### S3.7: Envelope Audit Events
- [ ] EnvelopeAuditEventFactory
- [ ] EnvelopeAuditEventSeeder
- **Data:** 500 audit events (5 per envelope avg)

#### S3.8: Envelope Views
- [ ] EnvelopeViewFactory
- [ ] EnvelopeViewSeeder
- **Data:** 300 view records

#### S3.9: Envelope Workflows
- [ ] EnvelopeWorkflowFactory
- [ ] EnvelopeWorkflowSeeder
- [ ] EnvelopeWorkflowStepFactory
- [ ] EnvelopeWorkflowStepSeeder
- **Data:** 30 workflows with 100 steps

#### S3.10: Envelope Locks
- [ ] EnvelopeLockFactory
- [ ] EnvelopeLockSeeder
- **Data:** 10 active locks

#### S3.11: Envelope Transfer Rules
- [ ] EnvelopeTransferRuleFactory
- [ ] EnvelopeTransferRuleSeeder
- **Data:** 20 transfer rules

**Deliverables:**
- 14 new factories
- 14 new seeders
- Sample PDF files for testing

---

## Phase S4: Templates & Documents (6 models)

**Priority:** HIGH
**Duration:** 10 hours
**Dependencies:** Phase S2, S3

### Tasks

#### S4.1: Templates
- [ ] TemplateFactory
- [ ] TemplateSeeder
- **Data:** 25 templates (various types)
- **States:** withDocuments(), withRecipients(), withTabs(), public(), private()

#### S4.2: Favorite Templates
- [ ] FavoriteTemplateFactory
- [ ] FavoriteTemplateSeeder
- **Data:** 50 favorites (users favoriting templates)

#### S4.3: Shared Access
- [ ] SharedAccessFactory
- [ ] SharedAccessSeeder
- **Data:** 40 sharing records (envelopes + templates)

#### S4.4: Folders
- [ ] FolderFactory
- [ ] FolderSeeder
- **Data:** 30 folders (hierarchical structure)

#### S4.5: Chunked Uploads
- [ ] ChunkedUploadFactory
- [ ] ChunkedUploadSeeder
- **Data:** 15 uploads (10 completed, 5 in-progress)

#### S4.6: Captive Recipients
- [ ] CaptiveRecipientFactory
- [ ] CaptiveRecipientSeeder
- **Data:** 30 captive recipients

**Deliverables:**
- 6 new factories
- 6 new seeders

---

## Phase S5: Recipients & Routing (5 models)

**Priority:** HIGH
**Duration:** 8 hours
**Dependencies:** Phase S2

### Tasks

#### S5.1: Signing Groups
- [ ] SigningGroupFactory
- [ ] SigningGroupSeeder
- **Data:** 10 signing groups with members

#### S5.2: User Groups
- [ ] UserGroupFactory
- [ ] UserGroupSeeder
- **Data:** 8 user groups with members and brands

#### S5.3: Recipient Document Visibility
- [ ] RecipientDocumentVisibilityFactory
- [ ] RecipientDocumentVisibilitySeeder
- **Data:** 100 visibility records

#### S5.4: Custom Tabs
- [ ] CustomTabFactory
- [ ] CustomTabSeeder
- **Data:** 50 custom tab definitions

**Deliverables:**
- 4 new factories
- 4 new seeders

---

## Phase S6: Billing & Payments (5 models)

**Priority:** MEDIUM
**Duration:** 8 hours
**Dependencies:** Phase S2

### Tasks

#### S6.1: Billing Charges
- [ ] BillingChargeFactory
- [ ] BillingChargeSeeder
- **Data:** 80 charges (across accounts)

#### S6.2: Billing Invoices
- [ ] BillingInvoiceFactory
- [ ] BillingInvoiceSeeder
- **Data:** 30 invoices (paid, pending, overdue)

#### S6.3: Billing Invoice Items
- [ ] BillingInvoiceItemFactory
- [ ] BillingInvoiceItemSeeder
- **Data:** 100 invoice items

#### S6.4: Billing Payments
- [ ] BillingPaymentFactory
- [ ] BillingPaymentSeeder
- **Data:** 25 payments (completed, pending, failed)

**Deliverables:**
- 4 new factories (BillingPlan already planned in S2)
- 4 new seeders

---

## Phase S7: Branding & Customization (8 models)

**Priority:** MEDIUM
**Duration:** 10 hours
**Dependencies:** Phase S2

### Tasks

#### S7.1: Brands
- [ ] BrandFactory
- [ ] BrandSeeder
- **Data:** 10 brands (across accounts)

#### S7.2: Brand Logos
- [ ] BrandLogoFactory
- [ ] BrandLogoSeeder
- **Data:** 15 logos (primary + secondary)

#### S7.3: Brand Resources
- [ ] BrandResourceFactory
- [ ] BrandResourceSeeder
- **Data:** 30 resources

#### S7.4: Brand Email Contents
- [ ] BrandEmailContentFactory
- [ ] BrandEmailContentSeeder
- **Data:** 20 email templates

#### S7.5: Account Custom Fields
- [ ] AccountCustomFieldFactory
- [ ] AccountCustomFieldSeeder
- **Data:** 25 custom fields

#### S7.6: Consumer Disclosures
- [ ] ConsumerDisclosureFactory
- [ ] ConsumerDisclosureSeeder
- **Data:** 20 disclosures (multiple languages)

#### S7.7: Watermark Configurations
- [ ] WatermarkConfigurationFactory
- [ ] WatermarkConfigurationSeeder
- **Data:** 5 watermark configs

#### S7.8: eNote Configurations
- [ ] EnoteConfigurationFactory
- [ ] EnoteConfigurationSeeder
- **Data:** 3 eNote configs

**Deliverables:**
- 8 new factories
- 8 new seeders

---

## Phase S8: Bulk Operations (3 models)

**Priority:** MEDIUM
**Duration:** 6 hours
**Dependencies:** Phase S2, S3

### Tasks

#### S8.1: Bulk Send Batches
- [ ] BulkSendBatchFactory
- [ ] BulkSendBatchSeeder
- **Data:** 10 batches (processing, completed, failed)

#### S8.2: Bulk Send Lists
- [ ] BulkSendListFactory
- [ ] BulkSendListSeeder
- **Data:** 15 lists

#### S8.3: Bulk Send Recipients
- [ ] BulkSendRecipientFactory
- [ ] BulkSendRecipientSeeder
- **Data:** 150 recipients (across batches)

**Deliverables:**
- 3 new factories
- 3 new seeders

---

## Phase S9: Connect & Webhooks (4 models)

**Priority:** LOW
**Duration:** 6 hours
**Dependencies:** Phase S2

### Tasks

#### S9.1: Connect Configurations
- [ ] ConnectConfigurationFactory
- [ ] ConnectConfigurationSeeder
- **Data:** 8 webhook configs

#### S9.2: Connect Logs
- [ ] ConnectLogFactory
- [ ] ConnectLogSeeder
- **Data:** 200 webhook delivery logs

#### S9.3: Connect Failures
- [ ] ConnectFailureFactory
- [ ] ConnectFailureSeeder
- **Data:** 20 failed deliveries

#### S9.4: Connect OAuth Configurations
- [ ] ConnectOAuthConfigFactory
- [ ] ConnectOAuthConfigSeeder
- **Data:** 5 OAuth configs

**Deliverables:**
- 4 new factories
- 4 new seeders

---

## Phase S10: Workspaces & Folders (3 models)

**Priority:** LOW
**Duration:** 4 hours
**Dependencies:** Phase S2

### Tasks

#### S10.1: Workspaces
- [ ] WorkspaceFactory
- [ ] WorkspaceSeeder
- **Data:** 5 workspaces

#### S10.2: Workspace Folders
- [ ] WorkspaceFolderFactory
- [ ] WorkspaceFolderSeeder
- **Data:** 15 workspace folders

#### S10.3: Workspace Files
- [ ] WorkspaceFileFactory
- [ ] WorkspaceFileSeeder
- **Data:** 40 files

**Deliverables:**
- 3 new factories
- 3 new seeders

---

## Phase S11: PowerForms & Advanced (2 models)

**Priority:** LOW
**Duration:** 3 hours
**Dependencies:** Phase S2, S3

### Tasks

#### S11.1: PowerForms
- [ ] PowerFormFactory
- [ ] PowerFormSeeder
- **Data:** 8 powerforms

#### S11.2: PowerForm Submissions
- [ ] PowerFormSubmissionFactory
- [ ] PowerFormSubmissionSeeder
- **Data:** 30 submissions

**Deliverables:**
- 2 new factories
- 2 new seeders

---

## Phase S12: Signatures & Seals (4 models)

**Priority:** MEDIUM
**Duration:** 6 hours
**Dependencies:** Phase S2

### Tasks

#### S12.1: Signatures
- [ ] SignatureFactory
- [ ] SignatureSeeder
- **Data:** 40 signatures (account + user signatures)

#### S12.2: Signature Images
- [ ] SignatureImageFactory
- [ ] SignatureImageSeeder
- **Data:** 60 signature images

#### S12.3: Seals
- [ ] SealFactory
- [ ] SealSeeder
- **Data:** 10 electronic seals

**Deliverables:**
- 3 new factories (SignatureProvider already done)
- 3 new seeders

---

## Phase S13: Logging & Diagnostics (2 models)

**Priority:** LOW
**Duration:** 3 hours
**Dependencies:** Phase S2, S3

### Tasks

#### S13.1: Request Logs
- [ ] RequestLogFactory
- [ ] RequestLogSeeder
- **Data:** 500 request logs

#### S13.2: Audit Logs
- [ ] AuditLogFactory
- [ ] AuditLogSeeder
- **Data:** 300 audit logs

**Deliverables:**
- 2 new factories
- 2 new seeders

---

## Summary Statistics

### Total Work Required

| Item | Count | Status |
|------|-------|--------|
| **Total Models** | 70 | - |
| **Factories to Create** | 66 | 4 done, 62 pending |
| **Seeders to Create** | 55 | 8 done, 47 pending |
| **Total Hours** | ~120h | Planning phase |

### Completion Tracking

- ✅ **Completed:** 8 seeders, 4 factories (11%)
- 🔄 **In Progress:** 0
- ⏳ **Pending:** 47 seeders, 62 factories (89%)

---

## Seeding Order (Dependency Graph)

```
Level 1 (No Dependencies):
├── FileType
├── SupportedLanguage
├── SignatureProvider
├── IdentityVerificationWorkflow
├── TabSetting
├── NotificationDefault
├── PasswordRule
└── EnvelopePurgeConfiguration

Level 2 (Depends on Level 1):
├── Plan
├── BillingPlan
└── AccountSettings

Level 3 (Depends on Level 2):
├── Account
└── PermissionProfile

Level 4 (Depends on Level 3):
├── User
├── Brand
├── Folder
├── SigningGroup
├── UserGroup
└── Workspace

Level 5 (Depends on Level 4):
├── UserAddress
├── UserProfile
├── UserSetting
├── UserCustomSetting
├── Contact
├── ApiKey
├── UserAuthorization
├── BrandLogo
├── BrandResource
├── BrandEmailContent
├── AccountCustomField
├── ConsumerDisclosure
├── WatermarkConfiguration
├── EnoteConfiguration
├── Signature
├── SignatureImage
├── Seal
├── ConnectConfiguration
├── ConnectOAuthConfig
├── WorkspaceFolder
├── WorkspaceFile
└── PowerForm

Level 6 (Depends on Level 5):
├── Template
├── Envelope
├── BulkSendBatch
├── BulkSendList
├── BillingCharge
├── BillingInvoice
└── PowerFormSubmission

Level 7 (Depends on Level 6):
├── EnvelopeDocument
├── EnvelopeRecipient
├── EnvelopeCustomField
├── EnvelopeAttachment
├── EnvelopeWorkflow
├── EnvelopeLock
├── EnvelopeTransferRule
├── FavoriteTemplate
├── SharedAccess
├── CaptiveRecipient
├── ChunkedUpload
├── BulkSendRecipient
├── BillingInvoiceItem
├── BillingPayment
├── ConnectLog
└── ConnectFailure

Level 8 (Depends on Level 7):
├── EnvelopeTab
├── EnvelopeAuditEvent
├── EnvelopeView
├── EnvelopeWorkflowStep
├── RecipientDocumentVisibility
├── CustomTab
├── RequestLog
└── AuditLog
```

---

## Implementation Guidelines

### Factory Standards

```php
// Factory template
class ModelNameFactory extends Factory
{
    protected $model = ModelName::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            // ... other fields
        ];
    }

    // State modifiers
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    // Relationships
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }
}
```

### Seeder Standards

```php
// Seeder template
class ModelNameSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data (optional, for dev only)
        if (App::environment('local')) {
            ModelName::truncate();
        }

        // Seed specific records
        ModelName::factory()->count(10)->create();

        // Or seed with relationships
        Account::all()->each(function ($account) {
            ModelName::factory()
                ->count(5)
                ->forAccount($account)
                ->create();
        });
    }
}
```

### Testing Seeders

```bash
# Refresh and seed
php artisan migrate:fresh --seed

# Seed specific seeder
php artisan db:seed --class=EnvelopeSeeder

# Test factory in tinker
php artisan tinker
>>> Envelope::factory()->count(5)->create()
```

---

## File Organization

```
database/
├── factories/
│   ├── AccountFactory.php ✅
│   ├── ApiKeyFactory.php ✅
│   ├── PermissionProfileFactory.php ✅
│   ├── UserFactory.php ✅
│   ├── EnvelopeFactory.php ⏳
│   ├── TemplateFactory.php ⏳
│   └── ... (62 more to create)
│
├── seeders/
│   ├── DatabaseSeeder.php ✅
│   ├── Reference/
│   │   ├── FileTypeSeeder.php ✅
│   │   ├── SupportedLanguageSeeder.php ✅
│   │   ├── SignatureProviderSeeder.php ✅
│   │   ├── IdentityVerificationWorkflowSeeder.php ⏳
│   │   └── ... (5 more reference seeders)
│   │
│   ├── Core/
│   │   ├── PlanSeeder.php ✅
│   │   ├── AccountSeeder.php ✅
│   │   ├── PermissionProfileSeeder.php ✅
│   │   ├── UserSeeder.php ✅
│   │   └── ... (8 more core seeders)
│   │
│   ├── Envelopes/
│   │   └── ... (14 envelope seeders)
│   │
│   ├── Templates/
│   │   └── ... (6 template seeders)
│   │
│   └── ... (other modules)
│
└── data/ (optional)
    ├── file-types.json
    ├── languages.json
    └── ... (reference data files)
```

---

## Next Steps

1. **Review and approve** this task list
2. **Prioritize phases** based on immediate needs
3. **Start with Phase S1** (Reference Data) - 8 hours
4. **Implement Phase S2** (Core Infrastructure) - 16 hours
5. **Build Phase S3** (Envelopes Module) - 24 hours
6. **Continue sequentially** through remaining phases

---

**Document Version:** 1.0
**Last Updated:** 2025-11-17
**Status:** Ready for Implementation
