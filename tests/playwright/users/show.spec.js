import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('User Show', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, 'admin@example.com', 'password');
  });

  test('admin can view user details', async ({ page }) => {
    await page.goto('/users/user-id');
    await expect(page.locator('[data-test="user-name"]')).toBeVisible();
  });

  test('admin can view user permissions', async ({ page }) => {
    await page.goto('/users/user-id');
    await page.click('[data-test="permissions-tab"]');
    await expect(page.locator('[data-test="permission-list"]')).toBeVisible();
  });

  test('admin can view user activity', async ({ page }) => {
    await page.goto('/users/user-id');
    await page.click('[data-test="activity-tab"]');
    await expect(page.locator('[data-test="activity-item"]')).toBeVisible();
  });
});
