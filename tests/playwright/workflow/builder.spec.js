import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth.js';
import { fillForm, waitForAPI, waitForToast } from '../helpers/common.js';

test.describe('Workflow Builder', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/workflow/builder');
  });

  test('user can create sequential workflow', async ({ page }) => {
    // Set workflow type
    await page.selectOption('[data-test="workflow-type"]', 'sequential');
    
    // Add first step
    await page.click('[data-test="add-step"]');
    await fillForm(page, {
      step_name: 'Step 1: Review',
      action: 'approve',
      recipient_email: 'reviewer@example.com',
    });
    await page.click('[data-test="save-step"]');
    
    // Add second step
    await page.click('[data-test="add-step"]');
    await fillForm(page, {
      step_name: 'Step 2: Sign',
      action: 'sign',
      recipient_email: 'signer@example.com',
    });
    await page.click('[data-test="save-step"]');
    
    // Verify workflow preview
    await expect(page.locator('[data-test="workflow-step"]')).toHaveCount(2);
    await expect(page.locator('[data-test="workflow-step"]').first()).toContainText('Step 1: Review');
    await expect(page.locator('[data-test="workflow-step"]').last()).toContainText('Step 2: Sign');
    
    // Save workflow
    await page.click('[data-test="save-workflow"]');
    await waitForAPI(page, '/workflow');
    await waitForToast(page, 'success');
  });

  test('user can create parallel workflow', async ({ page }) => {
    await page.selectOption('[data-test="workflow-type"]', 'parallel');
    
    // Add two parallel steps
    await page.click('[data-test="add-step"]');
    await fillForm(page, {
      step_name: 'Parallel 1',
      action: 'sign',
      recipient_email: 'signer1@example.com',
    });
    await page.click('[data-test="save-step"]');
    
    await page.click('[data-test="add-step"]');
    await fillForm(page, {
      step_name: 'Parallel 2',
      action: 'sign',
      recipient_email: 'signer2@example.com',
    });
    await page.click('[data-test="save-step"]');
    
    // Verify parallel indicator
    await expect(page.locator('[data-test="parallel-indicator"]')).toBeVisible();
    await expect(page.locator('[data-test="workflow-step"]')).toHaveCount(2);
  });

  test('user can reorder workflow steps', async ({ page }) => {
    await page.selectOption('[data-test="workflow-type"]', 'sequential');
    
    // Add two steps
    await page.click('[data-test="add-step"]');
    await fillForm(page, { step_name: 'First Step', action: 'sign' });
    await page.click('[data-test="save-step"]');
    
    await page.click('[data-test="add-step"]');
    await fillForm(page, { step_name: 'Second Step', action: 'approve' });
    await page.click('[data-test="save-step"]');
    
    // Move second step up
    await page.click('[data-test="workflow-step"]:has-text("Second Step") [data-test="move-up"]');
    
    // Verify new order
    await expect(page.locator('[data-test="workflow-step"]').first()).toContainText('Second Step');
    await expect(page.locator('[data-test="workflow-step"]').last()).toContainText('First Step');
  });
});
