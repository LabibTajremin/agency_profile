<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * The academic levels the eligibility quiz reasons about, ordered so "at least this level" is
 * a comparison rather than a lookup table.
 */
enum StudyLevel: string
{
    case Foundation = 'foundation';
    case Diploma = 'diploma';
    case Undergraduate = 'undergraduate';
    case Postgraduate = 'postgraduate';
    case Doctorate = 'doctorate';

    public function rank(): int
    {
        return match ($this) {
            self::Foundation => 1,
            self::Diploma => 2,
            self::Undergraduate => 3,
            self::Postgraduate => 4,
            self::Doctorate => 5,
        };
    }

    /**
     * The level someone at this level is normally applying *to* next.
     */
    public function nextLevel(): self
    {
        return match ($this) {
            self::Foundation => self::Diploma,
            self::Diploma, self::Undergraduate => self::Undergraduate,
            self::Postgraduate => self::Postgraduate,
            self::Doctorate => self::Doctorate,
        };
    }
}
