import { describe, expect, it } from 'vitest';

import {
  RestError,
  SettingsClient,
  type RestRequest,
  type RestResponse,
} from '../src/settingsClient';

function recordingTransport(response: RestResponse): {
  readonly client: SettingsClient;
  readonly requests: RestRequest[];
} {
  const requests: RestRequest[] = [];

  const client = new SettingsClient(async (request) => {
    requests.push(request);

    return response;
  }, 'nonce-123');

  return { client, requests };
}

describe('the settings REST client', () => {
  it('round-trips a load through the namespaced route with the nonce attached', async () => {
    const { client, requests } = recordingTransport({ status: 200, json: { accent: 'ocean' } });

    await expect(client.load()).resolves.toEqual({ accent: 'ocean' });
    expect(requests[0]).toEqual({ path: 'edulume/v1/settings', method: 'GET', nonce: 'nonce-123' });
  });

  it('sends the whole settings object on save rather than a patch', async () => {
    const { client, requests } = recordingTransport({ status: 200, json: { accent: 'plum' } });

    await client.save({ accent: 'plum', motion: { preset: 'none' } });

    expect(requests[0]?.method).toBe('POST');
    expect(requests[0]?.body).toEqual({ accent: 'plum', motion: { preset: 'none' } });
  });

  it('loads the defaults from their own route', async () => {
    const { client, requests } = recordingTransport({ status: 200, json: {} });

    await client.defaults();

    expect(requests[0]?.path).toBe('edulume/v1/settings/defaults');
  });

  it('previews a preset without applying it', async () => {
    const { client, requests } = recordingTransport({ status: 200, json: { accent: 'ember' } });

    await client.previewPreset('gulf-premium');

    expect(requests[0]?.path).toBe('edulume/v1/presets/preview');
    expect(requests[0]?.body).toEqual({ preset: 'gulf-premium' });
  });

  it('applies a preset through the apply route', async () => {
    const { client, requests } = recordingTransport({ status: 200, json: {} });

    await client.applyPreset('nordic-calm');

    expect(requests[0]?.path).toBe('edulume/v1/presets/apply');
  });

  it('raises the server message on a failure status', async () => {
    const { client } = recordingTransport({
      status: 403,
      json: { message: 'Sorry, you are not allowed to do that.' },
    });

    await expect(client.load()).rejects.toThrow(
      new RestError(403, 'Sorry, you are not allowed to do that.')
    );
  });

  it('falls back to a status message when the body carries none', async () => {
    const { client } = recordingTransport({ status: 500, json: null });

    await expect(client.load()).rejects.toThrow('Request failed with status 500.');
  });

  it('refuses a success response that is not an object', async () => {
    const { client } = recordingTransport({ status: 200, json: ['unexpected'] });

    await expect(client.load()).rejects.toThrow('Expected an object from settings.');
  });
});
