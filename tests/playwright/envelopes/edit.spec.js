/**
 * Envelopes - Edit Tests
 */

import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm } from '../helpers/common.js';

test.describe('Envelope Edit', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('user can edit draft envelope', async ({ page }) => {
    await page.goto('/envelopes');
    await page.click('[data-test="envelope-draft"]:first-child');
    await page.click('[data-test="edit-envelope"]');
    await fillForm(page, { subject: 'Updated Subject' });
    await page.click('[data-test="save"]');
    await expect(page.locator('[data-test="envelope-subject"]')).toContainText('Updated Subject');
  });

  test('user can update envelope details', async ({ page }) => {
    await page.goto('/envelopes/draft-id/edit');
    await fillForm(page, { message: 'Updated message' });
    await page.click('[data-test="save"]');
  });

  test('user can add/remove documents', async ({ page }) => {
    await page.goto('/envelopes/draft-id/edit');
    await page.click('[data-test="remove-document"]');
    await expect(page.locator('[data-test="document-item"]')).toHaveCount(0);
  });

  test('user can add/remove recipients', async ({ page }) => {
    await page.goto('/envelopes/draft-id/edit');
    await page.click('[data-test="remove-recipient"]');
    await page.click('[data-test="confirm-remove"]');
  });

  test('user cannot edit sent envelope', async ({ page }) => {
    await page.goto('/envelopes/sent-id');
    await expect(page.locator('[data-test="edit-envelope"]')).not.toBeVisible();
  });
});
