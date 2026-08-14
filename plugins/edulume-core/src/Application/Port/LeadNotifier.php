<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Domain\Lead\FormDefinition;
use Edulume\Core\Domain\Lead\Lead;

/**
 * Tells the people who need to know that a lead arrived, and confirms receipt to the sender.
 *
 * A notification that fails must never lose the lead: the lead is stored first, and delivery
 * problems are reported separately.
 */
interface LeadNotifier
{
    public function notifyStaff(Lead $lead, FormDefinition $form): void;

    public function autorespond(Lead $lead, FormDefinition $form): void;
}
