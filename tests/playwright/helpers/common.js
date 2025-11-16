/**
 * Common Helper Functions
 *
 * Shared utility functions for Playwright tests
 */

/**
 * Wait for API response
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} urlPattern
 * @param {number} timeout
 * @returns {Promise<Response>}
 */
export async function waitForAPI(page, urlPattern, timeout = 5000) {
  return await page.waitForResponse(
    (response) => response.url().includes(urlPattern) && response.status() === 200,
    { timeout }
  );
}

/**
 * Wait for toast notification
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} type - 'success', 'error', 'warning', 'info'
 * @param {number} timeout
 */
export async function waitForToast(page, type = 'success', timeout = 5000) {
  await page.waitForSelector(`[data-test="toast-${type}"]`, { timeout });
}

/**
 * Fill form fields
 *
 * @param {import('@playwright/test').Page} page
 * @param {object} fields - Key-value pairs of field name and value
 */
export async function fillForm(page, fields) {
  for (const [name, value] of Object.entries(fields)) {
    if (typeof value === 'boolean') {
      if (value) {
        await page.check(`input[name="${name}"]`);
      } else {
        await page.uncheck(`input[name="${name}"]`);
      }
    } else {
      await page.fill(`input[name="${name}"], textarea[name="${name}"], select[name="${name}"]`, String(value));
    }
  }
}

/**
 * Upload file
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 * @param {string} filePath
 */
export async function uploadFile(page, selector, filePath) {
  const fileInput = await page.locator(selector);
  await fileInput.setInputFiles(filePath);
}

/**
 * Wait for loading to complete
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} timeout
 */
export async function waitForLoading(page, timeout = 10000) {
  await page.waitForSelector('[data-test="loading"]', { state: 'hidden', timeout });
}

/**
 * Navigate to page and wait for load
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} url
 */
export async function navigateTo(page, url) {
  await page.goto(url);
  await page.waitForLoadState('networkidle');
}

/**
 * Click and wait for navigation
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 */
export async function clickAndWaitForNavigation(page, selector) {
  await Promise.all([
    page.waitForNavigation(),
    page.click(selector),
  ]);
}

/**
 * Get table row count
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} tableSelector
 * @returns {Promise<number>}
 */
export async function getTableRowCount(page, tableSelector = 'table tbody tr') {
  return await page.locator(tableSelector).count();
}

/**
 * Search in table/list
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} searchTerm
 * @param {string} searchInputSelector
 */
export async function searchTable(page, searchTerm, searchInputSelector = 'input[name="search"]') {
  await page.fill(searchInputSelector, searchTerm);
  await page.keyboard.press('Enter');
  await waitForLoading(page);
}

/**
 * Select from dropdown
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 * @param {string} value
 */
export async function selectDropdown(page, selector, value) {
  await page.selectOption(selector, value);
}

/**
 * Check if element exists
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 * @returns {Promise<boolean>}
 */
export async function elementExists(page, selector) {
  return (await page.locator(selector).count()) > 0;
}

/**
 * Get element text
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 * @returns {Promise<string>}
 */
export async function getElementText(page, selector) {
  return await page.locator(selector).textContent();
}

/**
 * Wait for element to be visible
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 * @param {number} timeout
 */
export async function waitForVisible(page, selector, timeout = 5000) {
  await page.waitForSelector(selector, { state: 'visible', timeout });
}

/**
 * Wait for element to be hidden
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 * @param {number} timeout
 */
export async function waitForHidden(page, selector, timeout = 5000) {
  await page.waitForSelector(selector, { state: 'hidden', timeout });
}

/**
 * Take screenshot
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} name
 */
export async function takeScreenshot(page, name) {
  await page.screenshot({ path: `screenshots/${name}.png`, fullPage: true });
}

/**
 * Confirm dialog
 *
 * @param {import('@playwright/test').Page} page
 * @param {boolean} accept
 */
export async function handleDialog(page, accept = true) {
  page.on('dialog', async (dialog) => {
    if (accept) {
      await dialog.accept();
    } else {
      await dialog.dismiss();
    }
  });
}

/**
 * Generate random string
 *
 * @param {number} length
 * @returns {string}
 */
export function randomString(length = 10) {
  return Math.random().toString(36).substring(2, length + 2);
}

/**
 * Generate random email
 *
 * @returns {string}
 */
export function randomEmail() {
  return `test${Date.now()}${randomString(5)}@example.com`;
}

/**
 * Wait for Alpine.js to be ready
 *
 * @param {import('@playwright/test').Page} page
 */
export async function waitForAlpine(page) {
  await page.waitForFunction(() => window.Alpine !== undefined);
}

/**
 * Scroll to element
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 */
export async function scrollToElement(page, selector) {
  await page.locator(selector).scrollIntoViewIfNeeded();
}

/**
 * Get current URL
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string>}
 */
export async function getCurrentURL(page) {
  return page.url();
}

/**
 * Reload page
 *
 * @param {import('@playwright/test').Page} page
 */
export async function reloadPage(page) {
  await page.reload();
  await page.waitForLoadState('networkidle');
}
