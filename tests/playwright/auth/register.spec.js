/**
 * Authentication - Register Tests
 *
 * Tests for user registration functionality
 */

import { test, expect } from '@playwright/test';
import { register, clearAuth } from '../helpers/auth.js';
import { randomEmail } from '../helpers/common.js';

test.describe('Register', () => {
  test.beforeEach(async ({ page }) => {
    await clearAuth(page);
  });

  test('user can register with valid data', async ({ page }) => {
    const userData = {
      name: 'Test User',
      email: randomEmail(),
      password: 'Password123!',
      password_confirmation: 'Password123!',
    };

    await page.goto('/register');

    // Fill registration form
    await page.fill('input[name="name"]', userData.name);
    await page.fill('input[name="email"]', userData.email);
    await page.fill('input[name="password"]', userData.password);
    await page.fill('input[name="password_confirmation"]', userData.password_confirmation);

    // Submit form
    await page.click('button[type="submit"]');

    // Wait for redirect to login
    await page.waitForURL('/login', { timeout: 5000 });

    // Verify success message
    await expect(page.locator('[data-test="success-message"]')).toBeVisible();
    await expect(page.locator('[data-test="success-message"]')).toContainText(/registered successfully/i);
  });

  test('user cannot register with existing email', async ({ page }) => {
    await page.goto('/register');

    // Fill form with existing email
    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'Password123!');
    await page.fill('input[name="password_confirmation"]', 'Password123!');

    // Submit form
    await page.click('button[type="submit"]');

    // Should show error
    await expect(page.locator('[data-test="validation-error-email"]')).toBeVisible();
    await expect(page.locator('[data-test="validation-error-email"]')).toContainText(/already.*taken/i);
  });

  test('user sees validation errors for invalid data', async ({ page }) => {
    await page.goto('/register');

    // Submit empty form
    await page.click('button[type="submit"]');

    // Should show validation errors
    await expect(page.locator('[data-test="validation-error-name"]')).toBeVisible();
    await expect(page.locator('[data-test="validation-error-email"]')).toBeVisible();
    await expect(page.locator('[data-test="validation-error-password"]')).toBeVisible();
  });

  test('user cannot register with mismatched passwords', async ({ page }) => {
    await page.goto('/register');

    // Fill form with mismatched passwords
    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', randomEmail());
    await page.fill('input[name="password"]', 'Password123!');
    await page.fill('input[name="password_confirmation"]', 'DifferentPassword123!');

    // Submit form
    await page.click('button[type="submit"]');

    // Should show password confirmation error
    await expect(page.locator('[data-test="validation-error-password_confirmation"]')).toBeVisible();
    await expect(page.locator('[data-test="validation-error-password_confirmation"]')).toContainText(/do not match/i);
  });

  test('user sees password strength meter', async ({ page }) => {
    await page.goto('/register');

    // Password strength meter should exist
    await expect(page.locator('[data-test="password-strength-meter"]')).toBeVisible();

    // Type weak password
    await page.fill('input[name="password"]', 'weak');
    await expect(page.locator('[data-test="password-strength-meter"]')).toHaveClass(/weak/);

    // Type medium password
    await page.fill('input[name="password"]', 'Password123');
    await expect(page.locator('[data-test="password-strength-meter"]')).toHaveClass(/medium/);

    // Type strong password
    await page.fill('input[name="password"]', 'Password123!@#');
    await expect(page.locator('[data-test="password-strength-meter"]')).toHaveClass(/strong/);
  });

  test('user must accept terms and conditions', async ({ page }) => {
    await page.goto('/register');

    const userData = {
      name: 'Test User',
      email: randomEmail(),
      password: 'Password123!',
      password_confirmation: 'Password123!',
    };

    // Fill form
    await page.fill('input[name="name"]', userData.name);
    await page.fill('input[name="email"]', userData.email);
    await page.fill('input[name="password"]', userData.password);
    await page.fill('input[name="password_confirmation"]', userData.password_confirmation);

    // Submit without accepting terms
    await page.click('button[type="submit"]');

    // Should show error
    await expect(page.locator('[data-test="validation-error-terms"]')).toBeVisible();

    // Accept terms
    await page.check('input[name="terms"]');

    // Submit again
    await page.click('button[type="submit"]');

    // Should succeed
    await page.waitForURL('/login', { timeout: 5000 });
  });

  test('registration form is accessible via keyboard', async ({ page }) => {
    await page.goto('/register');

    // Navigate form with keyboard
    await page.keyboard.press('Tab'); // Name
    await expect(page.locator('input[name="name"]')).toBeFocused();

    await page.keyboard.press('Tab'); // Email
    await expect(page.locator('input[name="email"]')).toBeFocused();

    await page.keyboard.press('Tab'); // Password
    await expect(page.locator('input[name="password"]')).toBeFocused();

    await page.keyboard.press('Tab'); // Password confirmation
    await expect(page.locator('input[name="password_confirmation"]')).toBeFocused();

    await page.keyboard.press('Tab'); // Terms checkbox
    await expect(page.locator('input[name="terms"]')).toBeFocused();
  });

  test('user can navigate to login from register page', async ({ page }) => {
    await page.goto('/register');

    // Find login link
    const loginLink = page.locator('a[href="/login"]');
    await expect(loginLink).toBeVisible();
    await expect(loginLink).toContainText(/Already.*account|Login/i);

    // Click and navigate
    await loginLink.click();
    await page.waitForURL('/login', { timeout: 5000 });
  });
});
