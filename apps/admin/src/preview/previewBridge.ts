/**
 * The live preview: token changes applied inside an iframe as CSS variable patches.
 *
 * The reason this is a patch protocol and not a reload is the 300 ms budget. A reload of a real
 * WordPress page is hundreds of milliseconds before it has parsed anything, and it throws away
 * scroll position and any open menu — so the person loses their place every time they nudge a
 * slider. Setting custom properties on the document element re-styles the whole page in one
 * style recalculation instead.
 */

export type PreviewDevice = 'mobile' | 'tablet' | 'desktop';

export interface DeviceFrame {
  readonly device: PreviewDevice;
  readonly width: number;
  readonly label: string;
}

/**
 * Widths chosen to sit just inside common breakpoints rather than on them: a preview that lands
 * exactly on a breakpoint shows whichever side of it the CSS happens to favour.
 */
export const DEVICE_FRAMES: readonly DeviceFrame[] = [
  { device: 'mobile', width: 390, label: 'Mobile' },
  { device: 'tablet', width: 834, label: 'Tablet' },
  { device: 'desktop', width: 1440, label: 'Desktop' },
];

export const PREVIEW_MESSAGE = 'edulume/preview';

/**
 * The attribute the compiled stylesheet keys its dark block on.
 *
 * The same string as `CompileStylesheet::DARK_MODE_ATTRIBUTE` and the theme's no-flash script.
 * The preview used to set its own name, which meant the mode switch toggled an attribute
 * nothing read — the preview would simply not change mode, silently.
 */
export const MODE_ATTRIBUTE = 'data-theme';

export type PreviewCommand =
  | {
      readonly type: 'patch';
      readonly tokens: Readonly<Record<string, string>>;
      readonly removed: readonly string[];
    }
  | { readonly type: 'mode'; readonly mode: 'light' | 'dark' }
  | { readonly type: 'navigate'; readonly url: string };

export interface PreviewMessage {
  readonly channel: typeof PREVIEW_MESSAGE;
  readonly command: PreviewCommand;
}

/** The narrow slice of `window` the bridge needs, so a test can supply a plain object. */
export interface MessageTarget {
  postMessage(message: unknown, targetOrigin: string): void;
}

/**
 * Computes the smallest patch that turns `previous` into `next`.
 *
 * Sending only what changed is what keeps the update under budget when a preset touches four
 * tokens out of two hundred, and it is what makes hold-to-compare instant: the reverse patch is
 * the same computation with the arguments swapped.
 */
export function diffTokens(
  previous: Readonly<Record<string, string>>,
  next: Readonly<Record<string, string>>
): { readonly tokens: Record<string, string>; readonly removed: string[] } {
  const tokens: Record<string, string> = {};
  const removed: string[] = [];

  for (const [name, value] of Object.entries(next)) {
    if (previous[name] !== value) {
      tokens[name] = value;
    }
  }

  for (const name of Object.keys(previous)) {
    if (!(name in next)) {
      removed.push(name);
    }
  }

  return { tokens, removed };
}

export function isEmptyPatch(patch: {
  readonly tokens: Record<string, string>;
  readonly removed: string[];
}): boolean {
  return Object.keys(patch.tokens).length === 0 && patch.removed.length === 0;
}

/**
 * Sits on the admin side of the iframe boundary and holds the last tokens it sent, so callers
 * can hand it whole token sets and still get minimal messages on the wire.
 */
export class PreviewBridge {
  private lastSent: Record<string, string> = {};

  constructor(
    private readonly target: MessageTarget,
    private readonly origin: string
  ) {}

  /** Returns true when a message was actually posted. An unchanged token set posts nothing. */
  push(tokens: Readonly<Record<string, string>>): boolean {
    const patch = diffTokens(this.lastSent, tokens);

    if (isEmptyPatch(patch)) {
      return false;
    }

    this.lastSent = { ...tokens };
    this.send({ type: 'patch', tokens: patch.tokens, removed: patch.removed });

    return true;
  }

  /**
   * Hold-to-compare: show the saved state without losing the working one.
   *
   * `push` already tracks what the frame is showing, so releasing is just pushing the working
   * tokens back — no separate "restore" path to get out of step.
   */
  hold(saved: Readonly<Record<string, string>>): void {
    this.push(saved);
  }

  setMode(mode: 'light' | 'dark'): void {
    this.send({ type: 'mode', mode });
  }

  navigate(url: string): void {
    this.send({ type: 'navigate', url });
  }

  /** Forces the next `push` to send everything — used after the iframe reloads. */
  resetTracking(): void {
    this.lastSent = {};
  }

  private send(command: PreviewCommand): void {
    const message: PreviewMessage = { channel: PREVIEW_MESSAGE, command };

    this.target.postMessage(message, this.origin);
  }
}

/** The narrow slice of `documentElement` the receiver needs. */
export interface StyleTarget {
  readonly style: {
    setProperty(name: string, value: string): void;
    removeProperty(name: string): void;
  };
  setAttribute(name: string, value: string): void;
}

/**
 * Rejects anything that is not a preview message from the expected origin.
 *
 * An iframe listens to every `message` event the page receives, including ones from advertising
 * scripts and browser extensions, so an unguarded listener is an injection point for arbitrary
 * CSS values.
 */
export function parsePreviewMessage(
  data: unknown,
  origin: string,
  expectedOrigin: string
): PreviewCommand | null {
  if (origin !== expectedOrigin || data === null || typeof data !== 'object') {
    return null;
  }

  const message = data as { channel?: unknown; command?: unknown };

  if (
    message.channel !== PREVIEW_MESSAGE ||
    message.command === null ||
    typeof message.command !== 'object'
  ) {
    return null;
  }

  const command = message.command as { type?: unknown };

  if (command.type === 'patch' || command.type === 'mode' || command.type === 'navigate') {
    return message.command as PreviewCommand;
  }

  return null;
}

/**
 * Applies a command inside the previewed page.
 *
 * Returns the URL to navigate to, if the command was a navigation — the caller owns the
 * location, so this stays free of side effects a test cannot observe.
 */
export function applyPreviewCommand(root: StyleTarget, command: PreviewCommand): string | null {
  if (command.type === 'patch') {
    for (const [name, value] of Object.entries(command.tokens)) {
      root.style.setProperty(name, value);
    }

    for (const name of command.removed) {
      root.style.removeProperty(name);
    }

    return null;
  }

  if (command.type === 'mode') {
    root.setAttribute(MODE_ATTRIBUTE, command.mode);

    return null;
  }

  return command.url;
}

/** The message the unsaved-changes guard shows. Listing the panels beats "you have changes". */
export function unsavedChangesPrompt(dirtyPanels: readonly string[]): string | null {
  if (dirtyPanels.length === 0) {
    return null;
  }

  const panels = dirtyPanels.join(', ');

  return `You have unsaved changes in ${panels}. Leaving now discards them.`;
}
