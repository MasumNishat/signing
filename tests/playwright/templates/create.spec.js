import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm } from '../helpers/common.js';

test.describe('Template Create', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/templates/create');
  });

  test('user can create new template', async ({ page }) => {
    await fillForm(page, { name: 'Test Template', description: 'Template description' });
    await page.click('[data-test="save-template"]');
    await expect(page).toHaveURL(/\/templates\/[a-z0-9-]+/);
  });

  test('user can add documents to template', async ({ page }) => {
    await page.click('[data-test="add-document"]');
    await expect(page.locator('[data-test="document-item"]')).toBeVisible();
  });

  test('user can add recipients to template', async ({ page }) => {
    await page.click('[data-test="add-recipient"]');
    await fillForm(page, { recipient_role: 'Signer 1', recipient_type: 'signer' });
    await page.click('[data-test="save-recipient"]');
  });
});
