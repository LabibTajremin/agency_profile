import { expect, test } from '@playwright/test';

/*
 * The destination pages.
 *
 * The demo pack seeds twelve destinations and forty institutions, so the archive and at least
 * one country page have real content behind them in CI.
 */

/*
 * A country page, found from the archive.
 *
 * Links back to the archive itself are filtered out — a breadcrumb or a "see all" link matches
 * the same selector, and following one lands on the archive and fails against the single
 * template for a reason that has nothing to do with the code under test.
 */
async function firstDestination(page: import('@playwright/test').Page): Promise<string> {
  await page.goto('/destinations/');

  const links = page.locator('a[href*="/destinations/"]');

  await expect(links.first()).toBeVisible();

  const total = await links.count();

  for (let index = 0; index < total; index += 1) {
    const href = (await links.nth(index).getAttribute('href')) ?? '';
    const path = href.replace(/^https?:\/\/[^/]+/, '').replace(/[?#].*$/, '');

    if (/^\/destinations\/[^/]+\/?$/.test(path)) {
      return path;
    }
  }

  return '';
}

test.describe('a destination page', () => {
  test('renders its own template rather than the generic single', async ({ page }) => {
    const url = await firstDestination(page);

    test.skip(url === '', 'this site has no published destinations');

    await page.goto(url);

    await expect(page.locator('.edulume-destination')).toBeVisible();
    await expect(page.locator('h1')).toBeVisible();
  });

  test('is complete in the server response, before any script runs', async ({ page }) => {
    // Read from the response rather than the DOM. The list is the page; filtering is an
    // addition to it, and a country page that renders an empty div until a fetch resolves is
    // one that nobody with JavaScript blocked — and no crawler — ever sees.
    const url = await firstDestination(page);

    test.skip(url === '', 'this site has no published destinations');

    const response = await page.request.get(url);
    const html = await response.text();

    expect(response.status()).toBe(200);
    expect(html).toContain('edulume-destination');
  });

  test('narrows the list as you type, without a reload', async ({ page }) => {
    const url = await firstDestination(page);

    test.skip(url === '', 'this site has no published destinations');

    await page.goto(url);

    const cards = page.locator('[data-edulume-university]');
    const total = await cards.count();

    test.skip(total < 2, 'this country has fewer than two universities to filter');

    const search = page.locator('[data-edulume-filter-search]');

    await search.fill('zzzzzznomatch');

    await expect(cards.first()).toBeHidden();

    // The filter reflects into the query string so a narrowed view is shareable.
    expect(page.url()).toContain('q=zzzzzznomatch');

    await search.fill('');

    await expect(cards.first()).toBeVisible();
  });
});
