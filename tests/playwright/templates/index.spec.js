import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { searchTable } from '../helpers/common.js';

test.describe('Template List', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/templates');
  });

  test('user can list all templates', async ({ page }) => {
    await expect(page.locator('[data-test="template-item"]')).toHaveCount(10, { timeout: 5000 });
  });

  test('user can search templates', async ({ page }) => {
    await searchTable(page, 'Contract');
    await expect(page.locator('[data-test="template-item"]')).toContainText('Contract');
  });

  test('user can filter templates', async ({ page }) => {
    await page.selectOption('[data-test="filter-type"]', 'my-templates');
    await expect(page.locator('[data-test="template-item"]')).toBeVisible();
  });
});
