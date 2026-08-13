<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\IntegrationLog;

/** Keeps what was logged, so a test can assert a failure was recorded rather than swallowed. */
final class RecordingIntegrationLog implements IntegrationLog
{
    /** @var list<array{integration:string, leadId:int}> */
    public array $successes = [];

    /** @var list<array{integration:string, leadId:int, reason:string}> */
    public array $failures = [];

    public function recordSuccess(string $integration, int $leadId): void
    {
        $this->successes[] = ['integration' => $integration, 'leadId' => $leadId];
    }

    public function recordFailure(string $integration, int $leadId, string $reason): void
    {
        $this->failures[] = ['integration' => $integration, 'leadId' => $leadId, 'reason' => $reason];
    }
}
