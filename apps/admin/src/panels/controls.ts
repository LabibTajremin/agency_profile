/**
 * What a configurator control *is*, independent of how it is drawn.
 *
 * The panels carry sixty-odd controls. Describing them as data rather than as sixty hand-written
 * components is what makes the search index, the modified dot, the revert button, the Advanced
 * disclosure and the REST round-trip work the same way everywhere instead of nine times slightly
 * differently.
 */

/**
 * The kind of live sample a control renders beside itself.
 *
 * A swatch tells you a colour is teal. A sample tells you what a button, a heading and a link
 * look like in teal, which is the question the person is actually asking.
 */
export type SampleKind =
  | 'none'
  /** Miniature button + heading + link + card in the candidate palette. */
  | 'palette'
  /** One tile of the pattern at the current accent and opacity. */
  | 'pattern-tile'
  /** A real sentence at the real family, size and weight. */
  | 'type-specimen'
  /** A card that replays the motion preset on demand. */
  | 'motion-card'
  /** A scaled outline of the header or footer arrangement. */
  | 'chrome-outline';

export type ControlKind =
  | 'accent'
  | 'color'
  | 'select'
  | 'toggle'
  | 'range'
  | 'text'
  | 'number'
  | 'font'
  | 'pattern'
  | 'preset'
  | 'bezier';

export interface ControlDefinition {
  /** Stable id. Also the anchor the settings search scrolls to. */
  readonly id: string;
  readonly label: string;
  readonly kind: ControlKind;
  /** Dotted path into the settings object, e.g. `motion.preset`. */
  readonly path: string;
  /** One sentence shown inline, not in a tooltip — tooltips are unreachable by touch. */
  readonly help: string;
  readonly sample?: SampleKind;
  /** True when the control lives behind the panel's Advanced disclosure. */
  readonly isAdvanced?: boolean;
  /** Words a person might search for that do not appear in the label. */
  readonly keywords?: readonly string[];
  /** Present when the control accepts a per-section override. */
  readonly overridable?: boolean;
}

export interface PanelDefinition {
  readonly id: string;
  readonly title: string;
  /** Shown under the title. Says what the panel is for, not what it contains. */
  readonly summary: string;
  /**
   * The presets row every panel leads with. A person who wants "make it look professional"
   * should never have to reach a slider to get there.
   */
  readonly presets: readonly string[];
  readonly controls: readonly ControlDefinition[];
}

/** The controls a panel shows before the Advanced disclosure is opened. */
export function basicControls(panel: PanelDefinition): readonly ControlDefinition[] {
  return panel.controls.filter((control) => control.isAdvanced !== true);
}

export function advancedControls(panel: PanelDefinition): readonly ControlDefinition[] {
  return panel.controls.filter((control) => control.isAdvanced === true);
}

/**
 * Reads a dotted path out of a settings object.
 *
 * Returns `undefined` for a missing leaf, which is the same thing the override model means by
 * "inherited": absence, not a sentinel value.
 */
export function readPath(settings: unknown, path: string): unknown {
  return path.split('.').reduce<unknown>((carry, segment) => {
    if (carry === null || typeof carry !== 'object') {
      return undefined;
    }

    return (carry as Record<string, unknown>)[segment];
  }, settings);
}

/**
 * Returns a copy of `settings` with `path` set to `value`.
 *
 * Copies rather than mutates so the live preview can hold the pre-change state for
 * hold-to-compare without having to clone defensively at every call site.
 */
export function writePath<T>(settings: T, path: string, value: unknown): T {
  const [head, ...rest] = path.split('.');

  // An empty path addresses nothing. `''.split('.')` yields `['']`, so this needs saying
  // explicitly or the write lands under a blank key.
  if (head === undefined || head === '') {
    return settings;
  }

  const base: Record<string, unknown> =
    settings !== null && typeof settings === 'object'
      ? { ...(settings as Record<string, unknown>) }
      : {};

  if (rest.length === 0) {
    base[head] = value;

    return base as T;
  }

  base[head] = writePath(base[head] ?? {}, rest.join('.'), value);

  return base as T;
}

/**
 * Returns a copy of `settings` with `path` removed entirely.
 *
 * Deleting rather than nulling is what makes an override inherit again: the section resolver
 * treats an absent key as "ask the global setting", and a stored `null` would be a value.
 */
export function clearPath<T>(settings: T, path: string): T {
  const [head, ...rest] = path.split('.');

  if (head === undefined || head === '' || settings === null || typeof settings !== 'object') {
    return settings;
  }

  const base: Record<string, unknown> = { ...(settings as Record<string, unknown>) };

  if (rest.length === 0) {
    delete base[head];

    return base as T;
  }

  if (base[head] !== undefined) {
    base[head] = clearPath(base[head], rest.join('.'));
  }

  return base as T;
}
