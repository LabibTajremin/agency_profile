<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

/**
 * Sends one HTTP request and says whether the receiver accepted it.
 */
interface WebhookTransport
{
    /**
     * @param array<string, string> $headers
     */
    public function post(string $url, string $body, array $headers): bool;
}
