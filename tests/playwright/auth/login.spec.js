/**
 * Authentication - Login Tests
 *
 * Tests for user login functionality
 */

import { test, expect } from '@playwright/test';
import { login, logout, clearAuth } from '../helpers/auth.js';

test.describe('Login', () => {
  test.beforeEach(async ({ page }) => {
    // Clear any existing authentication
    await clearAuth(page);
  });

  test('user can login with valid credentials', async ({ page }) => {
    await page.goto('/login');

    // Fill login form
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');

    // Submit form
    await page.click('button[type="submit"]');

    // Wait for redirect to dashboard
    await page.waitForURL('/dashboard', { timeout: 5000 });

    // Verify dashboard loaded
    await expect(page).toHaveTitle(/Dashboard/);
    await expect(page.locator('[data-test="dashboard"]')).toBeVisible();
  });

  test('user cannot login with invalid credentials', async ({ page }) => {
    await page.goto('/login');

    // Fill login form with invalid credentials
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'wrongpassword');

    // Submit form
    await page.click('button[type="submit"]');

    // Should stay on login page
    await expect(page).toHaveURL(/\/login/);

    // Should show error message
    await expect(page.locator('[data-test="error-message"]')).toBeVisible();
    await expect(page.locator('[data-test="error-message"]')).toContainText(/Invalid credentials/i);
  });

  test('user sees error message for invalid email format', async ({ page }) => {
    await page.goto('/login');

    // Fill login form with invalid email
    await page.fill('input[name="email"]', 'invalid-email');
    await page.fill('input[name="password"]', 'password');

    // Submit form
    await page.click('button[type="submit"]');

    // Should show validation error
    await expect(page.locator('[data-test="validation-error-email"]')).toBeVisible();
    await expect(page.locator('[data-test="validation-error-email"]')).toContainText(/valid email/i);
  });

  test('user is redirected to dashboard after successful login', async ({ page }) => {
    await login(page, 'admin@example.com', 'password');

    // Verify redirect to dashboard
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.locator('[data-test="dashboard"]')).toBeVisible();

    // Verify user info displayed
    await expect(page.locator('[data-test="user-name"]')).toBeVisible();
    await expect(page.locator('[data-test="user-email"]')).toContainText('admin@example.com');
  });

  test('remember me checkbox persists login session', async ({ page }) => {
    await page.goto('/login');

    // Fill login form
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');

    // Check remember me
    await page.check('input[name="remember"]');

    // Submit form
    await page.click('button[type="submit"]');

    // Wait for redirect
    await page.waitForURL('/dashboard', { timeout: 5000 });

    // Verify remember me cookie set
    const cookies = await page.context().cookies();
    const rememberCookie = cookies.find(c => c.name === 'remember_token');
    expect(rememberCookie).toBeDefined();
  });

  test('user can see password by toggling visibility', async ({ page }) => {
    await page.goto('/login');

    // Fill password
    await page.fill('input[name="password"]', 'password');

    // Verify password is hidden
    await expect(page.locator('input[name="password"]')).toHaveAttribute('type', 'password');

    // Click toggle visibility button
    await page.click('[data-test="toggle-password-visibility"]');

    // Verify password is visible
    await expect(page.locator('input[name="password"]')).toHaveAttribute('type', 'text');

    // Click again to hide
    await page.click('[data-test="toggle-password-visibility"]');

    // Verify password is hidden again
    await expect(page.locator('input[name="password"]')).toHaveAttribute('type', 'password');
  });

  test('login form is accessible via keyboard navigation', async ({ page }) => {
    await page.goto('/login');

    // Tab to email input
    await page.keyboard.press('Tab');
    await expect(page.locator('input[name="email"]')).toBeFocused();

    // Fill email
    await page.keyboard.type('admin@example.com');

    // Tab to password input
    await page.keyboard.press('Tab');
    await expect(page.locator('input[name="password"]')).toBeFocused();

    // Fill password
    await page.keyboard.type('password');

    // Tab to remember checkbox
    await page.keyboard.press('Tab');
    await expect(page.locator('input[name="remember"]')).toBeFocused();

    // Tab to submit button
    await page.keyboard.press('Tab');
    await expect(page.locator('button[type="submit"]')).toBeFocused();

    // Submit with Enter
    await page.keyboard.press('Enter');

    // Verify redirect
    await page.waitForURL('/dashboard', { timeout: 5000 });
  });

  test('login page has forgot password link', async ({ page }) => {
    await page.goto('/login');

    // Verify forgot password link exists
    const forgotLink = page.locator('a[href="/forgot-password"]');
    await expect(forgotLink).toBeVisible();
    await expect(forgotLink).toContainText(/Forgot.*password/i);

    // Click link and verify navigation
    await forgotLink.click();
    await page.waitForURL('/forgot-password', { timeout: 5000 });
  });

  test('login page has register link', async ({ page }) => {
    await page.goto('/login');

    // Verify register link exists
    const registerLink = page.locator('a[href="/register"]');
    await expect(registerLink).toBeVisible();
    await expect(registerLink).toContainText(/Register|Sign up/i);

    // Click link and verify navigation
    await registerLink.click();
    await page.waitForURL('/register', { timeout: 5000 });
  });

  test('user is redirected to intended page after login', async ({ page }) => {
    // Try to access protected page
    await page.goto('/envelopes/create');

    // Should redirect to login with return URL
    await page.waitForURL(/\/login/, { timeout: 5000 });

    // Login
    await login(page, 'admin@example.com', 'password');

    // Should redirect back to intended page
    await page.waitForURL('/envelopes/create', { timeout: 5000 });
  });
});
