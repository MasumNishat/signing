import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm, randomEmail } from '../helpers/common.js';

test.describe('User Create', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, 'admin@example.com', 'password');
    await page.goto('/users/create');
  });

  test('admin can create new user', async ({ page }) => {
    await fillForm(page, {
      name: 'New User',
      email: randomEmail(),
      role: 'user'
    });
    await page.click('[data-test="save-user"]');
  });

  test('admin can assign role to user', async ({ page }) => {
    await page.selectOption('[data-test="role-select"]', 'manager');
    await expect(page.locator('[data-test="role-select"]')).toHaveValue('manager');
  });

  test('admin can set user permissions', async ({ page }) => {
    await page.check('[data-test="permission-envelope-create"]');
    await page.check('[data-test="permission-envelope-send"]');
  });
});
