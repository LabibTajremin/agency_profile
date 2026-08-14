<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * How a conditional rule compares a field's answer.
 */
enum ConditionOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not-equals';
    case Contains = 'contains';
    case IsEmpty = 'is-empty';
    case IsNotEmpty = 'is-not-empty';

    public function matches(string $answer, string $expected): bool
    {
        return match ($this) {
            self::Equals => $answer === $expected,
            self::NotEquals => $answer !== $expected,
            self::Contains => $expected !== '' && str_contains($answer, $expected),
            self::IsEmpty => trim($answer) === '',
            self::IsNotEmpty => trim($answer) !== '',
        };
    }
}
