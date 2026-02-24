import type { Page, Locator } from '@playwright/test';

export class CheckEmailPage {
  readonly heading: Locator;
  readonly description: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Check your email' });
    this.description = page.getByText('We sent an activation link to your email address.');
  }
}
