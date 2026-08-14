import { describe, expect, it } from 'vitest';
import { SettingsSearchIndex, type RegisteredControl } from '../src/settingsSearch';

const CONTROLS: RegisteredControl[] = [
  { id: 'colors.accent', label: 'Accent', panel: 'Colors', keywords: ['brand', 'primary', 'hue'] },
  { id: 'colors.custom-hex', label: 'Custom accent hex', panel: 'Colors', isAdvanced: true },
  { id: 'typography.pairing', label: 'Font pairing', panel: 'Typography', keywords: ['typeface'] },
  { id: 'typography.ratio', label: 'Type scale ratio', panel: 'Typography', isAdvanced: true },
  {
    id: 'patterns.opacity-dark',
    label: 'Dark mode pattern opacity',
    panel: 'Patterns',
    isAdvanced: true,
  },
  { id: 'motion.preset', label: 'Motion preset', panel: 'Motion' },
  { id: 'layout.content-width', label: 'Content width', panel: 'Layout' },
];

function index(): SettingsSearchIndex {
  return new SettingsSearchIndex().register(...CONTROLS);
}

describe('SettingsSearchIndex', () => {
  it('reaches every registered control by its own label', () => {
    const search = index();

    CONTROLS.forEach((control) => {
      const hits = search.search(control.label);

      expect(hits.map((hit) => hit.control.id)).toContain(control.id);
    });
  });

  it('ranks an exact label above a partial one', () => {
    const hits = index().search('accent');

    expect(hits[0]?.control.id).toBe('colors.accent');
  });

  it('finds a control by a keyword that is not in its label', () => {
    expect(index().search('brand')[0]?.control.id).toBe('colors.accent');
    expect(index().search('typeface')[0]?.control.id).toBe('typography.pairing');
  });

  it('finds every control in a panel by the panel name', () => {
    const ids = index()
      .search('typography')
      .map((hit) => hit.control.id);

    expect(ids).toContain('typography.pairing');
    expect(ids).toContain('typography.ratio');
  });

  it('ignores case and surrounding space', () => {
    expect(index().search('  MOTION Preset ')[0]?.control.id).toBe('motion.preset');
  });

  it('returns nothing for an empty query rather than everything', () => {
    expect(index().search('')).toHaveLength(0);
    expect(index().search('   ')).toHaveLength(0);
  });

  it('returns nothing for a query that matches nothing', () => {
    expect(index().search('marzipan')).toHaveLength(0);
  });

  it('orders ties stably so the list does not reshuffle under the cursor', () => {
    const first = index().search('opacity');
    const second = index().search('opacity');

    expect(first.map((hit) => hit.control.id)).toEqual(second.map((hit) => hit.control.id));
  });

  it('honours the result limit', () => {
    expect(index().search('a', 2)).toHaveLength(2);
    expect(index().search('a', 0)).toHaveLength(0);
  });

  it('registers idempotently by id', () => {
    const search = index().register(...CONTROLS);

    expect(search.size).toBe(CONTROLS.length);
  });

  it('lets a control be removed when its panel unmounts', () => {
    const search = index().unregister('motion.preset');

    expect(search.has('motion.preset')).toBe(false);
    expect(search.search('motion preset')).toHaveLength(0);
  });

  it('sends a hit to its panel and control', () => {
    expect(index().destinationFor('colors.accent')).toEqual({
      panel: 'Colors',
      controlId: 'colors.accent',
      expandAdvanced: false,
    });
  });

  it('expands Advanced when the control hides behind it', () => {
    expect(index().destinationFor('typography.ratio')?.expandAdvanced).toBe(true);
  });

  it('has no destination for a control it does not know', () => {
    expect(index().destinationFor('nope')).toBeNull();
  });

  it('reaches a control that lives behind Advanced', () => {
    const hits = index().search('dark mode pattern opacity');

    expect(hits[0]?.control.id).toBe('patterns.opacity-dark');
    expect(hits[0]?.control.isAdvanced).toBe(true);
  });
});
