<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * The lead pipeline.
 *
 * Which moves are legal is a business rule, so it lives here rather than in a dropdown's
 * options. A counsellor cannot mark a lead Closed Won without an application ever having been
 * started, and a closed lead cannot drift back into the pipeline by accident.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case CounsellingBooked = 'counselling-booked';
    case ApplicationStarted = 'application-started';
    case ClosedWon = 'closed-won';
    case ClosedLost = 'closed-lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::CounsellingBooked => 'Counselling Booked',
            self::ApplicationStarted => 'Application Started',
            self::ClosedWon => 'Closed Won',
            self::ClosedLost => 'Closed Lost',
        };
    }

    public function isClosed(): bool
    {
        return $this === self::ClosedWon || $this === self::ClosedLost;
    }

    /**
     * Forward one step, or out to Closed Lost from anywhere open — a lead can go quiet at any
     * stage, and pretending otherwise just means counsellors lie to the pipeline.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Contacted, self::ClosedLost],
            self::Contacted => [self::CounsellingBooked, self::ClosedLost],
            self::CounsellingBooked => [self::ApplicationStarted, self::ClosedLost],
            self::ApplicationStarted => [self::ClosedWon, self::ClosedLost],
            self::ClosedWon, self::ClosedLost => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
