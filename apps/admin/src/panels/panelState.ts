import type { ControlDefinition, PanelDefinition } from './controls';
import { clearPath, readPath, writePath } from './controls';

/**
 * The behaviour behind every control: what it currently shows, whether it has been modified,
 * whether it is inheriting, and what reverting it does.
 *
 * Kept separate from rendering on purpose. "Render, change and reset" is the acceptance
 * criterion for all nine panels, and the part worth proving is the state transition, not that
 * a `<select>` appeared.
 */

export type ControlOrigin =
  /** No value stored anywhere: the shipped default is in effect. */
  | 'default'
  /** A value is stored, and it differs from the default. */
  | 'modified'
  /** Nothing stored on this section, so the global value is in effect. */
  | 'inherited';

export interface ControlState {
  readonly control: ControlDefinition;
  /** What the control should display right now, defaults folded in. */
  readonly value: unknown;
  readonly origin: ControlOrigin;
  /** Drives the modified dot and enables the one-click revert. */
  readonly isModified: boolean;
  /** True when the control is overridable and currently following its parent. */
  readonly isInherited: boolean;
}

export interface SettingsSnapshot {
  /** What is stored right now, before any unsaved edit. */
  readonly saved: Record<string, unknown>;
  /** The shipped defaults, used to decide whether a stored value counts as modified. */
  readonly defaults: Record<string, unknown>;
}

function sameValue(left: unknown, right: unknown): boolean {
  if (left === right) {
    return true;
  }

  if (left === null || right === null || typeof left !== 'object' || typeof right !== 'object') {
    return false;
  }

  // Both sides come out of JSON, so a structural comparison by serialisation is exact here and
  // avoids hand-rolling a deep-equal that would need maintaining alongside the settings schema.
  return JSON.stringify(left) === JSON.stringify(right);
}

/**
 * A panel's working copy: the saved settings plus whatever the person has changed since.
 *
 * Immutable — every operation returns a new session. That is what makes hold-to-compare and
 * undo cheap: both are just holding on to an older session.
 */
export class ConfiguratorSession {
  private constructor(
    private readonly defaults: Record<string, unknown>,
    private readonly savedSettings: Record<string, unknown>,
    private readonly workingSettings: Record<string, unknown>
  ) {}

  static from(snapshot: SettingsSnapshot): ConfiguratorSession {
    return new ConfiguratorSession(snapshot.defaults, snapshot.saved, snapshot.saved);
  }

  /** What the preview should render. */
  get settings(): Record<string, unknown> {
    return this.workingSettings;
  }

  /** What the preview renders while hold-to-compare is held down. */
  get saved(): Record<string, unknown> {
    return this.savedSettings;
  }

  /** True when leaving the screen would lose something. */
  get isDirty(): boolean {
    return !sameValue(this.savedSettings, this.workingSettings);
  }

  /** The paths that differ from what is stored — what the unsaved-changes guard lists. */
  get dirtyPaths(): readonly string[] {
    return this.controlPaths().filter(
      (path) => !sameValue(readPath(this.savedSettings, path), readPath(this.workingSettings, path))
    );
  }

  change(path: string, value: unknown): ConfiguratorSession {
    return new ConfiguratorSession(
      this.defaults,
      this.savedSettings,
      writePath(this.workingSettings, path, value)
    );
  }

  /**
   * One-click revert.
   *
   * An overridable control reverts to *absence*, which is what makes it inherit again. A plain
   * control reverts to the shipped default. Reverting is not the same as undo: it takes one
   * control back, it does not walk history.
   */
  revert(control: ControlDefinition): ConfiguratorSession {
    if (control.overridable === true) {
      return new ConfiguratorSession(
        this.defaults,
        this.savedSettings,
        clearPath(this.workingSettings, control.path)
      );
    }

    const fallback = readPath(this.defaults, control.path);

    if (fallback === undefined) {
      return new ConfiguratorSession(
        this.defaults,
        this.savedSettings,
        clearPath(this.workingSettings, control.path)
      );
    }

    return this.change(control.path, fallback);
  }

  /** Marks the working copy as saved. Called after the REST round-trip succeeds. */
  markSaved(): ConfiguratorSession {
    return new ConfiguratorSession(this.defaults, this.workingSettings, this.workingSettings);
  }

  /** Throws away unsaved edits — the "discard" branch of the unsaved-changes guard. */
  discard(): ConfiguratorSession {
    return new ConfiguratorSession(this.defaults, this.savedSettings, this.savedSettings);
  }

  stateOf(control: ControlDefinition): ControlState {
    const stored = readPath(this.workingSettings, control.path);
    const fallback = readPath(this.defaults, control.path);
    const isAbsent = stored === undefined;
    const isInherited = isAbsent && control.overridable === true;

    return {
      control,
      value: isAbsent ? fallback : stored,
      origin: isInherited
        ? 'inherited'
        : isAbsent || sameValue(stored, fallback)
          ? 'default'
          : 'modified',
      isModified: !isAbsent && !sameValue(stored, fallback),
      isInherited,
    };
  }

  /** Every control in a panel, ready to render. */
  panelState(panel: PanelDefinition): readonly ControlState[] {
    return panel.controls.map((control) => this.stateOf(control));
  }

  /** True when any control in the panel carries a modified dot — drives the sidebar badge. */
  panelIsModified(panel: PanelDefinition): boolean {
    return this.panelState(panel).some((state) => state.isModified);
  }

  private controlPaths(): readonly string[] {
    const paths = new Set<string>();

    for (const key of Object.keys({ ...this.savedSettings, ...this.workingSettings })) {
      paths.add(key);
    }

    return [...paths];
  }
}
