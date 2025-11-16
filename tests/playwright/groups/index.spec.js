import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm } from '../helpers/common.js';

test.describe('Groups', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/groups');
  });

  test('user can create signing group', async ({ page }) => {
    await page.click('[data-test="create-group"]');
    await fillForm(page, { name: 'Sales Team' });
    await page.click('[data-test="save"]');
  });

  test('user can add members to group', async ({ page }) => {
    await page.click('[data-test="group-item"]:first-child');
    await page.click('[data-test="add-member"]');
    await fillForm(page, { email: 'member@example.com' });
    await page.click('[data-test="save-member"]');
  });

  test('user can use group in envelope', async ({ page }) => {
    await page.goto('/envelopes/create');
    await page.click('[data-test="add-recipient"]');
    await page.selectOption('[data-test="recipient-type"]', 'group');
    await page.selectOption('[data-test="group-select"]', 'group-id');
  });
});
