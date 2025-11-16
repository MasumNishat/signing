import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Document List', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/documents');
  });

  test('user can list all documents', async ({ page }) => {
    await expect(page.locator('[data-test="document-item"]')).toHaveCount(10, { timeout: 5000 });
  });

  test('user can switch between grid and list view', async ({ page }) => {
    await page.click('[data-test="view-grid"]');
    await expect(page.locator('[data-test="grid-view"]')).toBeVisible();
    await page.click('[data-test="view-list"]');
    await expect(page.locator('[data-test="list-view"]')).toBeVisible();
  });
});
