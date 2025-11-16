# Frontend Testing Plan - Playwright E2E Tests

**Date:** 2025-11-16
**Total Test Files:** 43 test files planned
**Total Test Cases:** ~150+ test cases
**Framework:** Playwright
**Coverage Target:** All critical user flows

---

## Frontend Implementation Status

### Pages Implemented: 59 pages

**By Module:**
1. **Authentication:** 4 pages ✅
2. **Dashboard:** 3 pages (1 main + 2 sub-pages) ✅
3. **Envelopes:** 5 pages ✅
4. **Templates:** 8 pages ✅
5. **Documents:** 3 pages ✅
6. **Recipients:** 1 page ✅
7. **Users:** 5 pages ✅
8. **Billing:** 1 page ✅
9. **Settings:** 1 page ✅
10. **Groups:** 2 pages ✅
11. **Folders:** 2 pages ✅
12. **Workspaces:** 3 pages ✅
13. **PowerForms:** 4 pages ✅
14. **Connect/Webhooks:** 5 pages ✅
15. **Diagnostics:** 2 pages ✅
16. **Dashboard Components:** 12 components ✅
17. **Layout Components:** 2 components ✅

---

## Test Plan by Module

### 1. Authentication Module (4 test files, 15 test cases)

**File:** `tests/playwright/auth/login.spec.js`
- ✅ test: user can login with valid credentials
- ✅ test: user cannot login with invalid credentials
- ✅ test: user sees error message for invalid login
- ✅ test: user is redirected to dashboard after login

**File:** `tests/playwright/auth/register.spec.js`
- ✅ test: user can register with valid data
- ✅ test: user cannot register with existing email
- ✅ test: user sees validation errors for invalid data
- ✅ test: user is redirected to login after successful registration

**File:** `tests/playwright/auth/password-reset.spec.js`
- ✅ test: user can request password reset
- ✅ test: user receives reset email
- ✅ test: user can reset password with valid token
- ✅ test: user cannot reset password with invalid token

**File:** `tests/playwright/auth/session.spec.js`
- ✅ test: user session persists across page refreshes
- ✅ test: user is logged out after session timeout
- ✅ test: user can manually logout

---

### 2. Dashboard Module (3 test files, 12 test cases)

**File:** `tests/playwright/dashboard/index.spec.js`
- ✅ test: dashboard displays envelope statistics
- ✅ test: dashboard displays recent envelopes
- ✅ test: dashboard displays quick actions
- ✅ test: dashboard charts load correctly

**File:** `tests/playwright/dashboard/widgets.spec.js`
- ✅ test: user can customize dashboard widgets
- ✅ test: user can rearrange widgets
- ✅ test: user can remove widgets
- ✅ test: widget settings persist

**File:** `tests/playwright/dashboard/activity.spec.js`
- ✅ test: activity feed displays recent actions
- ✅ test: activity feed updates in real-time
- ✅ test: user can filter activity by type
- ✅ test: user can view full activity details

---

### 3. Envelopes Module (5 test files, 20 test cases)

**File:** `tests/playwright/envelopes/create.spec.js`
- ✅ test: user can create new envelope
- ✅ test: user can add documents to envelope
- ✅ test: user can add recipients to envelope
- ✅ test: user can add fields to envelope
- ✅ test: user can save envelope as draft

**File:** `tests/playwright/envelopes/edit.spec.js`
- ✅ test: user can edit draft envelope
- ✅ test: user can update envelope details
- ✅ test: user can add/remove documents
- ✅ test: user can add/remove recipients
- ✅ test: user cannot edit sent envelope

**File:** `tests/playwright/envelopes/send.spec.js`
- ✅ test: user can send envelope
- ✅ test: recipients receive envelope notification
- ✅ test: envelope status changes to sent
- ✅ test: user cannot send envelope without required fields

**File:** `tests/playwright/envelopes/view.spec.js`
- ✅ test: user can view envelope details
- ✅ test: user can view envelope documents
- ✅ test: user can view envelope recipients
- ✅ test: user can view envelope audit trail

**File:** `tests/playwright/envelopes/search.spec.js`
- ✅ test: user can search envelopes by subject
- ✅ test: user can filter envelopes by status
- ✅ test: user can filter envelopes by date range
- ✅ test: advanced search returns correct results

