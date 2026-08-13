<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Lead;

/**
 * What reached the outside world, and what did not.
 */
final class IntegrationDeliveryReport
{
    /**
     * @param list<string> $delivered
     * @param list<string> $failed
     */
    private function __construct(
        public readonly int $webhookAttempts,
        public readonly array $delivered,
        public readonly array $failed,
    ) {
    }

    /**
     * @param list<string> $delivered
     * @param list<string> $failed
     */
    public static function of(int $webhookAttempts, array $delivered, array $failed): self
    {
        return new self($webhookAttempts, $delivered, $failed);
    }

    public function everythingSucceeded(): bool
    {
        return $this->failed === [];
    }
}
