import { describe, expect, it } from 'vitest';
import { REST_NAMESPACE, restPath } from '../src/restPath';

describe('restPath', () => {
  it('prefixes a bare route with the Edulume namespace', () => {
    expect(restPath('settings')).toBe('edulume/v1/settings');
  });

  it('tolerates a leading slash without emitting a doubled one', () => {
    expect(restPath('/settings')).toBe('edulume/v1/settings');
  });

  it('drops a trailing slash', () => {
    expect(restPath('settings/')).toBe('edulume/v1/settings');
  });

  it('returns the bare namespace for an empty route', () => {
    expect(restPath('')).toBe(REST_NAMESPACE);
  });
});
