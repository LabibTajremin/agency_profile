<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * "Show this field only when that one says X."
 *
 * A hidden field is never required and never validated, which is the whole point: asking a
 * visitor to fill in something they cannot see is the most common way conditional forms break.
 */
final class FieldCondition
{
    private function __construct(
        public readonly string $fieldId,
        public readonly ConditionOperator $operator,
        public readonly string $expected,
    ) {
    }

    public static function of(string $fieldId, ConditionOperator $operator, string $expected = ''): self
    {
        return new self($fieldId, $operator, $expected);
    }

    /**
     * @param array<string, string> $answers
     */
    public function isSatisfiedBy(array $answers): bool
    {
        return $this->operator->matches($answers[$this->fieldId] ?? '', $this->expected);
    }
}
