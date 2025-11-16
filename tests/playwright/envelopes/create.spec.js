/**
 * Envelopes - Create Tests
 */

import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm, waitForToast, uploadFile } from '../helpers/common.js';

test.describe('Envelope Creation', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/envelopes/create');
  });

  test('user can create new envelope', async ({ page }) => {
    await fillForm(page, {
      subject: 'Test Envelope',
      message: 'Please sign this document',
    });
    await page.click('[data-test="save-draft"]');
    await waitForToast(page, 'success');
    await expect(page).toHaveURL(/\/envelopes\/[a-z0-9-]+/);
  });

  test('user can add documents to envelope', async ({ page }) => {
    await fillForm(page, { subject: 'Test Envelope' });
    await page.click('[data-test="add-documents"]');
    await uploadFile(page, 'input[type="file"]', './test-files/document.pdf');
    await expect(page.locator('[data-test="document-item"]')).toBeVisible();
  });

  test('user can add recipients to envelope', async ({ page }) => {
    await fillForm(page, { subject: 'Test Envelope' });
    await page.click('[data-test="add-recipient"]');
    await fillForm(page, {
      recipient_name: 'John Doe',
      recipient_email: 'john@example.com',
      recipient_type: 'signer',
    });
    await page.click('[data-test="save-recipient"]');
    await expect(page.locator('[data-test="recipient-item"]')).toContainText('John Doe');
  });

  test('user can add fields to envelope', async ({ page }) => {
    await page.click('[data-test="field-editor"]');
    await page.click('[data-test="add-signature-field"]');
    await page.locator('[data-test="document-canvas"]').click({ position: { x: 100, y: 100 } });
    await expect(page.locator('[data-test="field-signature"]')).toBeVisible();
  });

  test('user can save envelope as draft', async ({ page }) => {
    await fillForm(page, { subject: 'Draft Envelope' });
    await page.click('[data-test="save-draft"]');
    await waitForToast(page, 'success');
    await expect(page.locator('[data-test="envelope-status"]')).toContainText('draft');
  });
});
