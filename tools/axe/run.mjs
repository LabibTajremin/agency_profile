/*
 * The accessibility sweep.
 *
 * Every template, in both modes. Auditing one page in one mode is how a dark mode ships with an
 * invisible focus ring: the light run passed, and nothing ever looked at the other half.
 *
 * Only critical and serious violations fail the build. Moderate and minor ones are printed —
 * a gate that fails on every "best practice" advisory is a gate people start skipping, and a
 * skipped gate catches nothing at all.
 */

import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';

const BASE_URL = process.env.EDULUME_BASE_URL ?? 'http://localhost:8888';

const TEMPLATES = [
  { path: '/', name: 'home' },
  { path: '/courses/', name: 'course archive' },
  { path: '/courses/?level=masters', name: 'course finder, filtered' },
  { path: '/destinations/', name: 'destination archive' },
  { path: '/institutions/', name: 'institution archive' },
  { path: '/services/', name: 'service archive' },
  { path: '/about/', name: 'about' },
  { path: '/contact/', name: 'contact' },
  { path: '/?s=engineering', name: 'search results' },
  { path: '/this-page-does-not-exist/', name: '404' },
];

const MODES = ['light', 'dark'];

const BLOCKING_IMPACTS = new Set(['critical', 'serious']);

function describe(violation) {
  const targets = violation.nodes
    .slice(0, 3)
    .map((node) => node.target.join(' '))
    .join(', ');

  return `  [${violation.impact}] ${violation.id}: ${violation.help}\n    at ${targets}`;
}

async function auditPage(page, template, mode) {
  await page.goto(`${BASE_URL}${template.path}`, { waitUntil: 'networkidle' });

  // Set before the run, not after: the mode attribute is what the theme's tokens key off, so
  // auditing without it measures the light palette twice.
  await page.evaluate((chosen) => {
    document.documentElement.setAttribute('data-theme', chosen);
  }, mode);

  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();

  return results.violations;
}

async function main() {
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const page = await context.newPage();

  let blocking = 0;
  let advisory = 0;

  for (const template of TEMPLATES) {
    for (const mode of MODES) {
      const violations = await auditPage(page, template, mode);

      if (violations.length === 0) {
        console.log(`✓ ${template.name} (${mode})`);
        continue;
      }

      const blockingHere = violations.filter((violation) => BLOCKING_IMPACTS.has(violation.impact));

      blocking += blockingHere.length;
      advisory += violations.length - blockingHere.length;

      console.log(`${blockingHere.length > 0 ? '✗' : '!'} ${template.name} (${mode})`);
      violations.forEach((violation) => console.log(describe(violation)));
    }
  }

  await browser.close();

  console.log(`\n${blocking} blocking violation(s), ${advisory} advisory.`);

  if (blocking > 0) {
    process.exitCode = 1;
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
