<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Lead;

use Edulume\Core\Application\Port\LeadRepository;
use Edulume\Core\Domain\Lead\LeadQuery;

/**
 * The inbox listing.
 *
 * A counsellor's scope is applied here, after whatever the request asked for, so a hand-edited
 * query string cannot widen it. Authorisation that lives only in the UI is not authorisation.
 */
final class ListLeads
{
    public function __construct(private readonly LeadRepository $leadRepository)
    {
    }

    public function __invoke(LeadQuery $query, ?int $restrictToCounsellorId = null): LeadPage
    {
        $scoped = $restrictToCounsellorId === null ? $query : $query->scopedToCounsellor($restrictToCounsellorId);

        return LeadPage::of(
            $this->leadRepository->search($scoped),
            $this->leadRepository->count($scoped),
            $scoped,
        );
    }
}
