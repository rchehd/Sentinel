import { expect } from '@playwright/test';
import { Given, When, Then } from '../fixtures';

// ---------------------------------------------------------------------------
// Setup status mocks
// ---------------------------------------------------------------------------

/**
 * Mocks GET /api/setup/status so the first call returns unconfigured (shows
 * the setup wizard) and every subsequent call returns configured (so that
 * after window.location.replace('/login') the login page renders normally).
 */
Given('the app is not configured', async ({ page }) => {
  // Fallback for all calls after the first one (routes are matched LIFO)
  await page.route('**/api/setup/status', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ configured: true, mode: 'self_hosted' }),
    }),
  )
  // First call only: unconfigured
  await page.route(
    '**/api/setup/status',
    (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ configured: false, mode: 'self_hosted' }),
      }),
    { times: 1 },
  )
})

Given('the app is configured', async ({ page }) => {
  await page.route('**/api/setup/status', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ configured: true, mode: 'self_hosted' }),
    }),
  )
})

// ---------------------------------------------------------------------------
// Setup admin API mock
// ---------------------------------------------------------------------------

Given('the setup admin API returns 201', async ({ page }) => {
  await page.route('**/api/setup/admin', (route) =>
    route.fulfill({
      status: 201,
      contentType: 'application/json',
      body: JSON.stringify({ message: 'Admin account created.' }),
    }),
  )
})

// ---------------------------------------------------------------------------
// Setup form interactions
// ---------------------------------------------------------------------------

Then('the setup password fields are visible', async ({ setupAdminPage }) => {
  await expect(setupAdminPage.passwordInput).toBeVisible();
  await expect(setupAdminPage.confirmPasswordInput).toBeVisible();
});

When('I fill the setup form with valid credentials', async ({ setupAdminPage }) => {
  await setupAdminPage.fill('admin@example.com', 'admin', 'Password123')
})

When(
  'I fill the setup form with email {string} username {string} password {string} and confirm {string}',
  async ({ setupAdminPage }, email: string, username: string, password: string, confirm: string) => {
    await setupAdminPage.fill(email, username, password, confirm)
  },
)
