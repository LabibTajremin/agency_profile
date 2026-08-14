<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * The JSON body sent to an outbound webhook, and the signature that proves it came from here.
 *
 * The signature covers the exact bytes sent, and the timestamp is inside the signed material,
 * so an intercepted payload cannot be replayed later against a receiver that checks freshness.
 */
final class LeadWebhookPayload
{
    public const SIGNATURE_HEADER = 'X-Edulume-Signature';
    public const TIMESTAMP_HEADER = 'X-Edulume-Timestamp';

    private const SIGNATURE_ALGORITHM = 'sha256';

    private function __construct(
        public readonly string $body,
        public readonly int $timestamp,
    ) {
    }

    public static function forLead(Lead $lead, int $timestamp): self
    {
        $body = json_encode([
            'event' => 'lead.created',
            'timestamp' => $timestamp,
            'lead' => $lead->toArray(),
        ], JSON_UNESCAPED_SLASHES);

        return new self($body === false ? '{}' : $body, $timestamp);
    }

    public function signatureFor(string $secret): string
    {
        return hash_hmac(self::SIGNATURE_ALGORITHM, $this->timestamp . '.' . $this->body, $secret);
    }

    /**
     * @return array<string, string>
     */
    public function headersFor(string $secret): array
    {
        return [
            'Content-Type' => 'application/json',
            self::TIMESTAMP_HEADER => (string) $this->timestamp,
            self::SIGNATURE_HEADER => $this->signatureFor($secret),
        ];
    }

    public function isSignedWith(string $signature, string $secret): bool
    {
        return hash_equals($this->signatureFor($secret), $signature);
    }
}
