<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

use DomainException;

final class IllegalLeadTransitionException extends DomainException
{
    public static function between(LeadStatus $from, LeadStatus $to): self
    {
        if ($from->isClosed()) {
            return new self(sprintf('A lead that is %s cannot be reopened as %s.', $from->label(), $to->label()));
        }

        return new self(sprintf('A lead cannot move from %s straight to %s.', $from->label(), $to->label()));
    }
}
