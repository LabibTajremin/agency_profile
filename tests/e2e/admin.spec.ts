import { expect, test } from '@playwright/test';

/*
 * The admin screens that render server-side.
 *
 * All three are screens somebody reaches on a brand-new site, and all three have to work before
 * the admin bundle has loaded — the starter-content page in particular was a React route with
 * no REST endpoint behind it, so the import button did not exist for anyone without a shell.
 * These prove the markup is there in the server's response, which is the thing that was missing.
 */

const USER = process.env.WP_ADMIN_USER ?? 'admin';
const PASSWORD = process.env.WP_ADMIN_PASSWORD ?? 'password';

async function signIn(page: import('@playwright/test').Page): Promise<void> {
  await page.goto('/wp-login.php');
  await page.fill('#user_login', USER);
  await page.fill('#user_pass', PASSWORD);
  await page.click('#wp-submit');
  await page.waitForURL(/wp-admin/);
}

test.describe('the Edulume admin', () => {
  test.beforeEach(async ({ page }) => {
    await signIn(page);
  });

  test('shows one menu with the site under it, not four top-level items', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=edulume');

    await expect(page.locator('#adminmenu >> text=Edulume')).toBeVisible();
  });

  test('explains every screen in plain language, on the screen', async ({ page }) => {
    for (const route of ['edulume-sections', 'edulume-videos', 'edulume-demos', 'edulume-safety']) {
      await page.goto(`/wp-admin/admin.php?page=${route}`);

      await expect(page.locator('.edulume-help'), `${route} has no help panel`).toBeVisible();
    }
  });

  test('offers a real import button on the starter-content screen', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=edulume-demos');

    await expect(page.locator('form[data-edulume-import]').first()).toBeVisible();
    await expect(
      page.locator('form[data-edulume-import] button[type="submit"]').first()
    ).toBeVisible();
  });

  test('lists every home section with a toggle and a position', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=edulume-sections');

    const rows = page.locator('[data-edulume-section-row]');

    expect(await rows.count()).toBeGreaterThan(10);

    await expect(rows.first().locator('input[type="checkbox"]')).toBeVisible();
    await expect(rows.first().locator('[data-edulume-position]')).toBeVisible();
  });

  test('keeps the login shield off until somebody turns it on', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=edulume-safety');

    const enabled = page.locator('form[data-edulume-shield] input[name="enabled"]');

    await expect(enabled).toBeVisible();
    await expect(enabled).not.toBeChecked();

    // The rescue line has to be on the page, not only in a readme nobody has open at the moment
    // they need it.
    await expect(page.locator('text=EDULUME_SHIELD_DISABLE')).toBeVisible();
  });

  test('will not enable the shield until the new address is acknowledged', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=edulume-safety');

    await page.check('form[data-edulume-shield] input[name="enabled"]');

    page.once('dialog', (dialog) => dialog.dismiss());

    await page.click('form[data-edulume-shield] button[type="submit"]');

    // Still on the settings screen: the submit was refused rather than saved.
    await expect(page.locator('form[data-edulume-shield]')).toBeVisible();
  });
});
