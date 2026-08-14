<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Application\Port\WebhookTransport;

/**
 * Posts a signed webhook through `wp_remote_post`.
 *
 * `blocking` is true and the timeout is short. A non-blocking request cannot report whether it
 * arrived, which would make the retry schedule decorative — and the retry schedule is the whole
 * reason a webhook is worth having over a fire-and-forget POST.
 */
final class WpRemoteWebhookTransport implements WebhookTransport
{
    private const TIMEOUT_SECONDS = 8;

    public function post(string $url, string $body, array $headers): bool
    {
        $response = wp_remote_post($url, [
            'timeout' => self::TIMEOUT_SECONDS,
            'blocking' => true,
            'headers' => [...$headers, 'Content-Type' => 'application/json'],
            'body' => $body,
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $status = (int) wp_remote_retrieve_response_code($response);

        // Any 2xx counts. A receiver that answers 202 has accepted the delivery, and treating
        // that as a failure would retry something that already arrived.
        return $status >= 200 && $status < 300;
    }
}
