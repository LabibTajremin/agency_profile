<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Licence;

use DateTimeImmutable;
use Edulume\Core\Application\Port\LicenceServer;
use Edulume\Core\Domain\Licence\LicenceResponse;

/**
 * Talks to the licence and update endpoint over HTTP.
 *
 * Every failure — a network error, a non-2xx status, an unparseable body — becomes
 * `LicenceResponse::unreachable()` rather than an exception or an "invalid" verdict. The
 * distinction matters more here than almost anywhere else in the product: an invalid verdict
 * overwrites what the site believes, so a vendor outage misreported as invalid would take
 * updates away from every customer at once.
 */
final class HttpLicenceServer implements LicenceServer
{
    private const TIMEOUT_SECONDS = 10;

    public function __construct(private readonly string $endpoint)
    {
    }

    public function activate(string $key, string $siteUrl): LicenceResponse
    {
        return $this->call('activate', $key, $siteUrl);
    }

    public function deactivate(string $key, string $siteUrl): LicenceResponse
    {
        return $this->call('deactivate', $key, $siteUrl);
    }

    public function check(string $key, string $siteUrl): LicenceResponse
    {
        return $this->call('check', $key, $siteUrl);
    }

    public function latestRelease(string $key, string $siteUrl): ?array
    {
        $payload = $this->request('release', $key, $siteUrl);

        if ($payload === null) {
            return null;
        }

        $version = is_string($payload['version'] ?? null) ? $payload['version'] : '';
        $package = is_string($payload['package'] ?? null) ? $payload['package'] : '';

        // A release with no downloadable package is not a release. Offering it would produce an
        // update button that fails halfway through, which is worse than offering nothing.
        if ($version === '' || $package === '') {
            return null;
        }

        return [
            'version' => $version,
            'package' => $package,
            'changelog' => is_string($payload['changelog'] ?? null) ? $payload['changelog'] : '',
        ];
    }

    private function call(string $action, string $key, string $siteUrl): LicenceResponse
    {
        $payload = $this->request($action, $key, $siteUrl);

        if ($payload === null) {
            return LicenceResponse::unreachable();
        }

        $isValid = ($payload['valid'] ?? false) === true;
        $message = is_string($payload['message'] ?? null) ? $payload['message'] : '';

        if (!$isValid) {
            return LicenceResponse::invalid($message === '' ? 'That licence key was not accepted.' : $message);
        }

        return new LicenceResponse(
            true,
            true,
            $this->dateOf($payload['expires_at'] ?? null),
            is_numeric($payload['site_limit'] ?? null) ? (int) $payload['site_limit'] : 1,
            is_numeric($payload['activation_count'] ?? null) ? (int) $payload['activation_count'] : 0,
            ($payload['revoked'] ?? false) === true,
            $message,
        );
    }

    /**
     * @return array<string, mixed>|null null whenever the server could not be reached or
     *                                   answered with something this cannot read
     */
    private function request(string $action, string $key, string $siteUrl): ?array
    {
        $response = wp_remote_post($this->endpoint . '/' . $action, [
            'timeout' => self::TIMEOUT_SECONDS,
            'body' => ['key' => $key, 'site' => $siteUrl],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $status = (int) wp_remote_retrieve_response_code($response);

        if ($status < 200 || $status >= 300) {
            return null;
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function dateOf(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            // A malformed expiry reads as "no expiry" rather than as "expired". Failing the
            // other way would disable updates on a server-side formatting mistake.
            return null;
        }
    }
}
