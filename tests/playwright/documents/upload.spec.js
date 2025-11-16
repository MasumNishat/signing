import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { uploadFile } from '../helpers/common.js';

test.describe('Document Upload', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/documents/upload');
  });

  test('user can upload single document', async ({ page }) => {
    await uploadFile(page, '[data-test="file-input"]', './test-files/document.pdf');
    await expect(page.locator('[data-test="upload-progress"]')).toBeVisible();
  });

  test('user can upload multiple documents', async ({ page }) => {
    await page.setInputFiles('[data-test="file-input"]', [
      './test-files/doc1.pdf',
      './test-files/doc2.pdf'
    ]);
    await expect(page.locator('[data-test="document-item"]')).toHaveCount(2);
  });

  test('user can drag-drop upload documents', async ({ page }) => {
    await expect(page.locator('[data-test="drop-zone"]')).toBeVisible();
  });

  test('upload progress is displayed', async ({ page }) => {
    await uploadFile(page, '[data-test="file-input"]', './test-files/large.pdf');
    await expect(page.locator('[data-test="progress-bar"]')).toBeVisible();
  });
});
