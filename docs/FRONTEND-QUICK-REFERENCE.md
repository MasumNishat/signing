# Frontend Implementation - Quick Reference Guide

**Purpose:** Quick lookup for files, API endpoints, and task dependencies

---

## Quick Stats

- **Total Pages:** 89
- **Total Components:** 156
- **Total Phases:** 8 (16-20 weeks)
- **API Endpoints:** 358
- **Test Files:** ~50

---

## Phase Overview

| Phase | Duration | Priority | Pages | Components | Status |
|-------|----------|----------|-------|------------|--------|
| 1. Foundation | 2 weeks | CRITICAL | 0 | 47 | ⏳ Pending |
| 2. Auth & Dashboard | 2 weeks | CRITICAL | 7 | 20 | ⏳ Pending |
| 3. Envelopes Core | 3 weeks | CRITICAL | 12 | 28 | ⏳ Pending |
| 4. Signing Interface | 2 weeks | CRITICAL | 1 | 30 | ⏳ Pending |
| 5. Docs & Templates | 2 weeks | HIGH | 14 | 30 | ⏳ Pending |
| 6. Users & Billing | 2 weeks | MEDIUM | 24 | 46 | ⏳ Pending |
| 7. Advanced Features | 2 weeks | MEDIUM | 25 | 48 | ⏳ Pending |
| 8. Polish | 2 weeks | LOW | 6 | 10 | ⏳ Pending |

---

## API Endpoint Quick Reference

### Authentication Module
| Endpoint | Method | Page/Component | File |
|----------|--------|----------------|------|
| `/oauth/token` | POST | Login | `auth/login.blade.php:80-120` |
| `/oauth/userinfo` | GET | Login | `auth/login.blade.php:130-145` |
| `/accounts/{id}/users` | POST | Register | `auth/register.blade.php:90-140` |

### Dashboard Module
| Endpoint | Method | Page/Component | File |
|----------|--------|----------------|------|
| `/accounts/{id}/envelopes/statistics` | GET | Dashboard | `dashboard/index.blade.php:30-60` |
| `/billing/summary` | GET | Billing Widget | `components/dashboard/billing-summary.blade.php:20-40` |
| `/accounts/{id}/folders` | GET | Folder Widget | `components/dashboard/folder-widget.blade.php:15-35` |

### Envelopes Module (55 endpoints)
| Endpoint | Method | Page/Component | File |
|----------|--------|----------------|------|
| `/accounts/{id}/envelopes` | GET | List | `envelopes/index.blade.php:40-80` |
| `/accounts/{id}/envelopes` | POST | Create | `envelopes/create.blade.php:120-180` |
| `/accounts/{id}/envelopes/{eid}` | GET | Show | `envelopes/show.blade.php:30-60` |
| `/accounts/{id}/envelopes/{eid}` | PUT | Edit | `envelopes/edit.blade.php:90-130` |
| `/accounts/{id}/envelopes/{eid}` | DELETE | Delete | `components/envelope/list-table.blade.php:80-100` |
| `/accounts/{id}/envelopes/{eid}/send` | POST | Send | `envelopes/send.blade.php:60-90` |
| `/accounts/{id}/envelopes/{eid}/void` | POST | Void | `components/envelope/void-dialog.blade.php:40-70` |
| `/accounts/{id}/envelopes/{eid}/notification` | GET/PUT | Notification | `components/envelope/notification-settings.blade.php:25-60` |
| `/accounts/{id}/envelopes/{eid}/email_settings` | GET/PUT | Email | `components/envelope/email-settings.blade.php:25-60` |
| `/accounts/{id}/envelopes/{eid}/custom_fields` | GET/POST/PUT/DELETE | Custom Fields | `components/envelope/custom-fields.blade.php:30-90` |
| `/accounts/{id}/envelopes/{eid}/lock` | GET/POST/PUT/DELETE | Lock | `components/envelope/lock-indicator.blade.php:20-70` |
| `/accounts/{id}/envelopes/{eid}/audit_events` | GET | Audit Trail | `components/envelope/audit-timeline.blade.php:25-60` |
| `/accounts/{id}/envelopes/{eid}/workflow` | GET/PUT | Workflow | `envelopes/workflow.blade.php:50-120` |
| `/accounts/{id}/envelopes/{eid}/views/recipient` | POST | Signing URL | `envelopes/sign.blade.php:30-50` |

