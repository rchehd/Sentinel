import type { Page, Locator } from '@playwright/test';

export class SetupAdminPage {
  readonly heading: Locator;
  readonly emailInput: Locator;
  readonly usernameInput: Locator;
  readonly passwordInput: Locator;
  readonly confirmPasswordInput: Locator;
  readonly submitButton: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Create admin account' });
    this.emailInput = page.getByLabel('Email');
    this.usernameInput = page.getByLabel('Username');
    this.passwordInput = page.getByRole('textbox', { name: 'Password', exact: true });
    this.confirmPasswordInput = page.getByRole('textbox', { name: 'Confirm password' });
    this.submitButton = page.getByRole('button', { name: 'Create admin account' });
  }

  async fill(email: string, username: string, password: string, confirmPassword = password) {
    await this.emailInput.fill(email);
    await this.usernameInput.fill(username);
    await this.passwordInput.fill(password);
    await this.confirmPasswordInput.fill(confirmPassword);
  }

  async submit() {
    await this.submitButton.click();
  }
}
