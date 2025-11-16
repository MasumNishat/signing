import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Template Share', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('user can share template', async ({ page }) => {
    await page.goto('/templates/template-id/share');
    await page.fill('[data-test="share-email"]', 'colleague@example.com');
    await page.click('[data-test="send-share"]');
  });

  test('shared user can view template', async ({ page }) => {
    await page.goto('/templates/shared-id');
    await expect(page.locator('[data-test="template-name"]')).toBeVisible();
  });

  test('shared user can use template', async ({ page }) => {
    await page.goto('/templates/shared-id');
    await page.click('[data-test="use-template"]');
  });
});
