import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Bulk Send List', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/bulk');
  });

  test('user can view bulk send batches', async ({ page }) => {
    await expect(page.locator('[data-test="batch-item"]')).toBeVisible();
  });

  test('user can view batch progress', async ({ page }) => {
    await page.click('[data-test="batch-item"]:first-child');
    await expect(page.locator('[data-test="progress-bar"]')).toBeVisible();
  });

  test('user can view batch details', async ({ page }) => {
    await page.click('[data-test="batch-item"]:first-child');
    await expect(page.locator('[data-test="batch-details"]')).toBeVisible();
  });
});
