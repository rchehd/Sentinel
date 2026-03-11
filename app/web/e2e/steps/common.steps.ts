import { expect } from '@playwright/test';
import { Given, When, Then } from '../fixtures';

Given('I navigate to {string}', async ({ page }, url: string) => {
  await page.goto(url);
});

Then('the page URL is {string}', async ({ page }, url: string) => {
  await expect(page).toHaveURL(url);
});

Then('I am redirected to {string}', async ({ page }, url: string) => {
  await page.waitForURL(url);
});

Then('I see the heading {string}', async ({ page }, name: string) => {
  await expect(page.getByRole('heading', { name })).toBeVisible();
});

Then('I see the text {string}', async ({ page }, text: string) => {
  await expect(page.getByText(text)).toBeVisible();
});

Then('the {string} button is visible', async ({ page }, name: string) => {
  await expect(page.getByRole('button', { name })).toBeVisible();
});

When('I click {string}', async ({ page }, name: string) => {
  await page.getByRole('button', { name }).click();
});

Then('I see field error {string}', async ({ page }, text: string) => {
  await expect(page.getByText(text)).toBeVisible();
});

Then('I see an alert containing {string}', async ({ page }, text: string) => {
  await expect(page.getByRole('alert').filter({ hasText: text })).toBeVisible();
});

Then('the {string} field is visible', async ({ page }, name: string) => {
  await expect(page.getByLabel(name)).toBeVisible();
});

Then('the {string} field is not visible', async ({ page }, name: string) => {
  await expect(page.getByLabel(name)).not.toBeVisible();
});

When('I check the {string} checkbox', async ({ page }, name: string) => {
  await page.getByLabel(name).check();
});

Given('I am authenticated as a user', async ({ page }) => {
  await page.route('**/api/me', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: 'user-id',
        email: 'user@example.com',
        username: 'testuser',
        firstName: null,
        lastName: null,
        roles: ['ROLE_USER'],
        mustChangePassword: false,
      }),
    }),
  );
});

Given('the workspaces API returns a workspace with slug {string}', async ({ page }, slug: string) => {
  await page.route('**/api/workspaces', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify([{ id: 'ws-id', name: 'Test Workspace', slug }]),
    }),
  );
});
