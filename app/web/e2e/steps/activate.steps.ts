import { expect } from '@playwright/test';
import { Given, When, Then } from '../fixtures';

Given('the activation API returns {int}', async ({ page }, status: number) => {
  await page.route('**/api/activate/*', (route) =>
    route.fulfill({
      status,
      contentType: 'application/json',
      body: JSON.stringify({}),
    }),
  );
});

Given('the activation API returns 200 after a delay', async ({ page }) => {
  await page.route('**/api/activate/*', async (route) => {
    await page.waitForTimeout(300);
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({}),
    });
  });
});

When('I visit the activation page with token {string}', async ({ page }, token: string) => {
  await page.goto(`/activate/${token}`);
});

When('I visit the activation page early with token {string}', async ({ page }, token: string) => {
  await page.goto(`/activate/${token}`, { waitUntil: 'domcontentloaded' });
});

Then('the activation loader is visible', async ({ activatePage }) => {
  await expect(activatePage.loader).toBeVisible();
});
