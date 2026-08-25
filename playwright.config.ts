import { defineConfig, devices } from '@playwright/test';

/*
 * End-to-end tests against a real WordPress.
 *
 * The unit suite proves the rules; this proves the wiring. Every bug this feature round was
 * written to fix was a wiring bug — a section that rendered nothing, a screen with no route
 * behind it, a module bound to markup nobody emitted — and not one of them was catchable by a
 * test of either side on its own.
 *
 * `wp-env` is expected to be up already: the CI job that runs these has booted WordPress,
 * activated both, imported the demo pack and flushed rewrites. Starting it from here would mean
 * every local run paid a minute of Docker before the first assertion.
 */
export default defineConfig({
  testDir: './tests/e2e',
  // The suite is small and the pages are server-rendered; parallelism here mostly buys flake.
  fullyParallel: false,
  workers: 1,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? 'github' : 'list',
  timeout: 30_000,
  use: {
    baseURL: process.env.EDULUME_BASE_URL ?? 'http://localhost:8888',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