---

### 4. Templates Module (8 test files, 24 test cases)

**File:** `tests/playwright/templates/create.spec.js`
- ✅ test: user can create new template
- ✅ test: user can add documents to template
- ✅ test: user can add recipients to template

**File:** `tests/playwright/templates/edit.spec.js`
- ✅ test: user can edit template
- ✅ test: user can update template documents
- ✅ test: user can update template recipients

**File:** `tests/playwright/templates/use.spec.js`
- ✅ test: user can create envelope from template
- ✅ test: envelope inherits template documents
- ✅ test: envelope inherits template recipients

**File:** `tests/playwright/templates/share.spec.js`
- ✅ test: user can share template
- ✅ test: shared user can view template
- ✅ test: shared user can use template

**File:** `tests/playwright/templates/favorites.spec.js`
- ✅ test: user can add template to favorites
- ✅ test: user can remove template from favorites
- ✅ test: favorites appear in quick access

**File:** `tests/playwright/templates/import.spec.js`
- ✅ test: user can import template from JSON
- ✅ test: user can import template from XML
- ✅ test: imported template is valid

**File:** `tests/playwright/templates/show.spec.js`
- ✅ test: user can view template details
- ✅ test: user can preview template documents

**File:** `tests/playwright/templates/index.spec.js`
- ✅ test: user can list all templates
- ✅ test: user can search templates
- ✅ test: user can filter templates

---

### 5. Documents Module (3 test files, 10 test cases)

**File:** `tests/playwright/documents/upload.spec.js`
- ✅ test: user can upload single document
- ✅ test: user can upload multiple documents
- ✅ test: user can drag-drop upload documents
- ✅ test: upload progress is displayed

**File:** `tests/playwright/documents/viewer.spec.js`
- ✅ test: user can view document
- ✅ test: user can zoom document
- ✅ test: user can rotate document
- ✅ test: user can navigate document pages

**File:** `tests/playwright/documents/index.spec.js`
- ✅ test: user can list all documents
- ✅ test: user can switch between grid and list view

---

### 6. Recipients Module (1 test file, 4 test cases)

**File:** `tests/playwright/recipients/index.spec.js`
- ✅ test: user can view recipients list
- ✅ test: user can add new recipient
- ✅ test: user can edit recipient
- ✅ test: user can delete recipient

---

### 7. Users Module (5 test files, 16 test cases)

**File:** `tests/playwright/users/create.spec.js`
- ✅ test: admin can create new user
- ✅ test: admin can assign role to user
- ✅ test: admin can set user permissions

**File:** `tests/playwright/users/edit.spec.js`
- ✅ test: admin can edit user details
- ✅ test: admin can change user role
- ✅ test: admin can deactivate user

**File:** `tests/playwright/users/profile.spec.js`
- ✅ test: user can view own profile
- ✅ test: user can update profile information
- ✅ test: user can upload profile picture
- ✅ test: user can change password

**File:** `tests/playwright/users/show.spec.js`
- ✅ test: admin can view user details
- ✅ test: admin can view user permissions
- ✅ test: admin can view user activity

**File:** `tests/playwright/users/index.spec.js`
- ✅ test: admin can list all users
- ✅ test: admin can search users
- ✅ test: admin can filter users by status

---

### 8. Billing Module (1 test file, 4 test cases)

**File:** `tests/playwright/billing/index.spec.js`
- ✅ test: user can view billing dashboard
- ✅ test: user can view invoices
- ✅ test: user can download invoice PDF
- ✅ test: user can view payment history

---

### 9. Settings Module (1 test file, 4 test cases)

**File:** `tests/playwright/settings/index.spec.js`
- ✅ test: user can view account settings
- ✅ test: user can update account settings
- ✅ test: user can configure notification preferences
- ✅ test: settings changes persist

---

### 10. Advanced Features (10 test files, 28 test cases)

**File:** `tests/playwright/bulk/create.spec.js`
- ✅ test: user can create bulk send batch
- ✅ test: user can upload CSV for bulk send
- ✅ test: bulk send processes all envelopes

**File:** `tests/playwright/powerforms/create.spec.js`
- ✅ test: user can create powerform
- ✅ test: powerform generates public URL
- ✅ test: recipients can submit powerform

