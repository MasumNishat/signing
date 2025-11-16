/**
 * Dashboard - Main Dashboard Tests
 */

import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { waitForAPI } from '../helpers/common.js';

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('dashboard displays envelope statistics', async ({ page }) => {
    await waitForAPI(page, '/envelopes/statistics');
    await expect(page.locator('[data-test="stat-draft"]')).toBeVisible();
    await expect(page.locator('[data-test="stat-sent"]')).toBeVisible();
    await expect(page.locator('[data-test="stat-completed"]')).toBeVisible();
  });

  test('dashboard displays recent envelopes', async ({ page }) => {
    await waitForAPI(page, '/envelopes');
    await expect(page.locator('[data-test="recent-envelopes"]')).toBeVisible();
  });

  test('dashboard displays quick actions', async ({ page }) => {
    await expect(page.locator('[data-test="quick-action-create-envelope"]')).toBeVisible();
    await expect(page.locator('[data-test="quick-action-use-template"]')).toBeVisible();
  });

  test('dashboard charts load correctly', async ({ page }) => {
    await expect(page.locator('[data-test="chart-envelope-status"]')).toBeVisible();
    await expect(page.locator('[data-test="chart-signing-activity"]')).toBeVisible();
  });
});
