export const REST_NAMESPACE = 'edulume/v1';

/**
 * Builds a REST route inside the Edulume namespace.
 *
 * Accepts a route with or without a leading slash and never emits a doubled one,
 * because callers write both forms and a doubled slash silently 404s in WordPress.
 */
export function restPath(route: string): string {
  const trimmed = route.replace(/^\/+/, '').replace(/\/+$/, '');

  if (trimmed === '') {
    return REST_NAMESPACE;
  }

  return `${REST_NAMESPACE}/${trimmed}`;
}
