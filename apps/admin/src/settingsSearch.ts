/**
 * The Cmd-K / Ctrl-K settings search.
 *
 * Every control registers itself here, so the search reaches things a person could otherwise
 * only find by opening eight panels and expanding Advanced on three of them. A configurator
 * with sixty settings and no search is a configurator where half the settings are never found.
 */

export interface RegisteredControl {
  /** Stable id, used to focus and highlight the control once the panel opens. */
  readonly id: string;
  readonly label: string;
  readonly panel: string;
  /** Words a person might search for that do not appear in the label. */
  readonly keywords?: readonly string[];
  /** True when the control lives behind the panel's Advanced disclosure. */
  readonly isAdvanced?: boolean;
}

export interface SearchHit {
  readonly control: RegisteredControl;
  readonly score: number;
}

const EXACT_LABEL_SCORE = 100;
const LABEL_PREFIX_SCORE = 80;
const LABEL_CONTAINS_SCORE = 60;
const KEYWORD_SCORE = 40;
const PANEL_SCORE = 20;

const DEFAULT_LIMIT = 10;

function normalise(value: string): string {
  return value.trim().toLowerCase();
}

function scoreOf(control: RegisteredControl, query: string): number {
  const label = normalise(control.label);

  if (label === query) {
    return EXACT_LABEL_SCORE;
  }

  if (label.startsWith(query)) {
    return LABEL_PREFIX_SCORE;
  }

  if (label.includes(query)) {
    return LABEL_CONTAINS_SCORE;
  }

  if ((control.keywords ?? []).some((keyword) => normalise(keyword).includes(query))) {
    return KEYWORD_SCORE;
  }

  if (normalise(control.panel).includes(query)) {
    return PANEL_SCORE;
  }

  return 0;
}

/**
 * An index of every registered control.
 *
 * Registration is idempotent by id: a panel that mounts, unmounts and mounts again must not
 * put three copies of every control into the results.
 */
export class SettingsSearchIndex {
  private readonly controls = new Map<string, RegisteredControl>();

  register(...controls: readonly RegisteredControl[]): this {
    controls.forEach((control) => this.controls.set(control.id, control));

    return this;
  }

  unregister(id: string): this {
    this.controls.delete(id);

    return this;
  }

  has(id: string): boolean {
    return this.controls.has(id);
  }

  get size(): number {
    return this.controls.size;
  }

  all(): readonly RegisteredControl[] {
    return [...this.controls.values()];
  }

  /**
   * Ranked matches. Ties break on label so the order is stable between renders — a result
   * list that reshuffles under the cursor is a result list people click the wrong row in.
   */
  search(rawQuery: string, limit: number = DEFAULT_LIMIT): readonly SearchHit[] {
    const query = normalise(rawQuery);

    if (query === '') {
      return [];
    }

    return this.all()
      .map((control) => ({ control, score: scoreOf(control, query) }))
      .filter((hit) => hit.score > 0)
      .sort((first, second) =>
        second.score === first.score
          ? first.control.label.localeCompare(second.control.label)
          : second.score - first.score
      )
      .slice(0, Math.max(0, limit));
  }

  /**
   * Where a hit sends the admin: the panel to open, the control to focus, and whether the
   * Advanced disclosure has to be expanded first.
   */
  destinationFor(id: string): { panel: string; controlId: string; expandAdvanced: boolean } | null {
    const control = this.controls.get(id);

    if (control === undefined) {
      return null;
    }

    return {
      panel: control.panel,
      controlId: control.id,
      expandAdvanced: control.isAdvanced === true,
    };
  }
}
