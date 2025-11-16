import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm, uploadFile } from '../helpers/common.js';

test.describe('User Profile', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/users/profile');
  });

  test('user can view own profile', async ({ page }) => {
    await expect(page.locator('[data-test="profile-name"]')).toBeVisible();
  });

  test('user can update profile information', async ({ page }) => {
    await fillForm(page, { name: 'Updated Name', phone: '555-1234' });
    await page.click('[data-test="save-profile"]');
  });

  test('user can upload profile picture', async ({ page }) => {
    await uploadFile(page, '[data-test="profile-image"]', './test-files/avatar.jpg');
    await expect(page.locator('[data-test="profile-image-preview"]')).toBeVisible();
  });

  test('user can change password', async ({ page }) => {
    await page.click('[data-test="change-password-tab"]');
    await fillForm(page, {
      current_password: 'password',
      password: 'NewPassword123!',
      password_confirmation: 'NewPassword123!'
    });
    await page.click('[data-test="save-password"]');
  });
});
