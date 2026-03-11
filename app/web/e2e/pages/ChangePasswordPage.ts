import type { Page, Locator } from '@playwright/test';

export class ChangePasswordPage {
  readonly heading: Locator;
  readonly newPasswordInput: Locator;
  readonly confirmPasswordInput: Locator;
  readonly submitButton: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Set new password' });
    this.newPasswordInput = page.getByLabel('New password');
    this.confirmPasswordInput = page.getByLabel('Confirm password');
    this.submitButton = page.getByRole('button', { name: 'Set password' });
  }

  async fill(newPassword: string, confirmPassword: string) {
    await this.newPasswordInput.fill(newPassword);
    await this.confirmPasswordInput.fill(confirmPassword);
  }

  async submit() {
    await this.submitButton.click();
  }
}
