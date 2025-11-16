import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Template Use', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('user can create envelope from template', async ({ page }) => {
    await page.goto('/templates/template-id/use');
    await page.click('[data-test="use-template"]');
    await expect(page).toHaveURL(/\/envelopes\/create/);
  });

  test('envelope inherits template documents', async ({ page }) => {
    await page.goto('/templates/template-id/use');
    await page.click('[data-test="use-template"]');
    await expect(page.locator('[data-test="document-item"]')).toBeVisible();
  });

  test('envelope inherits template recipients', async ({ page }) => {
    await page.goto('/templates/template-id/use');
    await page.click('[data-test="use-template"]');
    await expect(page.locator('[data-test="recipient-item"]')).toBeVisible();
  });
});
