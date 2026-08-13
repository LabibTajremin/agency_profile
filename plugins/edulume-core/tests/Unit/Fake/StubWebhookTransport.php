<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\WebhookTransport;

/** A transport whose answers are decided in advance, so retry behaviour is a fact, not a wait. */
final class StubWebhookTransport implements WebhookTransport
{
    public int $attempts = 0;

    public string $lastUrl = '';

    /** @var array<string, string> */
    public array $lastHeaders = [];

    private function __construct(private readonly int $refusalsBeforeAccepting, private readonly bool $everAccepts)
    {
    }

    public static function alwaysAccepting(): self
    {
        return new self(0, true);
    }

    public static function alwaysRefusing(): self
    {
        return new self(0, false);
    }

    public static function refusingTimes(int $times): self
    {
        return new self($times, true);
    }

    public function post(string $url, string $body, array $headers): bool
    {
        $this->attempts++;
        $this->lastUrl = $url;
        $this->lastHeaders = $headers;

        if (!$this->everAccepts) {
            return false;
        }

        return $this->attempts > $this->refusalsBeforeAccepting;
    }
}
