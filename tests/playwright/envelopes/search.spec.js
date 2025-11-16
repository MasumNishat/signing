import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { searchTable, waitForAPI } from '../helpers/common.js';

test.describe('Envelope Search', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/envelopes');
  });

  test('user can search envelopes by subject', async ({ page }) => {
    await searchTable(page, 'Test Envelope');
    await waitForAPI(page, '/envelopes');
    await expect(page.locator('[data-test="envelope-item"]')).toContainText('Test Envelope');
  });

  test('user can filter envelopes by status', async ({ page }) => {
    await page.selectOption('[data-test="status-filter"]', 'sent');
    await waitForAPI(page, '/envelopes');
  });

  test('user can filter envelopes by date range', async ({ page }) => {
    await page.fill('[data-test="date-from"]', '2025-01-01');
    await page.fill('[data-test="date-to"]', '2025-12-31');
    await page.click('[data-test="apply-filter"]');
  });

  test('advanced search returns correct results', async ({ page }) => {
    await page.goto('/envelopes/advanced-search');
    await page.fill('[data-test="search-subject"]', 'Contract');
    await page.selectOption('[data-test="search-status"]', 'completed');
    await page.click('[data-test="search-submit"]');
    await waitForAPI(page, '/envelopes');
  });
});
