import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm, waitForAPI, waitForToast } from '../helpers/common.js';

test.describe('Connect/Webhook Create', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/connect/create');
  });

  test('user can create webhook configuration', async ({ page }) => {
    // Step 1: Basic info
    await fillForm(page, {
      name: 'Test Webhook',
      url: 'https://example.com/webhook',
    });
    await page.click('[data-test="next-step"]');

    // Step 2: Event selection
    await page.check('[data-test="event-envelope-sent"]');
    await page.check('[data-test="event-envelope-completed"]');
    await page.check('[data-test="event-envelope-voided"]');
    await page.click('[data-test="next-step"]');

    // Step 3: Settings
    await fillForm(page, {
      retry_enabled: true,
      retry_count: '3',
      retry_delay: '60',
    });

    // Save webhook
    await page.click('[data-test="save-webhook"]');
    await waitForAPI(page, '/connect');
    await waitForToast(page, 'success');

    // Verify redirect and creation
    await expect(page).toHaveURL(/\/connect\/[a-z0-9-]+/);
    await expect(page.locator('[data-test="webhook-name"]')).toHaveText('Test Webhook');
  });

  test('user can test webhook before saving', async ({ page }) => {
    await fillForm(page, {
      name: 'Test Webhook',
      url: 'https://example.com/webhook',
    });
    await page.click('[data-test="next-step"]');

    await page.check('[data-test="event-envelope-sent"]');
    await page.click('[data-test="next-step"]');

    // Test webhook
    await page.click('[data-test="test-webhook"]');
    await waitForAPI(page, '/connect/test');
    await expect(page.locator('[data-test="test-result"]')).toBeVisible();
  });

  test('webhook creation validates URL format', async ({ page }) => {
    await fillForm(page, {
      name: 'Test Webhook',
      url: 'invalid-url',
    });
    await page.click('[data-test="next-step"]');

    await expect(page.locator('[data-test="error-url"]')).toBeVisible();
    await expect(page.locator('[data-test="error-url"]')).toHaveText(/valid URL/i);
  });
});
