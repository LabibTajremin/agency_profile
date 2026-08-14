<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Domain\Licence\LicenceResponse;

/**
 * The licence and update endpoint.
 *
 * A port, so the whole activation and update flow is testable without a server — and so a site
 * that cannot reach the server has one well-defined failure to handle rather than a network
 * exception surfacing from wherever it happened to be called.
 */
interface LicenceServer
{
    public function activate(string $key, string $siteUrl): LicenceResponse;

    public function deactivate(string $key, string $siteUrl): LicenceResponse;

    public function check(string $key, string $siteUrl): LicenceResponse;

    /**
     * The latest available version and where to get it, or null when the server said nothing
     * useful.
     *
     * @return array{version: string, package: string, changelog: string}|null
     */
    public function latestRelease(string $key, string $siteUrl): ?array;
}
