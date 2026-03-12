import type { Page, Locator } from '@playwright/test';

export class FormsPage {
  readonly heading: Locator;
  readonly createButton: Locator;
  readonly importButton: Locator;
  readonly emptyState: Locator;

  constructor(private readonly page: Page) {
    this.heading = page.getByRole('heading', { name: /forms/i });
    this.createButton = page.getByRole('button', { name: 'New Form' });
    this.importButton = page.getByRole('button', { name: 'Import' });
    this.emptyState = page.getByText('No forms yet.');
  }

  async clickExportMenuForRow(rowIndex = 0) {
    const rows = this.page.getByRole('row');
    await rows.nth(rowIndex + 1).getByLabel('Export').click();
  }

  async clickExportJsonForRow(rowIndex = 0) {
    await this.clickExportMenuForRow(rowIndex);
    await this.page.getByText('Export as JSON').click();
  }

  async clickExportYamlForRow(rowIndex = 0) {
    await this.clickExportMenuForRow(rowIndex);
    await this.page.getByText('Export as YAML').click();
  }

  async clickEditForRow(rowIndex = 0) {
    const rows = this.page.getByRole('row');
    await rows.nth(rowIndex + 1).getByLabel('common.edit').click();
  }

  async clickDeleteForRow(rowIndex = 0) {
    const rows = this.page.getByRole('row');
    await rows.nth(rowIndex + 1).getByLabel('common.delete').click();
  }

  async fillCreateForm(title: string, description?: string) {
    await this.page.getByLabel('Title').fill(title);
    if (description) {
      await this.page.getByLabel('Description').fill(description);
    }
  }

  async submitCreateForm() {
    await this.page.getByRole('dialog').getByRole('button', { name: 'New Form' }).click();
  }

  async confirmDelete() {
    await this.page.getByRole('dialog').getByRole('button', { name: 'Delete' }).click();
  }
}
