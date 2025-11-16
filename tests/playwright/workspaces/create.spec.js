import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm, uploadFile } from '../helpers/common.js';

test.describe('Workspace Create', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/workspaces/create');
  });

  test('user can create workspace', async ({ page }) => {
    await fillForm(page, { name: 'Project Workspace' });
    await page.click('[data-test="save-workspace"]');
  });

  test('user can upload files to workspace', async ({ page }) => {
    await page.goto('/workspaces/workspace-id');
    await uploadFile(page, '[data-test="file-upload"]', './test-files/document.pdf');
  });

  test('user can share workspace', async ({ page }) => {
    await page.goto('/workspaces/workspace-id');
    await page.click('[data-test="share-workspace"]');
    await fillForm(page, { email: 'colleague@example.com' });
    await page.click('[data-test="send-share"]');
  });
});
