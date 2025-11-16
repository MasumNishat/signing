import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { waitForAPI, filterTable, searchTable } from '../helpers/common.js';

test.describe('Connect/Webhook Logs', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/connect/logs');
  });

  test('user can view webhook delivery logs', async ({ page }) => {
    await waitForAPI(page, '/connect/logs');
    await expect(page.locator('[data-test="log-item"]')).toHaveCount(10);
    await expect(page.locator('[data-test="log-timestamp"]').first()).toBeVisible();
    await expect(page.locator('[data-test="log-status"]').first()).toBeVisible();
  });

  test('user can filter logs by status', async ({ page }) => {
    await filterTable(page, 'status', 'success');
    await waitForAPI(page, '/connect/logs?status=success');
    
    const statusBadges = await page.locator('[data-test="log-status"]').allTextContents();
    statusBadges.forEach(status => {
      expect(status.toLowerCase()).toContain('success');
    });
  });

  test('user can view log details and retry failed deliveries', async ({ page }) => {
    // Click first failed log
    await page.click('[data-test="log-item"]:has([data-test="log-status"]:has-text("Failed")):first-child');
    
    // View details modal
    await expect(page.locator('[data-test="log-details-modal"]')).toBeVisible();
    await expect(page.locator('[data-test="log-payload"]')).toBeVisible();
    await expect(page.locator('[data-test="log-response"]')).toBeVisible();
    
    // Retry delivery
    await page.click('[data-test="retry-delivery"]');
    await waitForAPI(page, '/connect/retry');
    await expect(page.locator('[data-test="success-message"]')).toBeVisible();
  });
});
