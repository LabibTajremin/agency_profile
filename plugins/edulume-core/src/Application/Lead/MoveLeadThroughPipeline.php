<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Lead;

use Edulume\Core\Application\Port\Clock;
use Edulume\Core\Application\Port\LeadRepository;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadNote;
use Edulume\Core\Domain\Lead\LeadStatus;

/**
 * Moves a lead along the pipeline, assigns it, and records notes.
 *
 * The legality of a move is the entity's business; this use case only loads, delegates and
 * saves. Duplicating the rule here would be the second place it could drift out of date.
 */
final class MoveLeadThroughPipeline
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly Clock $clock,
    ) {
    }

    public function moveTo(int $leadId, LeadStatus $status, int $actorId): ?Lead
    {
        return $this->mutate($leadId, fn (Lead $lead): Lead => $lead->movedTo($status, $actorId, $this->clock->now()));
    }

    public function assignTo(int $leadId, int $counsellorId): ?Lead
    {
        return $this->mutate($leadId, fn (Lead $lead): Lead => $lead->assignedTo($counsellorId, $this->clock->now()));
    }

    public function annotate(int $leadId, int $authorId, string $body): ?Lead
    {
        return $this->mutate(
            $leadId,
            fn (Lead $lead): Lead => $lead->annotated(LeadNote::written($authorId, $body, $this->clock->now())),
        );
    }

    /**
     * @param callable(Lead): Lead $change
     */
    private function mutate(int $leadId, callable $change): ?Lead
    {
        $lead = $this->leadRepository->find($leadId);

        if ($lead === null) {
            return null;
        }

        $changed = $change($lead);

        $this->leadRepository->save($changed, $this->clock->now());

        return $changed;
    }
}
