import { expect } from '@playwright/test';
import { Given, When, Then } from '../fixtures';

Given('the register API returns 201', async ({ page }) => {
  await page.route('**/api/register', (route) =>
    route.fulfill({
      status: 201,
      contentType: 'application/json',
      body: JSON.stringify({}),
    }),
  );
});

Given('the register API returns 422 with detail {string}', async ({ page }, detail: string) => {
  await page.route('**/api/register', (route) =>
    route.fulfill({
      status: 422,
      contentType: 'application/json',
      body: JSON.stringify({ detail }),
    }),
  );
});

Then('the register password fields are visible', async ({ registerPage }) => {
  await expect(registerPage.passwordInput).toBeVisible();
  await expect(registerPage.confirmPasswordInput).toBeVisible();
});

When('I enter password {string} in the register form', async ({ registerPage }, password: string) => {
  await registerPage.passwordInput.fill(password);
});

When(
  'I enter confirm password {string} in the register form',
  async ({ registerPage }, confirm: string) => {
    await registerPage.confirmPasswordInput.fill(confirm);
  },
);

When(
  'I fill the register form with email {string}, username {string}, password {string}',
  async ({ registerPage }, email: string, username: string, password: string) => {
    await registerPage.fill(email, username, password, password);
  },
);
