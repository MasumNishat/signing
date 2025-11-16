import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Envelope View', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('user can view envelope details', async ({ page }) => {
    await page.goto('/envelopes/envelope-id');
    await expect(page.locator('[data-test="envelope-subject"]')).toBeVisible();
  });

  test('user can view envelope documents', async ({ page }) => {
    await page.goto('/envelopes/envelope-id');
    await page.click('[data-test="documents-tab"]');
    await expect(page.locator('[data-test="document-viewer"]')).toBeVisible();
  });

  test('user can view envelope recipients', async ({ page }) => {
    await page.goto('/envelopes/envelope-id');
    await page.click('[data-test="recipients-tab"]');
    await expect(page.locator('[data-test="recipient-list"]')).toBeVisible();
  });

  test('user can view envelope audit trail', async ({ page }) => {
    await page.goto('/envelopes/envelope-id');
    await page.click('[data-test="audit-trail-tab"]');
    await expect(page.locator('[data-test="audit-event"]')).toBeVisible();
  });
});
