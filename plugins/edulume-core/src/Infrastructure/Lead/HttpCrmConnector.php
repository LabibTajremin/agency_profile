<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Application\Port\CrmConnector;
use Edulume\Core\Domain\Lead\Lead;

/**
 * The CRM connectors.
 *
 * One class configured four ways rather than four classes: HubSpot, Zoho, Pipedrive and Mailchimp
 * all take a POST with a bearer or key auth and a flat contact payload, and four near-identical
 * files would be four places to fix the same bug.
 *
 * A failure here never blocks the lead. The enquiry is already stored by the time this runs, and
 * losing it because a CRM had a bad afternoon is the one outcome the whole integration exists to
 * avoid.
 */
final class HttpCrmConnector implements CrmConnector
{
    private const ENDPOINTS = [
        'hubspot' => 'https://api.hubapi.com/crm/v3/objects/contacts',
        'zoho' => 'https://www.zohoapis.com/crm/v3/Leads',
        'pipedrive' => 'https://api.pipedrive.com/v1/persons',
        'mailchimp' => 'https://us1.api.mailchimp.com/3.0/lists',
    ];

    private const TIMEOUT_SECONDS = 8;

    public function __construct(
        private readonly string $provider,
        private readonly string $apiKey,
        private readonly string $listId = '',
    ) {
    }

    public function name(): string
    {
        return $this->provider;
    }

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '' && array_key_exists($this->provider, self::ENDPOINTS);
    }

    public function send(Lead $lead): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $response = wp_remote_post($this->endpoint(), [
            'timeout' => self::TIMEOUT_SECONDS,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => (string) wp_json_encode($this->payloadFor($lead)),
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $status = (int) wp_remote_retrieve_response_code($response);

        return $status >= 200 && $status < 300;
    }

    private function endpoint(): string
    {
        $base = self::ENDPOINTS[$this->provider];

        return $this->provider === 'mailchimp' && $this->listId !== ''
            ? $base . '/' . $this->listId . '/members'
            : $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(Lead $lead): array
    {
        [$first, $last] = $this->splitName($lead->name);

        return match ($this->provider) {
            'hubspot' => ['properties' => [
                'email' => $lead->email,
                'firstname' => $first,
                'lastname' => $last,
                'phone' => $lead->phone,
            ]],
            'zoho' => ['data' => [[
                'Last_Name' => $last === '' ? $first : $last,
                'First_Name' => $first,
                'Email' => $lead->email,
                'Phone' => $lead->phone,
            ]]],
            'pipedrive' => [
                'name' => $lead->name,
                'email' => [$lead->email],
                'phone' => [$lead->phone],
            ],
            default => [
                'email_address' => $lead->email,
                // Pending rather than subscribed: adding someone to a mailing list because they
                // asked about a course is what gets a sending domain blacklisted.
                'status' => 'pending',
                'merge_fields' => ['FNAME' => $first, 'LNAME' => $last],
            ],
        };
    }

    /**
     * @return array{string, string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = (string) ($parts[0] ?? '');
        $last = count($parts) > 1 ? (string) end($parts) : '';

        return [$first, $last];
    }
}
