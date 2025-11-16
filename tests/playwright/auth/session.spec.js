/**
 * Authentication - Session Tests
 */

import { test, expect } from '@playwright/test';
import { login, logout } from '../helpers/auth.js';

test.describe('Session Management', () => {
  test('user session persists across page refreshes', async ({ page }) => {
    await login(page);
    await page.reload();
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.locator('[data-test="user-name"]')).toBeVisible();
  });

  test('user can manually logout', async ({ page }) => {
    await login(page);
    await logout(page);
    await expect(page).toHaveURL(/\/login/);
  });

  test('unauthenticated user is redirected to login', async ({ page }) => {
    await page.goto('/envelopes');
    await page.waitForURL(/\/login/);
  });
});
