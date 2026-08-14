<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Licence;

use DateTimeImmutable;
use Edulume\Core\Application\Port\Clock;
use Edulume\Core\Application\Port\LicenceServer;
use Edulume\Core\Domain\Licence\Licence;
use Edulume\Core\Domain\Licence\LicenceResponse;
use Edulume\Core\Domain\Licence\LicenceStatus;

/**
 * Activation, deactivation, the periodic check, and the update decision.
 *
 * The rule the whole class is arranged around: nothing here can stop the product working. The
 * only thing a licence gates is whether an update is offered.
 */
final class ManageLicence
{
    public function __construct(
        private readonly LicenceServer $server,
        private readonly Clock $clock,
    ) {
    }

    /**
     * The clock's moment as a date.
     *
     * Built from the timestamp rather than from `now()`, whose string format belongs to
     * whichever adapter produced it; a Unix timestamp has exactly one reading and no time zone
     * to get wrong.
     */
    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . $this->clock->timestamp());
    }

    public function activate(string $key, string $siteUrl): Licence
    {
        $trimmed = trim($key);

        if ($trimmed === '') {
            return Licence::none();
        }

        $response = $this->server->activate($trimmed, $siteUrl);
        $now = $this->now();

        if (!$response->reachable) {
            // Stored optimistically and re-checked tomorrow. Refusing to store a key because
            // the vendor's server was down for ten minutes makes the first thing a customer
            // does after paying fail.
            return new Licence($trimmed, lastCheckedAt: null);
        }

        return $this->merge($trimmed, $response, $now, $response->isValid);
    }

    public function deactivate(Licence $licence, string $siteUrl): Licence
    {
        if (!$licence->hasKey()) {
            return Licence::none();
        }

        $this->server->deactivate($licence->key, $siteUrl);

        // Cleared locally regardless of what the server said: a site being decommissioned must
        // be able to release its key even if the vendor is unreachable, or the seat is lost.
        return Licence::none();
    }

    /**
     * The daily re-check. Returns the licence unchanged when nothing needs doing.
     */
    public function refresh(Licence $licence, string $siteUrl): Licence
    {
        $now = $this->now();

        if (!$licence->hasKey() || !$licence->isDueForCheck($now)) {
            return $licence;
        }

        $response = $this->server->check($licence->key, $siteUrl);

        if (!$response->shouldReplaceStoredLicence()) {
            // Not even the check timestamp is advanced, so the next request tries again rather
            // than waiting another day after a transient failure.
            return $licence;
        }

        return $this->merge($licence->key, $response, $now, $licence->isActivatedHere);
    }

    public function statusOf(Licence $licence): LicenceStatus
    {
        return $licence->statusAt($this->now());
    }

    /**
     * The update the dashboard should offer, if any.
     *
     * @return array{version: string, package: string, changelog: string}|null
     */
    public function availableUpdate(Licence $licence, string $currentVersion, string $siteUrl): ?array
    {
        if (!$this->statusOf($licence)->allowsUpdates()) {
            return null;
        }

        $release = $this->server->latestRelease($licence->key, $siteUrl);

        if ($release === null || version_compare($release['version'], $currentVersion, '<=')) {
            return null;
        }

        return $release;
    }

    private function merge(
        string $key,
        LicenceResponse $response,
        DateTimeImmutable $now,
        bool $isActivatedHere,
    ): Licence {
        return new Licence(
            $key,
            $response->expiresAt,
            $response->siteLimit,
            $response->activationCount,
            $response->isRevoked,
            $isActivatedHere && $response->isValid,
            $now,
        );
    }
}
