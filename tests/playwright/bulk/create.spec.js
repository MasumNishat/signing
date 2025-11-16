import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { uploadFile } from '../helpers/common.js';

test.describe('Bulk Send Create', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/bulk/create');
  });

  test('user can create bulk send batch', async ({ page }) => {
    await page.fill('[data-test="batch-name"]', 'Test Batch');
    await page.click('[data-test="next-step"]');
  });

  test('user can upload CSV for bulk send', async ({ page }) => {
    await uploadFile(page, '[data-test="csv-upload"]', './test-files/recipients.csv');
    await expect(page.locator('[data-test="recipient-count"]')).toContainText('10');
  });

  test('bulk send processes all envelopes', async ({ page }) => {
    await page.fill('[data-test="batch-name"]', 'Test Batch');
    await page.click('[data-test="next-step"]');
    await uploadFile(page, '[data-test="csv-upload"]', './test-files/recipients.csv');
    await page.click('[data-test="send-batch"]');
    await expect(page.locator('[data-test="processing-status"]')).toBeVisible();
  });
});
