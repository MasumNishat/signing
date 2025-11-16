import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Billing', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/billing');
  });

  test('user can view billing dashboard', async ({ page }) => {
    await expect(page.locator('[data-test="billing-summary"]')).toBeVisible();
  });

  test('user can view invoices', async ({ page }) => {
    await page.click('[data-test="invoices-tab"]');
    await expect(page.locator('[data-test="invoice-item"]')).toBeVisible();
  });

  test('user can download invoice PDF', async ({ page }) => {
    await page.click('[data-test="download-invoice"]:first-child');
  });

  test('user can view payment history', async ({ page }) => {
    await page.click('[data-test="payments-tab"]');
    await expect(page.locator('[data-test="payment-item"]')).toBeVisible();
  });
});
