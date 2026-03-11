import { Given, When } from '../fixtures';

Given('the change password API returns 200', async ({ page }) => {
  await page.route('**/api/password/change', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({}),
    }),
  );
});

Given('the change password API returns error with code {string}', async ({ page }, code: string) => {
  await page.route('**/api/password/change', (route) =>
    route.fulfill({
      status: 422,
      contentType: 'application/json',
      body: JSON.stringify({ code }),
    }),
  );
});

Given('the login API returns 200 with mustChangePassword', async ({ page }) => {
  await page.route('**/api/login', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ mustChangePassword: true }),
    }),
  );
});

When(
  'I fill the change password form with {string} and {string}',
  async ({ changePasswordPage }, newPassword: string, confirmPassword: string) => {
    await changePasswordPage.fill(newPassword, confirmPassword);
  },
);
