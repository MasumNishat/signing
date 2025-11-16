import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm } from '../helpers/common.js';

test.describe('Folders', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/folders');
  });

  test('user can create folder', async ({ page }) => {
    await page.click('[data-test="create-folder"]');
    await fillForm(page, { name: 'Contracts' });
    await page.click('[data-test="save"]');
  });

  test('user can move envelope to folder', async ({ page }) => {
    await page.goto('/envelopes/envelope-id');
    await page.click('[data-test="move-to-folder"]');
    await page.selectOption('[data-test="folder-select"]', 'folder-id');
    await page.click('[data-test="confirm-move"]');
  });

  test('user can view folder contents', async ({ page }) => {
    await page.click('[data-test="folder-item"]:first-child');
    await expect(page.locator('[data-test="envelope-item"]')).toBeVisible();
  });
});
