<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * One field on a form.
 */
final class FormField
{
    private function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly FormFieldType $type,
        public readonly bool $isRequired,
        public readonly int $stepIndex,
        /** @var list<string> */
        public readonly array $options,
        public readonly ?FieldCondition $condition,
    ) {
    }

    /**
     * @param list<string> $options
     */
    public static function of(
        string $id,
        string $label,
        FormFieldType $type,
        bool $isRequired = false,
        int $stepIndex = 0,
        array $options = [],
        ?FieldCondition $condition = null
    ): self {
        return new self(
            $id,
            $label,
            $type,
            $isRequired || $type->isAlwaysRequired(),
            max(0, $stepIndex),
            $type->needsOptions() ? $options : [],
            $condition,
        );
    }

    /**
     * @param array<string, string> $answers
     */
    public function isVisibleGiven(array $answers): bool
    {
        return $this->condition === null || $this->condition->isSatisfiedBy($answers);
    }

    public function accepts(string $answer): bool
    {
        return match ($this->type) {
            FormFieldType::Email => filter_var($answer, FILTER_VALIDATE_EMAIL) !== false,
            FormFieldType::Telephone => preg_match('/^\+[1-9]\d{6,17}$/', $answer) === 1,
            FormFieldType::Number => is_numeric($answer),
            FormFieldType::Date => preg_match('/^\d{4}-\d{2}-\d{2}$/', $answer) === 1,
            FormFieldType::Consent => $answer === '1',
            FormFieldType::Select, FormFieldType::Radio => in_array($answer, $this->options, true),
            default => true,
        };
    }

    public function validationMessage(): string
    {
        return match ($this->type) {
            FormFieldType::Email => 'Enter an email address we can reply to.',
            FormFieldType::Telephone => 'Enter a phone number including its country code, like +8801700000000.',
            FormFieldType::Number => 'Enter a number.',
            FormFieldType::Date => 'Enter a date as YYYY-MM-DD.',
            FormFieldType::Consent => 'This consent is required before we can store your details.',
            FormFieldType::Select, FormFieldType::Radio => 'Choose one of the options offered.',
            default => 'Check this answer.',
        };
    }
}
