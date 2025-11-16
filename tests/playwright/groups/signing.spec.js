import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Signing Groups', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/groups/signing');
  });

  test('user can manage signing groups', async ({ page }) => {
    await expect(page.locator('[data-test="signing-group-item"]')).toBeVisible();
  });

  test('user can delete signing group', async ({ page }) => {
    await page.click('[data-test="delete-group"]:first-child');
    await page.click('[data-test="confirm-delete"]');
  });
});
