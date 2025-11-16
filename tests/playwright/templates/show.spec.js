import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Template Show', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('user can view template details', async ({ page }) => {
    await page.goto('/templates/template-id');
    await expect(page.locator('[data-test="template-name"]')).toBeVisible();
  });

  test('user can preview template documents', async ({ page }) => {
    await page.goto('/templates/template-id');
    await page.click('[data-test="preview-document"]');
    await expect(page.locator('[data-test="document-viewer"]')).toBeVisible();
  });
});
