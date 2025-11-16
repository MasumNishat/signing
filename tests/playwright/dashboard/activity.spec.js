/**
 * Dashboard - Activity Feed Tests
 */

import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { waitForAPI } from '../helpers/common.js';

test.describe('Activity Feed', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/dashboard/activity');
  });

  test('activity feed displays recent actions', async ({ page }) => {
    await waitForAPI(page, '/audit_events');
    await expect(page.locator('[data-test="activity-item"]')).toHaveCount(10, { timeout: 5000 });
  });

  test('user can filter activity by type', async ({ page }) => {
    await page.selectOption('[data-test="activity-filter"]', 'envelope-sent');
    await waitForAPI(page, '/audit_events');
    const items = page.locator('[data-test="activity-item"]');
    await expect(items.first()).toContainText(/sent/i);
  });

  test('user can view full activity details', async ({ page }) => {
    await page.click('[data-test="activity-item"]:first-child [data-test="view-details"]');
    await expect(page.locator('[data-test="activity-modal"]')).toBeVisible();
  });
});