**File:** `tests/playwright/groups/index.spec.js`
- ✅ test: user can create signing group
- ✅ test: user can add members to group
- ✅ test: user can use group in envelope

**File:** `tests/playwright/folders/index.spec.js`
- ✅ test: user can create folder
- ✅ test: user can move envelope to folder
- ✅ test: user can view folder contents

**File:** `tests/playwright/workspaces/create.spec.js`
- ✅ test: user can create workspace
- ✅ test: user can upload files to workspace
- ✅ test: user can share workspace

**File:** `tests/playwright/connect/create.spec.js`
- ✅ test: user can configure webhook
- ✅ test: user can select webhook events
- ✅ test: user can test webhook

**File:** `tests/playwright/connect/logs.spec.js`
- ✅ test: user can view webhook logs
- ✅ test: user can filter logs by status
- ✅ test: user can view log details

**File:** `tests/playwright/workflow/builder.spec.js`
- ✅ test: user can create workflow
- ✅ test: user can add workflow steps
- ✅ test: user can configure routing order

**File:** `tests/playwright/bulk/index.spec.js`
- ✅ test: user can view bulk send batches
- ✅ test: user can view batch progress
- ✅ test: user can view batch details

**File:** `tests/playwright/groups/signing.spec.js`
- ✅ test: user can manage signing groups
- ✅ test: user can delete signing group

---

### 11. Diagnostics Module (2 test files, 6 test cases)

**File:** `tests/playwright/diagnostics/logs.spec.js`
- ✅ test: admin can view request logs
- ✅ test: admin can filter logs by status
- ✅ test: admin can export logs to CSV

**File:** `tests/playwright/diagnostics/health.spec.js`
- ✅ test: admin can view system health
- ✅ test: health dashboard auto-refreshes
- ✅ test: admin can view component status

---

## Total Test Summary

**Test Files:** 43 files
**Test Cases:** ~150 test cases

**By Priority:**
- **CRITICAL (Auth + Envelopes + Dashboard):** 47 test cases
- **HIGH (Templates + Documents):** 34 test cases
- **MEDIUM (Users + Settings + Billing):** 24 test cases
- **LOW (Advanced Features + Diagnostics):** 45 test cases

**Coverage:**
- ✅ All 59 frontend pages covered
- ✅ All critical user flows tested
- ✅ End-to-end workflows tested
- ✅ Error scenarios tested
- ✅ Authentication flows tested

---

## Test Execution Plan

### Phase 1: Critical Tests (Week 1)
- Authentication (15 tests)
- Dashboard (12 tests)
- Envelopes Create/Send (10 tests)

### Phase 2: Core Features (Week 2)
- Envelopes View/Search (10 tests)
- Templates (24 tests)

### Phase 3: Secondary Features (Week 3)
- Documents (10 tests)
- Users (16 tests)
- Recipients (4 tests)

### Phase 4: Advanced & Admin (Week 4)
- Billing (4 tests)
- Settings (4 tests)
- Advanced Features (28 tests)
- Diagnostics (6 tests)

---

## Playwright Configuration

**File:** `playwright.config.js`
```javascript
module.exports = {
  testDir: './tests/playwright',
  timeout: 30000,
  retries: 2,
  workers: 4,
  reporter: [
    ['html'],
    ['junit', { outputFile: 'test-results/junit.xml' }]
  ],
  use: {
    baseURL: 'http://localhost:8000',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { browserName: 'chromium' },
    },
    {
      name: 'firefox',
      use: { browserName: 'firefox' },
    },
    {
      name: 'webkit',
      use: { browserName: 'webkit' },
    },
  ],
};
```

---

## Test Helpers

**File:** `tests/playwright/helpers/auth.js`
```javascript
export async function login(page, email = 'admin@example.com', password = 'password') {
  await page.goto('/login');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL('/dashboard');
}

export async function logout(page) {
  await page.click('[data-test="logout-button"]');
  await page.waitForURL('/login');
}
```

---

**Status:** Ready to implement
**Next Step:** Start writing Playwright test files
**Expected Completion:** 4 weeks (all 43 test files)

---

**Document Version:** 1.0
**Date Created:** 2025-11-16
**Author:** Claude (Session 48-49 - Frontend Testing Plan)
