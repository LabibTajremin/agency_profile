import { expect, test } from '@playwright/test';

/*
 * The front page, end to end.
 *
 * These assert the two things this feature round exists to guarantee: that a section renders
 * something rather than bailing out on an empty query, and that the theme toggle survives a
 * reload. Both were broken in shipped code, and both passed every unit test in the suite —
 * which is the whole argument for this file.
 */

test.describe('the front page', () => {
  test('renders sections with content in them, not empty scopes', async ({ page }) => {
    await page.goto('/');

    const scopes = page.locator('[data-edulume-section]');

    await expect(scopes.first()).toBeVisible();

    const total = await scopes.count();

    expect(total).toBeGreaterThan(10);

    /*
     * The failure this round fixed: the wrapper rendered, the section inside it bailed on an
     * empty query, and the front page was a header and a footer with a stack of empty divs
     * between them — one populated section out of eleven.
     *
     * Counted rather than asserted per section, because a few legitimately stay quiet: the
     * video rail until an owner pastes a URL, and any post-backed section on a site that has
     * not imported the demo pack.
     */
    const drawn = await page.locator('[data-edulume-section] section').count();

    expect(drawn, 'most of the front page rendered nothing').toBeGreaterThanOrEqual(10);
  });

  test('has a heading and a working search field in the hero', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('.edulume-hero__search input[type="search"]')).toBeVisible();
  });

  test('does not scroll sideways at 320px', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 720 });
    await page.goto('/');

    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth
    );

    expect(overflow).toBeLessThanOrEqual(2);
  });
});

test.describe('the theme toggle', () => {
  test('switches mode and keeps it across a reload', async ({ page }) => {
    await page.goto('/');

    const toggle = page.locator('[data-edulume-mode-toggle]').first();

    await expect(toggle).toBeVisible();

    const before = await page.evaluate(
      () => document.documentElement.getAttribute('data-theme') ?? 'light'
    );

    await toggle.click();

    const after = await page.evaluate(
      () => document.documentElement.getAttribute('data-theme') ?? 'light'
    );

    expect(after).not.toBe(before);

    await page.reload();

    const restored = await page.evaluate(
      () => document.documentElement.getAttribute('data-theme') ?? 'light'
    );

    expect(restored).toBe(after);
  });

  test('names the mode it will switch to, not the one showing', async ({ page }) => {
    await page.goto('/');

    const toggle = page.locator('[data-edulume-mode-toggle]').first();
    const label = (await toggle.getAttribute('aria-label')) ?? '';
    const mode = await page.evaluate(
      () => document.documentElement.getAttribute('data-theme') ?? 'light'
    );

    // A toggle labelled with the state you are already in is the usual defect in these.
    expect(label.toLowerCase()).toContain(mode === 'dark' ? 'light' : 'dark');
  });

  test('is operable from the keyboard alone', async ({ page }) => {
    await page.goto('/');

    const toggle = page.locator('[data-edulume-mode-toggle]').first();

    await toggle.focus();
    await expect(toggle).toBeFocused();

    const before = await page.evaluate(
      () => document.documentElement.getAttribute('data-theme') ?? 'light'
    );

    await page.keyboard.press('Enter');

    const after = await page.evaluate(
      () => document.documentElement.getAttribute('data-theme') ?? 'light'
    );

    expect(after).not.toBe(before);
  });
});
