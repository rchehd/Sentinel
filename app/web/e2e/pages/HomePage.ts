import type { Page, Locator } from '@playwright/test';

export class HomePage {
  readonly heading: Locator;
  readonly logoutButton: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: 'Welcome to Sentinel' });
    this.logoutButton = page.getByRole('button', { name: 'Log out' });
  }
}
