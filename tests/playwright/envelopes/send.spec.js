/**
 * Envelopes - Send Tests
 */

import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { waitForToast } from '../helpers/common.js';

test.describe('Envelope Send', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('user can send envelope', async ({ page }) => {
    await page.goto('/envelopes/draft-id');
    await page.click('[data-test="send-envelope"]');
    await page.click('[data-test="confirm-send"]');
    await waitForToast(page, 'success');
    await expect(page.locator('[data-test="envelope-status"]')).toContainText('sent');
  });

  test('envelope status changes to sent', async ({ page }) => {
    await page.goto('/envelopes/draft-id');
    await page.click('[data-test="send-envelope"]');
    await page.click('[data-test="confirm-send"]');
    await expect(page.locator('[data-test="envelope-status"]')).toContainText('sent');
  });

  test('user cannot send envelope without required fields', async ({ page }) => {
    await page.goto('/envelopes/incomplete-id');
    await page.click('[data-test="send-envelope"]');
    await expect(page.locator('[data-test="error-message"]')).toContainText(/required/i);
  });

  test('recipients receive notification after send', async ({ page }) => {
    await page.goto('/envelopes/draft-id');
    await page.click('[data-test="send-envelope"]');
    await page.click('[data-test="confirm-send"]');
    await waitForToast(page, 'success');
  });
});
