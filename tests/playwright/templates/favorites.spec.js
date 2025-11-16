import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';

test.describe('Template Favorites', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('user can add template to favorites', async ({ page }) => {
    await page.goto('/templates/template-id');
    await page.click('[data-test="add-to-favorites"]');
    await expect(page.locator('[data-test="favorites-icon"]')).toHaveClass(/active/);
  });

  test('user can remove template from favorites', async ({ page }) => {
    await page.goto('/templates/favorite-id');
    await page.click('[data-test="remove-from-favorites"]');
  });

  test('favorites appear in quick access', async ({ page }) => {
    await page.goto('/templates/favorites');
    await expect(page.locator('[data-test="favorite-template"]')).toBeVisible();
  });
});
