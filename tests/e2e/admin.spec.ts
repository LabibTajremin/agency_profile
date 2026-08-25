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

    const menu = page
      .locator('#adminmenu > li.menu-top')
      .filter({ has: page.locator('.wp-menu-name', { hasText: 'Edulume' }) });

    await expect(menu).toHaveCount(1);

    // The screens live under that one menu rather than beside it, which is the whole claim.
    const screens = menu.locator('.wp-submenu li:not(.wp-submenu-head) a');

    expect(await screens.count()).toBeGreaterThan(3);
  });

  const ROUTES = [
    'edulume',
    'edulume-design',
    'edulume-sections',
    'edulume-videos',
    'edulume-leads',
    'edulume-forms',
    'edulume-content',
    'edulume-demos',
    'edulume-safety',
    'edulume-licence',
    'edulume-system',
  ];

  test('explains every screen in plain language, on the screen', async ({ page }) => {
    for (const route of ROUTES) {
      await page.goto(`/wp-admin/admin.php?page=${route}`);

      await expect(page.locator('.edulume-help'), `${route} has no help panel`).toBeVisible();
    }
  });

  /*
   * The test that was missing.
   *
   * Seven of these eleven pages used to render their help panel and an empty div: the menu
   * registered the page, the screen behind it was never written, and the configurator bundle
   * meant to draw it was never enqueued. Asserting the help panel is present passed the whole
   * time, because the help panel was the only thing there.
   *
   * So this asserts the opposite: something you can operate, below the help panel.
   */
  test('gives every screen something to actually operate, not just a help panel', async ({
    page,
  }) => {
    for (const route of ROUTES) {
      await page.goto(`/wp-admin/admin.php?page=${route}`);

      const controls = page.locator(
        '.edulume-screen :is(input, select, textarea, button, a.edulume-tile)'
      );

      expect(await controls.count(), `${route} renders no controls at all`).toBeGreaterThan(0);
    }
  });

  test('saves a design change and shows it back', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=edulume-design');

    const density = page.locator('select[name="settings[layout][density]"]');

    await expect(density).toBeVisible();
    await density.selectOption('spacious');
    await page.locator('button[type="submit"]:has-text("Save design")').click();

    await expect(page.locator('.notice-success')).toBeVisible();
    await expect(page.locator('select[name="settings[layout][density]"]')).toHaveValue('spacious');
  });

  test('offers the forms the theme asks for when the site has none', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=edulume-forms');

    // Either the forms exist and are listed, or the screen offers to create them. A screen that
    // does neither is the state that made every enquiry on the live site fail.
    const listed = await page.locator('.edulume-group__title').count();
    const offered = await page
      .locator('form[action*="admin-post"] button:has-text("Create them")')
      .count();

    expect(listed + offered).toBeGreaterThan(0);
  });

  test('reports what the install is on the system screen', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=edulume-system');

    await expect(page.locator('.edulume-report')).toBeVisible();
    await expect(page.locator('[data-edulume-report]')).toContainText('PHP');
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

  test('gates enabling the shield behind an acknowledgement of the new address', async ({
    page,
  }) => {
    await page.goto('/wp-admin/admin.php?page=edulume-safety');

    /*
     * Deliberately does not submit.
     *
     * The first version of this test ticked "enable" and clicked save, expecting the browser
     * confirmation to block it. If the enhancement script fails to load for any reason nothing
     * blocks it — the shield genuinely enables on the site under test, wp-login.php starts
     * 404ing, and the retry of this very file fails at sign-in looking like an unrelated auth
     * problem. A test that can only be run once is not a test.
     *
     * So it asserts the gate is present and wired instead: the checkbox exists, starts
     * unticked, and the form carries the hook the script binds to.
     */
    const form = page.locator('form[data-edulume-shield]');
    const confirm = form.locator('[data-edulume-shield-confirm]');

    await expect(form).toBeVisible();
    await expect(confirm).toBeVisible();
    await expect(confirm).not.toBeChecked();

    // The new address is shown before it is agreed to; agreeing to an unseen URL is the
    // failure the whole gate exists to prevent.
    await expect(form.locator('.edulume-shield__confirm code')).toContainText('/');
  });
});
