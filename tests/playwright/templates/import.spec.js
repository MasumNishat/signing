import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { uploadFile } from '../helpers/common.js';

test.describe('Template Import', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/templates/import');
  });

  test('user can import template from JSON', async ({ page }) => {
    await uploadFile(page, '[data-test="import-file"]', './test-files/template.json');
    await page.click('[data-test="import-submit"]');
  });

  test('user can import template from XML', async ({ page }) => {
    await uploadFile(page, '[data-test="import-file"]', './test-files/template.xml');
    await page.click('[data-test="import-submit"]');
  });

  test('imported template is valid', async ({ page }) => {
    await uploadFile(page, '[data-test="import-file"]', './test-files/template.json');
    await page.click('[data-test="import-submit"]');
    await expect(page).toHaveURL(/\/templates\/[a-z0-9-]+/);
  });
});