### Documents Module (24 endpoints)
| Endpoint | Method | Page/Component | File |
|----------|--------|----------------|------|
| `/accounts/{id}/envelopes/{eid}/documents` | GET | List | `documents/index.blade.php:40-70` |
| `/accounts/{id}/envelopes/{eid}/documents` | POST | Upload | `documents/upload.blade.php:80-120` |
| `/accounts/{id}/chunked_uploads` | POST | Chunked Upload | `components/document/chunked-upload.blade.php:50-120` |
| `/accounts/{id}/envelopes/{eid}/documents/{did}` | GET | Download | `components/document/viewer.blade.php:90-110` |

### Templates Module (33 endpoints)
| Endpoint | Method | Page/Component | File |
|----------|--------|----------------|------|
| `/accounts/{id}/templates` | GET | List | `templates/index.blade.php:40-80` |
| `/accounts/{id}/templates` | POST | Create | `templates/create.blade.php:100-150` |
| `/accounts/{id}/templates/{tid}` | GET/PUT | Edit | `templates/edit.blade.php:60-120` |
| `/accounts/{id}/templates/{tid}/documents` | GET/POST/PUT/DELETE | Documents | `components/template/document-manager.blade.php:40-100` |
| `/accounts/{id}/templates/{tid}/recipients` | GET/POST/PUT/DELETE | Recipients | `components/template/recipient-roles.blade.php:35-80` |

### Users Module (22 endpoints)
| Endpoint | Method | Page/Component | File |
|----------|--------|----------------|------|
| `/accounts/{id}/users` | GET | List | `users/index.blade.php:40-80` |
| `/accounts/{id}/users` | POST | Create | `users/create.blade.php:70-110` |
| `/accounts/{id}/users/{uid}` | GET/PUT | Edit | `users/edit.blade.php:50-100` |
| `/accounts/{id}/users/{uid}/profile` | GET/PUT | Profile | `users/profile.blade.php:40-90` |
| `/accounts/{id}/users/{uid}/settings` | GET/PUT | Settings | `users/settings.blade.php:40-90` |

### Billing Module (21 endpoints)
| Endpoint | Method | Page/Component | File |
|----------|--------|----------------|------|
| `/accounts/{id}/billing_invoices` | GET | Invoices | `billing/invoices.blade.php:40-80` |
| `/accounts/{id}/billing_payments` | GET/POST | Payments | `billing/payments.blade.php:50-100` |
| `/billing_plans` | GET | Plans | `billing/plans.blade.php:30-70` |

---

## Component Dependencies

### Phase 1 Foundation Components (Required for all phases)
- ✅ Layout: app, auth, header, sidebar, footer, mobile-menu, breadcrumbs
- ✅ UI: button, badge, alert, toast, modal, dropdown, tooltip, tabs, card, pagination, spinner, progress, skeleton
- ✅ Form: input, textarea, select, checkbox, radio, toggle, file-upload, date-picker, time-picker, color-picker, multi-select, autocomplete
- ✅ Table: table, thead, tbody, row, cell, sortable-header, actions, bulk-actions, filter, search
- ✅ Theme: switcher, theme.js

### Module Component Dependencies
| Module | Requires | Components |
|--------|----------|------------|
| Auth | Layout (auth) | login-form, register-form, oauth-buttons |
| Dashboard | Layout (app), UI (card, chart) | stat-card, charts, widgets |
| Envelopes | Layout (app), UI (all), Form (all) | 28 envelope components |
| Documents | Layout (app), UI (modal, spinner) | 14 document components |
| Templates | Layout (app), Envelope components | 16 template components |

---

## File Location Quick Reference

### Layouts
```
resources/views/components/layout/
├── app.blade.php (1-180)
├── auth.blade.php (1-120)
├── header.blade.php (1-150)
├── sidebar.blade.php (1-200)
├── footer.blade.php (1-80)
├── mobile-menu.blade.php (1-100)
└── breadcrumbs.blade.php (1-60)
```

### UI Components
```
resources/views/components/ui/
├── button.blade.php (1-100)
├── badge.blade.php (1-60)
├── alert.blade.php (1-80)
├── toast.blade.php (1-100)
├── modal.blade.php (1-150)
├── dropdown.blade.php (1-120)
├── tooltip.blade.php (1-80)
├── tabs.blade.php (1-100)
├── accordion.blade.php (1-100)
├── card.blade.php (1-80)
├── pagination.blade.php (1-120)
├── loading-spinner.blade.php (1-50)
├── progress-bar.blade.php (1-60)
├── skeleton.blade.php (1-70)
└── icon-button.blade.php (1-60)
```

