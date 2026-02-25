import { expect } from '@playwright/test';
import { Given, Then, When } from '../fixtures';

Given('the admin users API returns an empty list', async ({ page }) => {
  await page.route('**/api/admin/users', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify([]),
      });
    }
    return route.continue();
  });
});

Given('the admin users API returns a list with one user', async ({ page }) => {
  await page.route('**/api/admin/users', (route) => {
    if (route.request().method() === 'GET') {
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify([
          {
            id: '1',
            email: 'admin@example.com',
            username: 'admin',
            firstName: null,
            lastName: null,
            roles: ['ROLE_SUPER_ADMIN'],
            isActive: true,
            mustChangePassword: false,
          },
        ]),
      });
    }
    return route.continue();
  });
});

Given('the admin users API returns 201 without generated password', async ({ page }) => {
  await page.route('**/api/admin/users', (route) => {
    if (route.request().method() === 'POST') {
      return route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ id: '2', email: 'new@example.com' }),
      });
    }
    return route.continue();
  });
});

Given(
  'the admin users API returns 201 with generated password {string}',
  async ({ page }, generatedPassword: string) => {
    await page.route('**/api/admin/users', (route) => {
      if (route.request().method() === 'POST') {
        return route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({ id: '3', email: 'new@example.com', generatedPassword }),
        });
      }
      return route.continue();
    });
  },
);

When(
  'I fill the create user form with email {string} and username {string}',
  async ({ adminUsersPage }, email: string, username: string) => {
    await adminUsersPage.fillCreateForm(email, username);
  },
);

Then('the password field is not empty', async ({ page }) => {
  const passwordInput = page.getByLabel('Password', { exact: true });
  const value = await passwordInput.inputValue();
  expect(value.length).toBeGreaterThan(0);
});

Then('the {string} checkbox is visible', async ({ page }, name: string) => {
  await expect(page.getByLabel(name)).toBeVisible();
});
