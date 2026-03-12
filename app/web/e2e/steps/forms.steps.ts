import { expect } from '@playwright/test';
import { Given, When, Then } from '../fixtures';

const WORKSPACE_ID = 'ws-id';
const FORM_ID = 'form-id';

// ---------------------------------------------------------------------------
// API mocks — Given
// ---------------------------------------------------------------------------

Given('the forms API returns an empty list', async ({ page }) => {
  await page.route(`**/api/workspaces/${WORKSPACE_ID}/forms`, (route) => {
    if (route.request().method() === 'GET') {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify([]),
      });
    } else {
      route.continue();
    }
  });
});

Given('the forms API returns a list with form {string}', async ({ page }, title: string) => {
  await page.route(`**/api/workspaces/${WORKSPACE_ID}/forms`, (route) => {
    if (route.request().method() === 'GET') {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify([
          {
            id: FORM_ID,
            title,
            description: null,
            status: 'draft',
            currentRevision: null,
            createdAt: '2024-01-01T00:00:00Z',
          },
        ]),
      });
    } else {
      route.continue();
    }
  });
});

Given(
  'the create form API returns a new form with title {string}',
  async ({ page }, title: string) => {
    await page.route(`**/api/workspaces/${WORKSPACE_ID}/forms`, (route) => {
      if (route.request().method() === 'POST') {
        route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            id: 'new-form-id',
            title,
            description: null,
            status: 'draft',
            currentRevision: null,
            createdAt: new Date().toISOString(),
          }),
        });
      } else {
        route.continue();
      }
    });
  },
);

Given(
  'the update form API returns a form with title {string}',
  async ({ page }, title: string) => {
    await page.route(`**/api/workspaces/${WORKSPACE_ID}/forms/${FORM_ID}`, (route) => {
      if (route.request().method() === 'PATCH') {
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            id: FORM_ID,
            title,
            description: null,
            status: 'draft',
            currentRevision: null,
            createdAt: '2024-01-01T00:00:00Z',
          }),
        });
      } else {
        route.continue();
      }
    });
  },
);

Given('the delete form API returns success', async ({ page }) => {
  await page.route(`**/api/workspaces/${WORKSPACE_ID}/forms/${FORM_ID}`, (route) => {
    if (route.request().method() === 'DELETE') {
      route.fulfill({ status: 204, body: '' });
    } else {
      route.continue();
    }
  });
});

Given('the export form API returns JSON content', async ({ page }) => {
  await page.route(`**/api/workspaces/${WORKSPACE_ID}/forms/${FORM_ID}/export**`, (route) => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      headers: {
        'Content-Disposition': 'attachment; filename="form.json"',
      },
      body: JSON.stringify({ title: 'Exportable Form', status: 'draft' }),
    });
  });
});

// ---------------------------------------------------------------------------
// Actions — When
// ---------------------------------------------------------------------------

When('I fill in {string} with {string}', async ({ page }, label: string, value: string) => {
  await page.getByLabel(label).fill(value);
});

When('I click the create form submit button', async ({ page }) => {
  await page.getByRole('dialog').getByRole('button', { name: 'New Form' }).click();
});

When('I click the edit button for the first form', async ({ page }) => {
  await page.getByLabel('common.edit').first().click();
});

When('I click the delete button for the first form', async ({ page }) => {
  await page.getByLabel('common.delete').first().click();
});

When('I clear the title input and type {string}', async ({ page }, value: string) => {
  const input = page.getByRole('dialog').getByLabel('Title');
  await input.clear();
  await input.fill(value);
});

When('I click the save button in the dialog', async ({ page }) => {
  await page.getByRole('dialog').getByRole('button', { name: 'Save' }).click();
});

When('I confirm the deletion', async ({ page }) => {
  await page.getByRole('dialog').getByRole('button', { name: 'Delete' }).click();
});

When('I open the export menu for the first form', async ({ page }) => {
  await page.getByLabel('Export').first().click();
});

// ---------------------------------------------------------------------------
// Assertions — Then
// ---------------------------------------------------------------------------

Then('the title input has value {string}', async ({ page }, value: string) => {
  const input = page.getByRole('dialog').getByLabel('Title');
  await expect(input).toHaveValue(value);
});

Then('I do not see the text {string}', async ({ page }, text: string) => {
  await expect(page.getByText(text)).not.toBeVisible();
});