### Form Components
```
resources/views/components/form/
├── input.blade.php (1-120)
├── textarea.blade.php (1-100)
├── select.blade.php (1-120)
├── checkbox.blade.php (1-80)
├── radio.blade.php (1-80)
├── toggle.blade.php (1-90)
├── file-upload.blade.php (1-150)
├── date-picker.blade.php (1-180)
├── time-picker.blade.php (1-150)
├── color-picker.blade.php (1-120)
├── multi-select.blade.php (1-200)
├── autocomplete.blade.php (1-180)
├── validation-error.blade.php (1-40)
├── label.blade.php (1-40)
└── help-text.blade.php (1-30)
```

### JavaScript Files
```
public/js/
├── app.js (main entry)
├── alpine-setup.js (1-200) - Alpine initialization
├── axios-setup.js (1-150) - API client
├── auth.js (1-300) - Auth logic
├── theme.js (1-180) - Theme management
├── charts.js (1-400) - Chart.js integration
├── chunked-upload.js (1-250) - Large file upload
├── field-editor.js (1-500) - Envelope field editor
├── signing-interface.js (1-600) - Signing UI
├── signature-pad.js (1-400) - Signature drawing
├── document-viewer.js (1-400) - PDF viewer
└── workflow-builder.js (1-600) - Workflow editor
```

### CSS Files
```
resources/css/
├── app.css (main CSS)
├── components.css (component styles)
└── themes/
    ├── default.css (1-150)
    ├── dark.css (1-150)
    ├── blue.css (1-150)
    ├── green.css (1-150)
    ├── purple.css (1-150)
    └── ocean.css (1-150)
```

---

## Axios Integration Pattern

### Setup (axios-setup.js)
```javascript
// Lines 1-50: Configuration
const api = axios.create({
  baseURL: '/api/v2.1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Lines 51-80: Request interceptor
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Lines 81-110: Response interceptor
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

### Usage Pattern (in components)
```javascript
Alpine.data('componentName', () => ({
  data: [],
  loading: false,
  error: null,

  async loadData() {
    this.loading = true;
    this.error = null;

    try {
      const response = await api.get('/endpoint');
      this.data = response.data.data;
    } catch (error) {
      this.error = error.response?.data?.message || 'Failed to load';
      this.$store.toast.add({
        type: 'error',
        message: this.error
      });
    } finally {
      this.loading = false;
    }
  },

  async saveData(data) {
    this.loading = true;
    try {
      const response = await api.post('/endpoint', data);
      this.$store.toast.add({
        type: 'success',
        message: 'Saved successfully'
      });
      return response.data;
    } catch (error) {
      this.$store.toast.add({
        type: 'error',
        message: error.response?.data?.message || 'Failed to save'
      });
      throw error;
    } finally {
      this.loading = false;
    }
  }
}));
```

---

## Alpine.js Store Pattern

### Global Stores (alpine-setup.js lines 50-180)

**Auth Store:**
```javascript
Alpine.store('auth', {
  user: Alpine.$persist(null),
  token: Alpine.$persist(null),

  isAuthenticated() {
    return this.token !== null;
  },

  hasRole(role) {
    return this.user?.role === role;
  },

  hasPermission(permission) {
    return this.user?.permissions?.includes(permission);
  },

  logout() {
    this.user = null;
    this.token = null;
    localStorage.clear();
    window.location.href = '/login';
  }
});
```

**Theme Store:**
```javascript
Alpine.store('theme', {
  current: Alpine.$persist('default'),
  mode: Alpine.$persist('light'),

  setTheme(theme) {
    this.current = theme;
    document.documentElement.setAttribute('data-theme', theme);
  },

  toggleMode() {
    this.mode = this.mode === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-mode', this.mode);
  }
});
```

**Toast Store:**
```javascript
Alpine.store('toast', {
  notifications: [],

  add(notification) {
    const id = Date.now();
    this.notifications.push({
      id,
      type: notification.type || 'info',
      message: notification.message,
      duration: notification.duration || 5000
    });

    setTimeout(() => this.remove(id), notification.duration || 5000);
  },

  remove(id) {
    this.notifications = this.notifications.filter(n => n.id !== id);
  }
});
```

**Sidebar Store:**
```javascript
Alpine.store('sidebar', {
  isOpen: false,

  toggle() {
    this.isOpen = !this.isOpen;
  },

  close() {
    this.isOpen = false;
  }
});
```

---

## Playwright Test Pattern

### Test File Structure
```javascript
// tests/playwright/module/feature.spec.js
import { test, expect } from '@playwright/test';

