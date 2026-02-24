import { test as base, createBdd } from 'playwright-bdd';
import { LoginPage } from './pages/LoginPage';
import { RegisterPage } from './pages/RegisterPage';
import { CheckEmailPage } from './pages/CheckEmailPage';
import { ActivatePage } from './pages/ActivatePage';
import { HomePage } from './pages/HomePage';

export const test = base.extend<{
  loginPage: LoginPage;
  registerPage: RegisterPage;
  checkEmailPage: CheckEmailPage;
  activatePage: ActivatePage;
  homePage: HomePage;
}>({
  loginPage: async ({ page }, use) => use(new LoginPage(page)),
  registerPage: async ({ page }, use) => use(new RegisterPage(page)),
  checkEmailPage: async ({ page }, use) => use(new CheckEmailPage(page)),
  activatePage: async ({ page }, use) => use(new ActivatePage(page)),
  homePage: async ({ page }, use) => use(new HomePage(page)),
});

export const { Given, When, Then } = createBdd(test);
