/**
 * Dashboard - Widgets Tests
 */

import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Dashboard Widgets', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/dashboard/widgets');
  });

  test('user can customize dashboard widgets', async ({ page }) => {
    await page.click('[data-test="customize-widgets"]');
    await expect(page.locator('[data-test="widget-selector"]')).toBeVisible();
  });

  test('user can rearrange widgets', async ({ page }) => {
    const widget = page.locator('[data-test="widget-envelope-stats"]');
    await widget.dragTo(page.locator('[data-test="widget-drop-zone"]'));
    await expect(widget).toBeVisible();
  });

  test('user can remove widgets', async ({ page }) => {
    await page.click('[data-test="remove-widget-billing"]');
    await expect(page.locator('[data-test="widget-billing"]')).not.toBeVisible();
  });

  test('widget settings persist', async ({ page }) => {
    await page.click('[data-test="customize-widgets"]');
    await page.check('[data-test="widget-toggle-activity"]');
    await page.reload();
    await expect(page.locator('[data-test="widget-activity"]')).toBeVisible();
  });
});
