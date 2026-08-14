<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

use Edulume\Core\Domain\Support\Guard;

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

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'fieldId' => $this->fieldId,
            'operator' => $this->operator->value,
            'expected' => $this->expected,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): ?self
    {
        $fieldId = trim(Guard::toString($data['fieldId'] ?? ''));

        // A condition with no field to look at is not a condition; treated as absence rather
        // than as a condition that is never satisfied, which would hide the field forever.
        if ($fieldId === '') {
            return null;
        }

        return self::of(
            $fieldId,
            Guard::toEnum(ConditionOperator::class, $data['operator'] ?? null, ConditionOperator::Equals),
            Guard::toString($data['expected'] ?? ''),
        );
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
