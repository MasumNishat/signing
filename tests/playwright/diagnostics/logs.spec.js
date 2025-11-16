import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { waitForAPI, filterTable, searchTable, exportCSV } from '../helpers/common.js';

test.describe('Diagnostics Logs', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/diagnostics/logs');
  });

  test('user can view request logs', async ({ page }) => {
    await waitForAPI(page, '/diagnostics/logs');
    await expect(page.locator('[data-test="log-row"]')).toHaveCount(20);
    
    // Verify log columns
    await expect(page.locator('[data-test="log-timestamp"]').first()).toBeVisible();
    await expect(page.locator('[data-test="log-method"]').first()).toBeVisible();
    await expect(page.locator('[data-test="log-endpoint"]').first()).toBeVisible();
    await expect(page.locator('[data-test="log-status"]').first()).toBeVisible();
    await expect(page.locator('[data-test="log-duration"]').first()).toBeVisible();
  });

  test('user can filter logs by status code', async ({ page }) => {
    await filterTable(page, 'status', '200');
    await waitForAPI(page, '/diagnostics/logs?status=200');
    
    const statusCodes = await page.locator('[data-test="log-status"]').allTextContents();
    statusCodes.forEach(status => {
      expect(status).toContain('200');
    });
  });

  test('user can view detailed log information', async ({ page }) => {
    // Click first log row
    await page.click('[data-test="log-row"]:first-child');
    
    // Expand details
    await expect(page.locator('[data-test="log-details"]')).toBeVisible();
    await expect(page.locator('[data-test="request-headers"]')).toBeVisible();
    await expect(page.locator('[data-test="request-body"]')).toBeVisible();
    await expect(page.locator('[data-test="response-body"]')).toBeVisible();
    await expect(page.locator('[data-test="response-time"]')).toBeVisible();
  });
});
