import type { Page, Locator } from '@playwright/test';

export class ActivatePage {
  readonly loader: Locator;

  constructor(private readonly page: Page) {
    this.loader = page.getByRole('status', { name: 'Loading' });
  }
}
