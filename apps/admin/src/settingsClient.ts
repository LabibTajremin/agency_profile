import { restPath } from './restPath';

/**
 * The one place the admin talks to the REST API.
 *
 * The transport is injected rather than reaching for global `fetch` so the round-trip is
 * provable in a test: "every control round-trips through the REST API" is only worth claiming
 * if something actually asserts the request body and the response mapping.
 */

export interface RestRequest {
  readonly path: string;
  readonly method: 'GET' | 'POST';
  readonly nonce: string;
  readonly body?: unknown;
}

export interface RestResponse {
  readonly status: number;
  readonly json: unknown;
}

export type RestTransport = (request: RestRequest) => Promise<RestResponse>;

export class RestError extends Error {
  constructor(
    readonly status: number,
    message: string
  ) {
    super(message);
    this.name = 'RestError';
  }
}

function messageOf(payload: unknown, status: number): string {
  if (
    payload !== null &&
    typeof payload === 'object' &&
    typeof (payload as { message?: unknown }).message === 'string'
  ) {
    return (payload as { message: string }).message;
  }

  return `Request failed with status ${status}.`;
}

function asRecord(payload: unknown, path: string): Record<string, unknown> {
  if (payload === null || typeof payload !== 'object' || Array.isArray(payload)) {
    throw new RestError(200, `Expected an object from ${path}.`);
  }

  return payload as Record<string, unknown>;
}

export class SettingsClient {
  constructor(
    private readonly transport: RestTransport,
    private readonly nonce: string
  ) {}

  async load(): Promise<Record<string, unknown>> {
    return asRecord(await this.send('settings', 'GET'), 'settings');
  }

  async defaults(): Promise<Record<string, unknown>> {
    return asRecord(await this.send('settings/defaults', 'GET'), 'settings/defaults');
  }

  /**
   * Saves the whole settings object, not a patch.
   *
   * A patch endpoint would need the server to merge, and a merge that has to decide between
   * "absent because unchanged" and "absent because reverted to inherit" cannot be got right.
   * Sending the whole object makes absence unambiguous.
   */
  async save(settings: Record<string, unknown>): Promise<Record<string, unknown>> {
    return asRecord(await this.send('settings', 'POST', settings), 'settings');
  }

  async previewPreset(id: string): Promise<Record<string, unknown>> {
    return asRecord(await this.send('presets/preview', 'POST', { preset: id }), 'presets/preview');
  }

  async applyPreset(id: string): Promise<Record<string, unknown>> {
    return asRecord(await this.send('presets/apply', 'POST', { preset: id }), 'presets/apply');
  }

  private async send(route: string, method: 'GET' | 'POST', body?: unknown): Promise<unknown> {
    const response = await this.transport({
      path: restPath(route),
      method,
      nonce: this.nonce,
      ...(body === undefined ? {} : { body }),
    });

    if (response.status < 200 || response.status >= 300) {
      throw new RestError(response.status, messageOf(response.json, response.status));
    }

    return response.json;
  }
}
