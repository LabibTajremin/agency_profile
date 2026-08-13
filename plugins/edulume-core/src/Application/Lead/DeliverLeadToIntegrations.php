<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Lead;

use Edulume\Core\Application\Port\Clock;
use Edulume\Core\Application\Port\CrmConnector;
use Edulume\Core\Application\Port\IntegrationLog;
use Edulume\Core\Application\Port\WebhookTransport;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadWebhookPayload;
use Edulume\Core\Domain\Lead\RetrySchedule;

/**
 * Pushes a stored lead outward: one signed webhook, then every configured connector.
 *
 * Nothing here can lose a lead. It runs after the lead is saved, every failure is logged
 * rather than thrown, and one connector refusing does not stop the next.
 */
final class DeliverLeadToIntegrations
{
    /**
     * @param list<CrmConnector> $connectors
     */
    public function __construct(
        private readonly WebhookTransport $webhookTransport,
        private readonly IntegrationLog $integrationLog,
        private readonly Clock $clock,
        private readonly array $connectors = [],
        private readonly RetrySchedule $retrySchedule = new RetrySchedule(),
    ) {
    }

    public function __invoke(Lead $lead, string $webhookUrl, string $webhookSecret): IntegrationDeliveryReport
    {
        $webhookAttempts = $webhookUrl === '' ? 0 : $this->deliverWebhook($lead, $webhookUrl, $webhookSecret);
        $delivered = [];
        $failed = [];

        foreach ($this->connectors as $connector) {
            if (!$connector->isConfigured()) {
                continue;
            }

            if ($connector->send($lead)) {
                $this->integrationLog->recordSuccess($connector->name(), $lead->id);
                $delivered[] = $connector->name();

                continue;
            }

            $this->integrationLog->recordFailure($connector->name(), $lead->id, 'the connector refused the lead');
            $failed[] = $connector->name();
        }

        return IntegrationDeliveryReport::of($webhookAttempts, $delivered, $failed);
    }

    private function deliverWebhook(Lead $lead, string $url, string $secret): int
    {
        $payload = LeadWebhookPayload::forLead($lead, $this->clock->timestamp());
        $attempt = 0;

        do {
            $attempt++;

            if ($this->webhookTransport->post($url, $payload->body, $payload->headersFor($secret))) {
                $this->integrationLog->recordSuccess('webhook', $lead->id);

                return $attempt;
            }

            $this->integrationLog->recordFailure(
                'webhook',
                $lead->id,
                sprintf('attempt %d was refused; retrying in %ds', $attempt, $this->retrySchedule->delayAfter($attempt)),
            );
        } while ($this->retrySchedule->shouldRetryAfter($attempt));

        return $attempt;
    }
}
