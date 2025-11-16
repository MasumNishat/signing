import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { waitForAPI } from '../helpers/common.js';

test.describe('Diagnostics Health', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/diagnostics/health');
  });

  test('user can view system health status', async ({ page }) => {
    await waitForAPI(page, '/diagnostics/health');
    
    // Verify health indicators
    await expect(page.locator('[data-test="health-database"]')).toBeVisible();
    await expect(page.locator('[data-test="health-cache"]')).toBeVisible();
    await expect(page.locator('[data-test="health-storage"]')).toBeVisible();
    await expect(page.locator('[data-test="health-queue"]')).toBeVisible();
    
    // Check status badges
    const dbStatus = await page.locator('[data-test="health-database"] [data-test="status-badge"]').textContent();
    expect(['Healthy', 'Warning', 'Error']).toContain(dbStatus);
  });

  test('health page auto-refreshes every 30 seconds', async ({ page }) => {
    await waitForAPI(page, '/diagnostics/health');
    
    // Wait for auto-refresh
    await page.waitForTimeout(31000);
    
    // Verify API was called again
    await waitForAPI(page, '/diagnostics/health');
    await expect(page.locator('[data-test="last-updated"]')).toBeVisible();
  });

  test('user can manually refresh health status', async ({ page }) => {
    await waitForAPI(page, '/diagnostics/health');
    
    // Click refresh button
    await page.click('[data-test="refresh-health"]');
    await waitForAPI(page, '/diagnostics/health');
    
    await expect(page.locator('[data-test="success-message"]')).toBeVisible();
  });
});
