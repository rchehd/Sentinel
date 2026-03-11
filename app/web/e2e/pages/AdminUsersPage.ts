import type { Page, Locator } from '@playwright/test';

export class AdminUsersPage {
  readonly heading: Locator;
  readonly emailInput: Locator;
  readonly usernameInput: Locator;
  readonly generatePasswordButton: Locator;
  readonly createUserButton: Locator;
  readonly usersTable: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Users' });
    this.emailInput = page.getByLabel('Email');
    this.usernameInput = page.getByLabel('Username');
    this.generatePasswordButton = page.getByRole('button', { name: 'Generate password' });
    this.createUserButton = page.getByRole('button', { name: 'Create user' });
    this.usersTable = page.getByRole('table');
  }

  async fillCreateForm(email: string, username: string) {
    await this.emailInput.fill(email);
    await this.usernameInput.fill(username);
  }

  async submitCreate() {
    await this.createUserButton.click();
  }
}
