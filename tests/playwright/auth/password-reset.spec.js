/**
 * Authentication - Password Reset Tests
 */

import { test, expect } from '@playwright/test';
import { randomEmail } from '../helpers/common.js';

test.describe('Password Reset', () => {
  test('user can request password reset', async ({ page }) => {
    await page.goto('/forgot-password');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.click('button[type="submit"]');
    await expect(page.locator('[data-test="success-message"]')).toBeVisible();
  });

  test('user sees error for non-existent email', async ({ page }) => {
    await page.goto('/forgot-password');
    await page.fill('input[name="email"]', randomEmail());
    await page.click('button[type="submit"]');
    await expect(page.locator('[data-test="error-message"]')).toContainText(/not found/i);
  });

  test('user can reset password with valid token', async ({ page }) => {
    const token = 'valid-reset-token';
    const email = 'admin@example.com';
    await page.goto(`/reset-password?token=${token}&email=${email}`);
    await page.fill('input[name="password"]', 'NewPassword123!');
    await page.fill('input[name="password_confirmation"]', 'NewPassword123!');
    await page.click('button[type="submit"]');
    await page.waitForURL('/login');
    await expect(page.locator('[data-test="success-message"]')).toBeVisible();
  });

  test('user cannot reset password with invalid token', async ({ page }) => {
    await page.goto('/reset-password?token=invalid&email=admin@example.com');
    await page.fill('input[name="password"]', 'NewPassword123!');
    await page.fill('input[name="password_confirmation"]', 'NewPassword123!');
    await page.click('button[type="submit"]');
    await expect(page.locator('[data-test="error-message"]')).toContainText(/invalid.*token/i);
  });
});
