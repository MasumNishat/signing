import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { searchTable } from '../helpers/common.js';

test.describe('User List', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, 'admin@example.com', 'password');
    await page.goto('/users');
  });

  test('admin can list all users', async ({ page }) => {
    await expect(page.locator('[data-test="user-item"]')).toHaveCount(10, { timeout: 5000 });
  });

  test('admin can search users', async ({ page }) => {
    await searchTable(page, 'John');
    await expect(page.locator('[data-test="user-item"]')).toContainText('John');
  });

  test('admin can filter users by status', async ({ page }) => {
    await page.selectOption('[data-test="status-filter"]', 'active');
    await expect(page.locator('[data-test="user-item"]')).toBeVisible();
  });
});
