import { describe, expect, it } from 'vitest';

import {
  advancedControls,
  basicControls,
  clearPath,
  readPath,
  writePath,
} from '../src/panels/controls';
import { PANELS, allControls, panelById } from '../src/panels/catalogue';
import { ConfiguratorSession } from '../src/panels/panelState';

const defaults = {
  accent: 'ocean',
  motion: { preset: 'balanced', easing: 'standard' },
  layout: { density: 'comfortable', radius: 8 },
  sections: { override: {} },
};

function session(saved: Record<string, unknown> = {}): ConfiguratorSession {
  return ConfiguratorSession.from({ saved, defaults });
}

function control(id: string) {
  const found = allControls().find((candidate) => candidate.id === id);

  if (found === undefined) {
    throw new Error(`No control ${id}.`);
  }

  return found;
}

describe('the panel catalogue', () => {
  it('ships the nine panels in configurator order', () => {
    expect(PANELS.map((panel) => panel.id)).toEqual([
      'presets',
      'colors',
      'typography',
      'patterns',
      'layout',
      'motion',
      'header',
      'footer',
      'sections',
    ]);
  });

  it('opens every panel with between three and six controls and hides the rest', () => {
    for (const panel of PANELS) {
      const basic = basicControls(panel);

      expect(basic.length, `${panel.id} basic controls`).toBeGreaterThanOrEqual(1);
      expect(basic.length, `${panel.id} basic controls`).toBeLessThanOrEqual(6);
      expect(basic.length + advancedControls(panel).length).toBe(panel.controls.length);
    }
  });

  it('leads every panel with presets', () => {
    for (const panel of PANELS) {
      expect(panel.presets.length, `${panel.id} presets`).toBeGreaterThan(0);
    }
  });

  it('gives every control inline help and a unique id', () => {
    const ids = new Set<string>();

    for (const candidate of allControls()) {
      expect(candidate.help.length, `${candidate.id} help`).toBeGreaterThan(10);
      expect(ids.has(candidate.id), `${candidate.id} is duplicated`).toBe(false);
      ids.add(candidate.id);
    }
  });

  it('renders a live sample, not a swatch, for every picker that has one', () => {
    expect(control('accent').sample).toBe('palette');
    expect(control('pattern').sample).toBe('pattern-tile');
    expect(control('font-pairing').sample).toBe('type-specimen');
    expect(control('motion-preset').sample).toBe('motion-card');
  });

  it('finds a panel by id and reports nothing for an unknown one', () => {
    expect(panelById('motion')?.title).toBe('Motion');
    expect(panelById('nope')).toBeUndefined();
  });
});

describe('reading and writing dotted paths', () => {
  it('reads a nested value and reports absence as undefined', () => {
    expect(readPath(defaults, 'motion.preset')).toBe('balanced');
    expect(readPath(defaults, 'motion.missing')).toBeUndefined();
    expect(readPath(defaults, 'accent.nested')).toBeUndefined();
  });

  it('writes without mutating the original', () => {
    const next = writePath(defaults, 'motion.preset', 'cinematic');

    expect(readPath(next, 'motion.preset')).toBe('cinematic');
    expect(readPath(defaults, 'motion.preset')).toBe('balanced');
  });

  it('creates missing branches on write', () => {
    expect(readPath(writePath({}, 'a.b.c', 1), 'a.b.c')).toBe(1);
  });

  it('removes a key entirely rather than nulling it', () => {
    const cleared = clearPath(defaults, 'motion.preset');

    expect(readPath(cleared, 'motion.preset')).toBeUndefined();
    expect(Object.keys(cleared.motion)).toEqual(['easing']);
  });

  it('leaves a non-object and an empty path alone', () => {
    expect(clearPath('leaf', 'a')).toBe('leaf');
    expect(clearPath(defaults, 'motion.absent')).toEqual(defaults);
    expect(writePath(defaults, '', 1)).toEqual(defaults);
  });
});

describe('a configurator session', () => {
  it('renders a control at its default when nothing is stored', () => {
    const state = session().stateOf(control('motion-preset'));

    expect(state.value).toBe('balanced');
    expect(state.origin).toBe('default');
    expect(state.isModified).toBe(false);
  });

  it('marks a control modified once it differs from the default', () => {
    const state = session().change('motion.preset', 'cinematic').stateOf(control('motion-preset'));

    expect(state.value).toBe('cinematic');
    expect(state.origin).toBe('modified');
    expect(state.isModified).toBe(true);
  });

  it('does not mark a control modified when the stored value equals the default', () => {
    expect(
      session({ motion: { preset: 'balanced' } }).stateOf(control('motion-preset')).isModified
    ).toBe(false);
  });

  it('shows an overridable control as inheriting while it has no stored value', () => {
    const state = session().stateOf(control('section-accent'));

    expect(state.isInherited).toBe(true);
    expect(state.origin).toBe('inherited');
  });

  it('reverts an overridable control back to inheriting rather than to a default', () => {
    const changed = session().change('sections.override.accent', 'plum');

    expect(changed.stateOf(control('section-accent')).isInherited).toBe(false);
    expect(
      changed.revert(control('section-accent')).stateOf(control('section-accent')).isInherited
    ).toBe(true);
  });

  it('reverts a plain control to its shipped default', () => {
    const reverted = session().change('motion.preset', 'none').revert(control('motion-preset'));

    expect(reverted.stateOf(control('motion-preset')).value).toBe('balanced');
    expect(reverted.stateOf(control('motion-preset')).isModified).toBe(false);
  });

  it('reverts a control with no shipped default by removing it', () => {
    const reverted = session()
      .change('presetExportName', 'my look')
      .revert(control('preset-export'));

    expect(readPath(reverted.settings, 'presetExportName')).toBeUndefined();
  });

  it('tracks dirtiness against what is saved, not against the defaults', () => {
    const saved = session({ motion: { preset: 'cinematic' } });

    expect(saved.isDirty).toBe(false);
    expect(saved.change('motion.preset', 'subtle').isDirty).toBe(true);
    expect(saved.change('motion.preset', 'subtle').dirtyPaths).toEqual(['motion']);
  });

  it('clears dirtiness on save and on discard, and keeps the right value each time', () => {
    const edited = session({ motion: { preset: 'balanced' } }).change('motion.preset', 'subtle');

    expect(edited.markSaved().isDirty).toBe(false);
    expect(readPath(edited.markSaved().saved, 'motion.preset')).toBe('subtle');
    expect(edited.discard().isDirty).toBe(false);
    expect(readPath(edited.discard().settings, 'motion.preset')).toBe('balanced');
  });

  it('badges a panel when any of its controls is modified', () => {
    const motion = panelById('motion');

    if (motion === undefined) {
      throw new Error('missing panel');
    }

    expect(session().panelIsModified(motion)).toBe(false);
    expect(session().change('motion.preset', 'none').panelIsModified(motion)).toBe(true);
    expect(session().panelState(motion)).toHaveLength(motion.controls.length);
  });

  it('compares object-valued settings structurally', () => {
    const saved = session({ layout: { density: 'compact' } });

    expect(saved.change('layout', { density: 'compact' }).isDirty).toBe(false);
    expect(saved.change('layout', { density: 'spacious' }).isDirty).toBe(true);
  });
});
