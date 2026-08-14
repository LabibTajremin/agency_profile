<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\LicenceServer;
use Edulume\Core\Domain\Licence\LicenceResponse;

/**
 * A licence server whose answers the test states outright.
 *
 * Records what it was asked, because "did deactivating actually tell the server" is exactly the
 * kind of thing that silently stops working and leaves customers unable to move a licence.
 */
final class StubLicenceServer implements LicenceServer
{
    /** @var list<array{method: string, key: string, site: string}> */
    public array $calls = [];

    /** @var array{version: string, package: string, changelog: string}|null */
    public ?array $release = null;

    public function __construct(private LicenceResponse $response = new LicenceResponse(true, true))
    {
    }

    public function willAnswer(LicenceResponse $response): void
    {
        $this->response = $response;
    }

    public function activate(string $key, string $siteUrl): LicenceResponse
    {
        $this->calls[] = ['method' => 'activate', 'key' => $key, 'site' => $siteUrl];

        return $this->response;
    }

    public function deactivate(string $key, string $siteUrl): LicenceResponse
    {
        $this->calls[] = ['method' => 'deactivate', 'key' => $key, 'site' => $siteUrl];

        return $this->response;
    }

    public function check(string $key, string $siteUrl): LicenceResponse
    {
        $this->calls[] = ['method' => 'check', 'key' => $key, 'site' => $siteUrl];

        return $this->response;
    }

    public function latestRelease(string $key, string $siteUrl): ?array
    {
        $this->calls[] = ['method' => 'latestRelease', 'key' => $key, 'site' => $siteUrl];

        return $this->release;
    }

    /**
     * @return list<string>
     */
    public function methodsCalled(): array
    {
        return array_map(static fn (array $call): string => $call['method'], $this->calls);
    }
}
