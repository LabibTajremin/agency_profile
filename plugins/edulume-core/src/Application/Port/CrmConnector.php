<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Domain\Lead\Lead;

/**
 * An outbound CRM or list — Mailchimp, Brevo, Google Sheets, HubSpot.
 *
 * `name()` exists so a failure can be logged as "Mailchimp refused this" rather than as an
 * anonymous integration error nobody can act on.
 */
interface CrmConnector
{
    public function name(): string;

    public function isConfigured(): bool;

    public function send(Lead $lead): bool;
}
