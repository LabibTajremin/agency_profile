<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Application\Port\IntegrationLog;

/**
 * A bounded integration log in one option.
 *
 * Bounded because an unbounded log in an autoloaded option is a slow, invisible way to make
 * every page load heavier — and the entry that matters is always one of the last few, not one
 * from March.
 */
final class OptionIntegrationLog implements IntegrationLog
{
    public const OPTION = 'edulume_integration_log';

    private const MAXIMUM_ENTRIES = 100;

    public function recordSuccess(string $integration, int $leadId): void
    {
        $this->append(['integration' => $integration, 'lead' => $leadId, 'ok' => true, 'reason' => '']);
    }

    public function recordFailure(string $integration, int $leadId, string $reason): void
    {
        $this->append(['integration' => $integration, 'lead' => $leadId, 'ok' => false, 'reason' => $reason]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function entries(): array
    {
        $stored = get_option(self::OPTION, []);

        if (!is_array($stored)) {
            return [];
        }

        return array_values(array_filter($stored, 'is_array'));
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function append(array $entry): void
    {
        $entries = [...$this->entries(), [...$entry, 'at' => gmdate('c')]];

        // Autoload off: nothing on a front-end page load needs this, and an autoloaded option
        // is fetched on every single request whether it is read or not.
        update_option(self::OPTION, array_slice($entries, -self::MAXIMUM_ENTRIES), false);
    }
}
