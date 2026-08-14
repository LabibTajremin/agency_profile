import { describe, expect, it } from 'vitest';

import {
  ROUTES,
  requiredIcons,
  resolveRoute,
  routeById,
  routeBySlug,
  urlFor,
  visibleRoutes,
} from '../src/shell/routes';

describe('the admin route table', () => {
  it('lists the nine screens in sidebar order', () => {
    expect(ROUTES.map((route) => route.id)).toEqual([
      'dashboard',
      'design',
      'leads',
      'forms',
      'content',
      'demos',
      'safety',
      'licence',
      'system',
    ]);
  });

  it('gives every route a unique slug, an icon and a capability', () => {
    const slugs = ROUTES.map((route) => route.slug);

    expect(slugs).toEqual([...new Set(slugs)]);

    for (const route of ROUTES) {
      expect(route.icon, route.id).not.toBe('');
      expect(route.capability, route.id).toMatch(/^edulume_/);
      expect(route.title, route.id).not.toBe('');
    }
  });

  it('gives the preview pane to exactly one route', () => {
    expect(ROUTES.filter((route) => route.hasPreview === true).map((route) => route.id)).toEqual([
      'design',
    ]);
  });

  it('finds a route by slug and by id', () => {
    expect(routeBySlug('edulume-leads')?.id).toBe('leads');
    expect(routeById('design')?.slug).toBe('edulume-design');
    expect(routeBySlug('nope')).toBeUndefined();
    expect(routeById('nope' as never)).toBeUndefined();
  });

  it('hides what a role cannot reach rather than showing it and refusing', () => {
    const counsellor = visibleRoutes(['edulume_manage_leads']);

    expect(counsellor.map((route) => route.id)).toEqual(['leads', 'forms']);
    expect(visibleRoutes([])).toEqual([]);
  });

  it('falls back to the dashboard for an unknown or missing slug', () => {
    expect(resolveRoute('edulume-design').id).toBe('design');
    expect(resolveRoute('edulume-not-a-page').id).toBe('dashboard');
    expect(resolveRoute(null).id).toBe('dashboard');
    expect(resolveRoute(undefined).id).toBe('dashboard');
  });

  it('names only the icons the sprite has to carry', () => {
    const icons = requiredIcons();

    expect(icons).toEqual([...icons].sort());
    expect(icons).toEqual([...new Set(icons)]);
    expect(icons).toContain('palette');
    expect(icons).toHaveLength(ROUTES.length);
  });

  it('builds a real admin link for each route', () => {
    expect(urlFor(ROUTES[1]!)).toBe('/wp-admin/admin.php?page=edulume-design');
    expect(urlFor(ROUTES[0]!, 'https://example.test/wp-admin/')).toBe(
      'https://example.test/wp-admin/admin.php?page=edulume'
    );
  });
});
