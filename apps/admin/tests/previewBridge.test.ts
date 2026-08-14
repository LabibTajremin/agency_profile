import { describe, expect, it } from 'vitest';

import {
  DEVICE_FRAMES,
  PREVIEW_MESSAGE,
  PreviewBridge,
  applyPreviewCommand,
  diffTokens,
  isEmptyPatch,
  parsePreviewMessage,
  unsavedChangesPrompt,
  type PreviewMessage,
  type StyleTarget,
} from '../src/preview/previewBridge';

function recordingTarget(): {
  readonly target: { postMessage(message: unknown, origin: string): void };
  readonly sent: PreviewMessage[];
} {
  const sent: PreviewMessage[] = [];

  return {
    target: {
      postMessage(message: unknown): void {
        sent.push(message as PreviewMessage);
      },
    },
    sent,
  };
}

function recordingRoot(): {
  readonly root: StyleTarget;
  readonly set: Record<string, string>;
  readonly removed: string[];
  readonly attributes: Record<string, string>;
} {
  const set: Record<string, string> = {};
  const removed: string[] = [];
  const attributes: Record<string, string> = {};

  return {
    set,
    removed,
    attributes,
    root: {
      style: {
        setProperty(name: string, value: string): void {
          set[name] = value;
        },
        removeProperty(name: string): void {
          removed.push(name);
        },
      },
      setAttribute(name: string, value: string): void {
        attributes[name] = value;
      },
    },
  };
}

describe('token diffing', () => {
  it('sends only what changed', () => {
    const patch = diffTokens({ '--a': '1', '--b': '2' }, { '--a': '1', '--b': '3' });

    expect(patch.tokens).toEqual({ '--b': '3' });
    expect(patch.removed).toEqual([]);
  });

  it('reports a token that disappeared so the frame can drop it', () => {
    const patch = diffTokens({ '--a': '1', '--gone': '2' }, { '--a': '1' });

    expect(patch.removed).toEqual(['--gone']);
  });

  it('recognises a no-op patch', () => {
    expect(isEmptyPatch(diffTokens({ '--a': '1' }, { '--a': '1' }))).toBe(true);
    expect(isEmptyPatch(diffTokens({}, { '--a': '1' }))).toBe(false);
  });
});

describe('the preview bridge', () => {
  it('posts a patch on the first push and nothing when nothing changed', () => {
    const { target, sent } = recordingTarget();
    const bridge = new PreviewBridge(target, 'https://example.test');

    expect(bridge.push({ '--accent': 'oklch(0.6 0.15 240)' })).toBe(true);
    expect(bridge.push({ '--accent': 'oklch(0.6 0.15 240)' })).toBe(false);
    expect(sent).toHaveLength(1);
    expect(sent[0]?.channel).toBe(PREVIEW_MESSAGE);
    expect(sent[0]?.command).toEqual({
      type: 'patch',
      tokens: { '--accent': 'oklch(0.6 0.15 240)' },
      removed: [],
    });
  });

  it('sends only the delta on a second push', () => {
    const { target, sent } = recordingTarget();
    const bridge = new PreviewBridge(target, 'https://example.test');

    bridge.push({ '--a': '1', '--b': '2' });
    bridge.push({ '--a': '1', '--b': '9' });

    expect(sent[1]?.command).toEqual({ type: 'patch', tokens: { '--b': '9' }, removed: [] });
  });

  it('returns to the working tokens after a hold-to-compare', () => {
    const { target, sent } = recordingTarget();
    const bridge = new PreviewBridge(target, 'https://example.test');
    const saved = { '--accent': 'blue' };
    const working = { '--accent': 'plum' };

    bridge.push(working);
    bridge.hold(saved);
    bridge.push(working);

    expect(sent).toHaveLength(3);
    expect(sent[2]?.command).toEqual({
      type: 'patch',
      tokens: { '--accent': 'plum' },
      removed: [],
    });
  });

  it('resends everything after the frame reloads', () => {
    const { target, sent } = recordingTarget();
    const bridge = new PreviewBridge(target, 'https://example.test');

    bridge.push({ '--a': '1' });
    bridge.resetTracking();

    expect(bridge.push({ '--a': '1' })).toBe(true);
    expect(sent).toHaveLength(2);
  });

  it('carries the mode and the page switcher over the same channel', () => {
    const { target, sent } = recordingTarget();
    const bridge = new PreviewBridge(target, 'https://example.test');

    bridge.setMode('dark');
    bridge.navigate('/courses/');

    expect(sent[0]?.command).toEqual({ type: 'mode', mode: 'dark' });
    expect(sent[1]?.command).toEqual({ type: 'navigate', url: '/courses/' });
  });

  it('offers a mobile, tablet and desktop frame', () => {
    expect(DEVICE_FRAMES.map((frame) => frame.device)).toEqual(['mobile', 'tablet', 'desktop']);
  });
});

describe('receiving a preview message', () => {
  const message: PreviewMessage = {
    channel: PREVIEW_MESSAGE,
    command: { type: 'patch', tokens: { '--a': '1' }, removed: ['--b'] },
  };

  it('accepts a well-formed message from the expected origin', () => {
    expect(parsePreviewMessage(message, 'https://a.test', 'https://a.test')).toEqual(
      message.command
    );
  });

  it('rejects a message from anywhere else', () => {
    expect(parsePreviewMessage(message, 'https://evil.test', 'https://a.test')).toBeNull();
  });

  it('rejects anything that is not one of the known commands', () => {
    expect(
      parsePreviewMessage({ channel: PREVIEW_MESSAGE, command: { type: 'eval' } }, 'a', 'a')
    ).toBeNull();
    expect(
      parsePreviewMessage({ channel: 'other', command: message.command }, 'a', 'a')
    ).toBeNull();
    expect(parsePreviewMessage({ channel: PREVIEW_MESSAGE, command: null }, 'a', 'a')).toBeNull();
    expect(parsePreviewMessage('a string', 'a', 'a')).toBeNull();
    expect(parsePreviewMessage(null, 'a', 'a')).toBeNull();
  });
});

describe('applying a preview command', () => {
  it('sets and removes custom properties without reloading', () => {
    const { root, set, removed } = recordingRoot();

    expect(
      applyPreviewCommand(root, { type: 'patch', tokens: { '--a': '1' }, removed: ['--b'] })
    ).toBeNull();
    expect(set).toEqual({ '--a': '1' });
    expect(removed).toEqual(['--b']);
  });

  it('switches the mode through the data attribute the theme reads', () => {
    const { root, attributes } = recordingRoot();

    applyPreviewCommand(root, { type: 'mode', mode: 'dark' });

    expect(attributes['data-edulume-mode']).toBe('dark');
  });

  it('hands navigation back to the caller instead of touching location itself', () => {
    const { root } = recordingRoot();

    expect(applyPreviewCommand(root, { type: 'navigate', url: '/about/' })).toBe('/about/');
  });
});

describe('the unsaved-changes guard', () => {
  it('says nothing when nothing is dirty', () => {
    expect(unsavedChangesPrompt([])).toBeNull();
  });

  it('names the panels that would lose changes', () => {
    expect(unsavedChangesPrompt(['Colors', 'Motion'])).toBe(
      'You have unsaved changes in Colors, Motion. Leaving now discards them.'
    );
  });
});
