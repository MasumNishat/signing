import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm } from '../helpers/common.js';

test.describe('Template Edit', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('user can edit template', async ({ page }) => {
    await page.goto('/templates/template-id/edit');
    await fillForm(page, { name: 'Updated Template' });
    await page.click('[data-test="save"]');
  });

  test('user can update template documents', async ({ page }) => {
    await page.goto('/templates/template-id/edit');
    await page.click('[data-test="documents-tab"]');
    await page.click('[data-test="remove-document"]');
  });

  test('user can update template recipients', async ({ page }) => {
    await page.goto('/templates/template-id/edit');
    await page.click('[data-test="recipients-tab"]');
    await expect(page.locator('[data-test="recipient-list"]')).toBeVisible();
  });
});
