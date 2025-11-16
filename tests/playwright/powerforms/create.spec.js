import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm } from '../helpers/common.js';

test.describe('PowerForm Create', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/powerforms/create');
  });

  test('user can create powerform', async ({ page }) => {
    await fillForm(page, { name: 'Test PowerForm', template_id: 'template-id' });
    await page.click('[data-test="save-powerform"]');
  });

  test('powerform generates public URL', async ({ page }) => {
    await page.goto('/powerforms/powerform-id');
    await expect(page.locator('[data-test="public-url"]')).toBeVisible();
  });

  test('recipients can submit powerform', async ({ page }) => {
    await page.goto('/powerforms/public/powerform-id');
    await fillForm(page, { name: 'John Doe', email: 'john@example.com' });
    await page.click('[data-test="submit"]');
  });
});
