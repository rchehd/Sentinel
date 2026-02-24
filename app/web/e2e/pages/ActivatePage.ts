import type { Page, Locator } from '@playwright/test';

export class ActivatePage {
  // The Mantine Loader renders with role="status" aria-label="Loading"
  readonly loader: Locator;

  constructor(private readonly page: Page) {
    this.loader = page.getByRole('status', { name: 'Loading' });
  }
}