test.describe('Feature Name', () => {
  test.beforeEach(async ({ page }) => {
    // Login
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('should perform action', async ({ page }) => {
    // Navigate
    await page.goto('/envelopes/create');

    // Interact
    await page.fill('input[name="subject"]', 'Test Envelope');
    await page.click('button[type="submit"]');

    // Assert
    await expect(page.locator('.success-message')).toBeVisible();
  });

  test('should show validation error', async ({ page }) => {
    await page.goto('/envelopes/create');
    await page.click('button[type="submit"]');
    await expect(page.locator('.error-message')).toContainText('required');
  });
});
```

### Test Categories
- `tests/playwright/auth/` - Authentication flows
- `tests/playwright/dashboard/` - Dashboard functionality
- `tests/playwright/envelopes/` - Envelope operations
- `tests/playwright/signing/` - Signing interface
- `tests/playwright/documents/` - Document management
- `tests/playwright/templates/` - Template operations
- `tests/playwright/users/` - User management
- `tests/playwright/billing/` - Billing operations
- `tests/playwright/advanced/` - Advanced features

---

## Common Tasks

### Create New Page
1. Create blade file: `resources/views/module/page.blade.php`
2. Use layout: `<x-layout.app title="Page Title">`
3. Add Alpine data: `<div x-data="pageName">`
4. Add API calls with axios
5. Add route in `routes/web.php`
6. Create Playwright test

### Create New Component
1. Create blade file: `resources/views/components/module/component.blade.php`
2. Define props: `@props(['prop1', 'prop2'])`
3. Add Alpine behavior if needed
4. Style with Tailwind classes
5. Document in component library

### Add API Endpoint Integration
1. Identify endpoint in `docs/FRONTEND-IMPLEMENTATION-PLAN.md`
2. Add axios call in Alpine data
3. Handle loading state
4. Handle errors with toast
5. Update UI reactively

### Add Form Validation
1. Use form components with `error` prop
2. Add Alpine validation logic
3. Show errors with `<x-form.validation-error>`
4. Validate before API call
5. Show server errors from API response

---

## Troubleshooting

### Common Issues

**Issue:** Alpine.js not initializing
- **Fix:** Check `app.js` imports Alpine setup
- **File:** `resources/js/app.js:5-10`

**Issue:** Theme not applying
- **Fix:** Verify `data-theme` and `data-mode` attributes
- **File:** `public/js/theme.js:150-180`

**Issue:** API calls fail with 401
- **Fix:** Check token in localStorage
- **File:** `public/js/axios-setup.js:51-80`

**Issue:** Components not rendering
- **Fix:** Verify component path and props
- **File:** Component blade file

**Issue:** Tailwind classes not applying
- **Fix:** Check `tailwind.config.js` content paths
- **File:** `tailwind.config.js:5-10`

---

## Implementation Checklist

### Phase 1: Foundation
- [ ] Install Tailwind CSS 4
- [ ] Install Alpine.js
- [ ] Create theme system (6 themes)
- [ ] Create layout components (7 files)
- [ ] Create UI components (15 files)
- [ ] Create form components (15 files)
- [ ] Create table components (10 files)
- [ ] Setup Vite build

### Phase 2: Auth & Dashboard
- [ ] Login page + API integration
- [ ] Register page + API integration
- [ ] Password reset flow
- [ ] Dashboard main page
- [ ] Dashboard charts
- [ ] Dashboard widgets
- [ ] Playwright auth tests

### Phase 3-8: Continue...

---

## Resources

- **Main Plan:** `docs/FRONTEND-IMPLEMENTATION-PLAN.md`
- **Detailed Tasks:** `docs/FRONTEND-DETAILED-TASKS.md`
- **API Docs:** `docs/openapi.json`
- **Backend Routes:** `routes/api/v2.1/*.php`
- **Penguin UI:** https://penguinui.com

---

**Last Updated:** 2025-11-16
**Version:** 1.0
