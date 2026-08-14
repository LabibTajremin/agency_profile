<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

/**
 * Where integration failures are recorded.
 *
 * They are recorded rather than thrown: an integration having a bad day must never cost the
 * consultancy the lead that was already safely stored.
 */
interface IntegrationLog
{
    public function recordSuccess(string $integration, int $leadId): void;

    public function recordFailure(string $integration, int $leadId, string $reason): void;
}
