import { Given, When } from '../fixtures';

Given('the login API returns 401 with code {string}', async ({ page }, code: string) => {
  await page.route('**/api/login', (route) =>
    route.fulfill({
      status: 401,
      contentType: 'application/json',
      body: JSON.stringify({ code }),
    }),
  );
});

Given('the login API returns 200', async ({ page }) => {
  await page.route('**/api/login', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({}),
    }),
  );
});

When(
  'I submit the login form with email {string} and password {string}',
  async ({ loginPage }, email: string, password: string) => {
    await loginPage.fill(email, password);
    await loginPage.submit();
  },
);
