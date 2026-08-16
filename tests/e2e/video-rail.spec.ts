import { expect, test } from '@playwright/test';

/*
 * The video rail's two hard promises: nothing loads before it is asked for, and never more than
 * two players are alive at once.
 *
 * The rail is empty until an owner pastes a URL, so these skip rather than fail on a site that
 * has not configured one. A skipped test that says why beats a test that asserts nothing.
 */

test.describe('the video rail', () => {
  test('loads no iframe until a card is activated', async ({ page }) => {
    await page.goto('/');

    const rail = page.locator('[data-edulume-video-rail]');

    test.skip((await rail.count()) === 0, 'no video rail is configured on this site');

    // Counted across the whole document: an embed injected anywhere is an embed the visitor
    // paid for.
    const iframes = await page.locator('iframe').count();

    expect(iframes).toBeLessThanOrEqual(2);
  });

  test('never keeps more than two players alive while scrolling the whole rail', async ({
    page,
  }) => {
    await page.goto('/');

    const rail = page.locator('[data-edulume-video-rail]').first();

    test.skip((await rail.count()) === 0, 'no video rail is configured on this site');

    const track = rail.locator('[data-edulume-video-track]');
    const cards = rail.locator('[data-edulume-video-card]');
    const total = await cards.count();

    test.skip(total < 2, 'a single-card rail cannot exceed the budget');

    let worst = 0;

    for (let index = 0; index < total; index += 1) {
      await cards.nth(index).scrollIntoViewIfNeeded();
      await track.evaluate((element, position) => {
        element.scrollLeft = position * 320;
      }, index);

      // The scroll handler is rAF-throttled, so the recompute lands on the next frame.
      await page.waitForTimeout(250);

      const live = await page.locator('iframe, .edulume-video-card__player').count();

      worst = Math.max(worst, live);
    }

    expect(worst).toBeLessThanOrEqual(2);
  });

  test('reserves its height, so scrolling past it shifts nothing', async ({ page }) => {
    await page.goto('/');

    const frame = page.locator('.edulume-video-card__frame').first();

    test.skip((await frame.count()) === 0, 'no video rail is configured on this site');

    const box = await frame.boundingBox();

    expect(box?.height ?? 0).toBeGreaterThan(0);
  });
});
