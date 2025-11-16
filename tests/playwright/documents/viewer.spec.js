import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Document Viewer', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/documents/viewer?id=doc-id');
  });

  test('user can view document', async ({ page }) => {
    await expect(page.locator('[data-test="pdf-viewer"]')).toBeVisible();
  });

  test('user can zoom document', async ({ page }) => {
    await page.click('[data-test="zoom-in"]');
    await page.click('[data-test="zoom-out"]');
  });

  test('user can rotate document', async ({ page }) => {
    await page.click('[data-test="rotate"]');
  });

  test('user can navigate document pages', async ({ page }) => {
    await page.click('[data-test="next-page"]');
    await page.click('[data-test="previous-page"]');
  });
});
