/**
 * Authentication Helper Functions
 *
 * Shared authentication utilities for Playwright tests
 */

/**
 * Login user with credentials
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} email
 * @param {string} password
 */
export async function login(page, email = 'admin@example.com', password = 'password') {
  await page.goto('/login');

  // Fill login form
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);

  // Submit form
  await page.click('button[type="submit"]');

  // Wait for redirect to dashboard
  await page.waitForURL('/dashboard', { timeout: 5000 });

  // Verify successful login
  await page.waitForSelector('[data-test="dashboard"]', { timeout: 5000 });
}

/**
 * Logout current user
 *
 * @param {import('@playwright/test').Page} page
 */
export async function logout(page) {
  // Click logout button
  await page.click('[data-test="logout-button"]');

  // Wait for redirect to login
  await page.waitForURL('/login', { timeout: 5000 });

  // Verify logout
  await page.waitForSelector('input[name="email"]', { timeout: 5000 });
}

/**
 * Register new user
 *
 * @param {import('@playwright/test').Page} page
 * @param {object} userData
 */
export async function register(page, userData = {}) {
  const defaultData = {
    name: 'Test User',
    email: `test${Date.now()}@example.com`,
    password: 'Password123!',
    password_confirmation: 'Password123!',
  };

  const data = { ...defaultData, ...userData };

  await page.goto('/register');

  // Fill registration form
  await page.fill('input[name="name"]', data.name);
  await page.fill('input[name="email"]', data.email);
  await page.fill('input[name="password"]', data.password);
  await page.fill('input[name="password_confirmation"]', data.password_confirmation);

  // Submit form
  await page.click('button[type="submit"]');

  // Wait for redirect to login
  await page.waitForURL('/login', { timeout: 5000 });

  return data;
}

/**
 * Request password reset
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} email
 */
export async function requestPasswordReset(page, email = 'admin@example.com') {
  await page.goto('/forgot-password');

  // Fill email
  await page.fill('input[name="email"]', email);

  // Submit form
  await page.click('button[type="submit"]');

  // Wait for success message
  await page.waitForSelector('[data-test="success-message"]', { timeout: 5000 });
}

/**
 * Reset password with token
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} token
 * @param {string} email
 * @param {string} password
 */
export async function resetPassword(page, token, email, password = 'NewPassword123!') {
  await page.goto(`/reset-password?token=${token}&email=${email}`);

  // Fill password reset form
  await page.fill('input[name="password"]', password);
  await page.fill('input[name="password_confirmation"]', password);

  // Submit form
  await page.click('button[type="submit"]');

  // Wait for redirect to login
  await page.waitForURL('/login', { timeout: 5000 });
}

/**
 * Get authentication token from localStorage
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string|null>}
 */
export async function getAuthToken(page) {
  return await page.evaluate(() => localStorage.getItem('auth_token'));
}

/**
 * Set authentication token in localStorage
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} token
 */
export async function setAuthToken(page, token) {
  await page.evaluate((token) => {
    localStorage.setItem('auth_token', token);
  }, token);
}

/**
 * Clear authentication
 *
 * @param {import('@playwright/test').Page} page
 */
export async function clearAuth(page) {
  await page.evaluate(() => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
  });
}

/**
 * Check if user is authenticated
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<boolean>}
 */
export async function isAuthenticated(page) {
  const token = await getAuthToken(page);
  return token !== null && token !== '';
}
