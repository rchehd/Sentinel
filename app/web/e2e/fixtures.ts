import { test as base, createBdd } from 'playwright-bdd';
import { LoginPage } from './pages/LoginPage';
import { RegisterPage } from './pages/RegisterPage';
import { CheckEmailPage } from './pages/CheckEmailPage';
import { ActivatePage } from './pages/ActivatePage';
import { HomePage } from './pages/HomePage';
import { ChangePasswordPage } from './pages/ChangePasswordPage';
import { AdminUsersPage } from './pages/AdminUsersPage';
import { SetupAdminPage } from './pages/SetupAdminPage';

export const test = base.extend<{
  loginPage: LoginPage;
  registerPage: RegisterPage;
  checkEmailPage: CheckEmailPage;
  activatePage: ActivatePage;
  homePage: HomePage;
  changePasswordPage: ChangePasswordPage;
  adminUsersPage: AdminUsersPage;
  setupAdminPage: SetupAdminPage;
}>({
  loginPage: async ({ page }, use) => use(new LoginPage(page)),
  registerPage: async ({ page }, use) => use(new RegisterPage(page)),
  checkEmailPage: async ({ page }, use) => use(new CheckEmailPage(page)),
  activatePage: async ({ page }, use) => use(new ActivatePage(page)),
  homePage: async ({ page }, use) => use(new HomePage(page)),
  changePasswordPage: async ({ page }, use) => use(new ChangePasswordPage(page)),
  adminUsersPage: async ({ page }, use) => use(new AdminUsersPage(page)),
  setupAdminPage: async ({ page }, use) => use(new SetupAdminPage(page)),
});

export const { Given, When, Then } = createBdd(test);
