<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\CrmConnector;
use Edulume\Core\Domain\Lead\Lead;

/** A connector that is either absent, or present and predetermined. */
final class StubCrmConnector implements CrmConnector
{
    private function __construct(
        private readonly string $name,
        private readonly bool $isConfigured,
        private readonly bool $accepts,
    ) {
    }

    public static function configured(string $name, bool $accepts): self
    {
        return new self($name, true, $accepts);
    }

    public static function notConfigured(string $name): self
    {
        return new self($name, false, false);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    public function send(Lead $lead): bool
    {
        return $this->accepts;
    }
}
