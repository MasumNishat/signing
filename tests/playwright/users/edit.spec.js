import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm } from '../helpers/common.js';

test.describe('User Edit', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, 'admin@example.com', 'password');
  });

  test('admin can edit user details', async ({ page }) => {
    await page.goto('/users/user-id/edit');
    await fillForm(page, { name: 'Updated Name' });
    await page.click('[data-test="save"]');
  });

  test('admin can change user role', async ({ page }) => {
    await page.goto('/users/user-id/edit');
    await page.selectOption('[data-test="role-select"]', 'admin');
    await page.click('[data-test="save"]');
  });

  test('admin can deactivate user', async ({ page }) => {
    await page.goto('/users/user-id/edit');
    await page.selectOption('[data-test="status-select"]', 'inactive');
    await page.click('[data-test="save"]');
  });
});
