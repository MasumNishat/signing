import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Settings', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/settings');
  });

  test('user can view account settings', async ({ page }) => {
    await expect(page.locator('[data-test="settings-form"]')).toBeVisible();
  });

  test('user can update account settings', async ({ page }) => {
    await page.fill('[data-test="company-name"]', 'Updated Company');
    await page.click('[data-test="save-settings"]');
  });

  test('user can configure notification preferences', async ({ page }) => {
    await page.click('[data-test="notifications-tab"]');
    await page.check('[data-test="email-notifications"]');
    await page.click('[data-test="save"]');
  });

  test('settings changes persist', async ({ page }) => {
    await page.fill('[data-test="company-name"]', 'Test Company');
    await page.click('[data-test="save-settings"]');
    await page.reload();
    await expect(page.locator('[data-test="company-name"]')).toHaveValue('Test Company');
  });
});
