/**
 * The admin's information architecture.
 *
 * The admin is one application with a route per page rather than nine separate screens. That is
 * what lets the live preview stay mounted while you move between panels, and what lets the
 * settings search jump straight to a control in a panel you have not opened yet.
 */

export type RouteId =
  | 'dashboard'
  | 'design'
  | 'leads'
  | 'forms'
  | 'content'
  | 'demos'
  | 'safety'
  | 'licence'
  | 'system';

export interface RouteDefinition {
  readonly id: RouteId;
  /** The `page` query parameter WordPress registered for this screen. */
  readonly slug: string;
  readonly title: string;
  /** Lucide icon name; the sprite ships only the icons this list names. */
  readonly icon: string;
  /** The capability the server already checked. Repeated here to hide what a role cannot reach. */
  readonly capability: string;
  /** True for the one route that owns the live preview pane. */
  readonly hasPreview?: boolean;
}

export const ROUTES: readonly RouteDefinition[] = [
  {
    id: 'dashboard',
    slug: 'edulume',
    title: 'Dashboard',
    icon: 'layout-dashboard',
    capability: 'edulume_manage_theme',
  },
  {
    id: 'design',
    slug: 'edulume-design',
    title: 'Design',
    icon: 'palette',
    capability: 'edulume_manage_theme',
    hasPreview: true,
  },
  {
    id: 'leads',
    slug: 'edulume-leads',
    title: 'Leads',
    icon: 'inbox',
    capability: 'edulume_manage_leads',
  },
  {
    id: 'forms',
    slug: 'edulume-forms',
    title: 'Forms',
    icon: 'clipboard-list',
    capability: 'edulume_manage_leads',
  },
  {
    id: 'content',
    slug: 'edulume-content',
    title: 'Content tools',
    icon: 'database',
    capability: 'edulume_manage_content',
  },
  {
    id: 'demos',
    slug: 'edulume-demos',
    title: 'Starter demos',
    icon: 'sparkles',
    capability: 'edulume_import_demo_content',
  },
  {
    id: 'safety',
    slug: 'edulume-safety',
    title: 'Safety',
    icon: 'life-buoy',
    capability: 'edulume_manage_theme',
  },
  {
    id: 'licence',
    slug: 'edulume-licence',
    title: 'Licence',
    icon: 'key-round',
    capability: 'edulume_manage_theme',
  },
  {
    id: 'system',
    slug: 'edulume-system',
    title: 'System',
    icon: 'activity',
    capability: 'edulume_manage_theme',
  },
];

export function routeBySlug(slug: string): RouteDefinition | undefined {
  return ROUTES.find((route) => route.slug === slug);
}

export function routeById(id: RouteId): RouteDefinition | undefined {
  return ROUTES.find((route) => route.id === id);
}

/**
 * The routes a person with these capabilities may see.
 *
 * The server has already refused the page; this only decides what to draw in the sidebar. A menu
 * listing eight items that produce "you do not have permission" is worse than a menu of three
 * that work.
 */
export function visibleRoutes(capabilities: readonly string[]): readonly RouteDefinition[] {
  return ROUTES.filter((route) => capabilities.includes(route.capability));
}

/** Every icon the sprite has to contain — and nothing more. */
export function requiredIcons(): readonly string[] {
  return [...new Set(ROUTES.map((route) => route.icon))].sort();
}

/**
 * Resolves the route from the page's mount point.
 *
 * Falls back to the dashboard rather than rendering nothing: an unrecognised slug means the
 * server registered a page the app has not learned about yet, and a blank screen is the worst
 * possible way to communicate that.
 */
export function resolveRoute(slug: string | null | undefined): RouteDefinition {
  const found = slug === null || slug === undefined ? undefined : routeBySlug(slug);
  const dashboard = ROUTES[0];

  if (dashboard === undefined) {
    throw new Error('The route table is empty.');
  }

  return found ?? dashboard;
}

/** The admin URL for a route, so the sidebar links are real links. */
export function urlFor(route: RouteDefinition, adminUrl = '/wp-admin/'): string {
  return `${adminUrl}admin.php?page=${route.slug}`;
}
