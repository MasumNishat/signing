import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm } from '../helpers/common.js';

test.describe('Recipients', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/recipients');
  });

  test('user can view recipients list', async ({ page }) => {
    await expect(page.locator('[data-test="recipient-item"]')).toBeVisible();
  });

  test('user can add new recipient', async ({ page }) => {
    await page.click('[data-test="add-recipient"]');
    await fillForm(page, { name: 'Jane Doe', email: 'jane@example.com' });
    await page.click('[data-test="save"]');
  });

  test('user can edit recipient', async ({ page }) => {
    await page.click('[data-test="edit-recipient"]:first-child');
    await fillForm(page, { name: 'Updated Name' });
    await page.click('[data-test="save"]');
  });

  test('user can delete recipient', async ({ page }) => {
    await page.click('[data-test="delete-recipient"]:first-child');
    await page.click('[data-test="confirm-delete"]');
  });
});
