import { defineConfig, devices } from '@playwright/test';
import { defineBddConfig } from 'playwright-bdd';

// In Docker, glibc 2.37+ maps *.localhost to 127.0.0.1 (RFC 6761), ignoring
// /etc/hosts. Use Chromium's --host-resolver-rules to bypass OS DNS and route
// directly to the Caddy container on the Docker network.
const resolverRules = process.env.HOST_RESOLVER_RULES;

export default defineConfig({
  testDir: defineBddConfig({
    features: 'e2e/features/**/*.feature',
    steps: ['e2e/steps/**/*.ts', 'e2e/fixtures.ts'],
  }),
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: process.env.BASE_URL ?? 'https://sentinel.localhost',
    ignoreHTTPSErrors: true,
    trace: 'on-first-retry',
    screenshot: (process.env.PW_SCREENSHOT as 'on' | 'only-on-failure' | 'off') ?? 'only-on-failure',
    video: (process.env.PW_VIDEO as 'on' | 'retain-on-failure' | 'off') ?? 'retain-on-failure',
    launchOptions: {
      args: resolverRules ? [`--host-resolver-rules=${resolverRules}`] : [],
    },
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'], locale: 'en' },
    },
  ],
});
