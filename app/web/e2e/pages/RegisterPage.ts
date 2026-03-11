import type { Page, Locator } from '@playwright/test';

export class RegisterPage {
  readonly heading: Locator;
  readonly emailInput: Locator;
  readonly usernameInput: Locator;
  // Mantine PasswordInput: label association is intercepted by the wrapper div,
  // so use getByRole('textbox') with the accessible name instead of getByLabel.
  readonly passwordInput: Locator;
  readonly confirmPasswordInput: Locator;
  readonly createOrgCheckbox: Locator;
  readonly companyNameInput: Locator;
  readonly companyDomainInput: Locator;
  readonly signUpButton: Locator;
  readonly signInButton: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Create an account' });
    this.emailInput = page.getByLabel('Email');
    this.usernameInput = page.getByLabel('Username');
    this.passwordInput = page.getByRole('textbox', { name: 'Password', exact: true });
    this.confirmPasswordInput = page.getByRole('textbox', { name: 'Confirm password' });
    this.createOrgCheckbox = page.getByLabel('Create an organization');
    this.companyNameInput = page.getByLabel('Company name');
    this.companyDomainInput = page.getByLabel('Company domain');
    this.signUpButton = page.getByRole('button', { name: 'Sign Up' });
    this.signInButton = page.getByRole('button', { name: 'Sign In' });
  }

  get workspaceNameInput(): Locator {
    return this.page.getByLabel('Workspace name');
  }

  async fill(email: string, username: string, password: string, confirm: string, workspaceName = 'Test Workspace') {
    await this.emailInput.fill(email);
    await this.usernameInput.fill(username);
    await this.workspaceNameInput.fill(workspaceName);
    await this.passwordInput.fill(password);
    await this.confirmPasswordInput.fill(confirm);
  }

  async submit() {
    await this.signUpButton.click();
  }
}
