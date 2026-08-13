<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\LeadNotifier;
use Edulume\Core\Domain\Lead\FormDefinition;
use Edulume\Core\Domain\Lead\Lead;

/** Counts what was sent, without sending anything. */
final class RecordingLeadNotifier implements LeadNotifier
{
    public int $staffNotifications = 0;

    public int $autoresponses = 0;

    public function notifyStaff(Lead $lead, FormDefinition $form): void
    {
        $this->staffNotifications++;
    }

    public function autorespond(Lead $lead, FormDefinition $form): void
    {
        $this->autoresponses++;
    }
}
