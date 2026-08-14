<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

use Edulume\Core\Domain\Support\Guard;

/**
 * A form: its fields, how they are stepped, where it appears, and what it promises about the
 * data it collects.
 *
 * The retention period lives here rather than in a global setting because different forms
 * collect different things — a newsletter signup and a full application enquiry do not deserve
 * the same retention.
 */
final class FormDefinition
{
    public const MINIMUM_RETENTION_DAYS = 30;
    public const MAXIMUM_RETENTION_DAYS = 3650;
    public const DEFAULT_RETENTION_DAYS = 730;

    /**
     * @param list<FormField> $fields
     */
    private function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly array $fields,
        public readonly FormPlacement $placement,
        public readonly int $retentionDays,
        public readonly string $consentText,
    ) {
    }

    /**
     * @param list<FormField> $fields
     */
    public static function of(
        string $id,
        string $title,
        array $fields,
        FormPlacement $placement = FormPlacement::Inline,
        int $retentionDays = self::DEFAULT_RETENTION_DAYS,
        string $consentText = ''
    ): self {
        return new self(
            $id,
            $title,
            $fields,
            $placement,
            min(self::MAXIMUM_RETENTION_DAYS, max(self::MINIMUM_RETENTION_DAYS, $retentionDays)),
            $consentText,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'fields' => array_map(static fn (FormField $field): array => $field->toArray(), $this->fields),
            'placement' => $this->placement->value,
            'retentionDays' => $this->retentionDays,
            'consentText' => $this->consentText,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $fields = [];

        foreach (Guard::toArray($data['fields'] ?? []) as $row) {
            // A row that is not an array is a storage corruption, not a field. Skipped rather
            // than defaulted, because a field with no id would silently collect nothing.
            if (is_array($row)) {
                $fields[] = FormField::fromArray($row);
            }
        }

        return self::of(
            Guard::toString($data['id'] ?? ''),
            Guard::toString($data['title'] ?? ''),
            $fields,
            Guard::toEnum(FormPlacement::class, $data['placement'] ?? null, FormPlacement::Inline),
            Guard::toInt($data['retentionDays'] ?? self::DEFAULT_RETENTION_DAYS),
            Guard::toString($data['consentText'] ?? ''),
        );
    }

    public function stepCount(): int
    {
        $highest = 0;

        foreach ($this->fields as $field) {
            $highest = max($highest, $field->stepIndex);
        }

        return $highest + 1;
    }

    public function isMultiStep(): bool
    {
        return $this->stepCount() > 1;
    }

    /**
     * @return list<FormField>
     */
    public function fieldsOnStep(int $stepIndex): array
    {
        $fields = [];

        foreach ($this->fields as $field) {
            if ($field->stepIndex === $stepIndex) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * How far through the form a visitor is, as a percentage, for the progress indicator.
     */
    public function progressAfterStep(int $stepIndex): int
    {
        return (int) round((min($stepIndex + 1, $this->stepCount()) / $this->stepCount()) * 100);
    }

    public function field(string $fieldId): ?FormField
    {
        foreach ($this->fields as $field) {
            if ($field->id === $fieldId) {
                return $field;
            }
        }

        return null;
    }

    public function requiresConsent(): bool
    {
        foreach ($this->fields as $field) {
            if ($field->type === FormFieldType::Consent) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $answers
     *
     * @return array<string, string> field id to message, empty when everything checks out
     */
    public function validate(array $answers): array
    {
        $errors = [];

        foreach ($this->fields as $field) {
            if (!$field->isVisibleGiven($answers)) {
                continue;
            }

            $answer = trim($answers[$field->id] ?? '');

            if ($answer === '') {
                if ($field->isRequired) {
                    $errors[$field->id] = $field->type === FormFieldType::Consent
                        ? $field->validationMessage()
                        : sprintf('%s is required.', $field->label);
                }

                continue;
            }

            if (!$field->accepts($answer)) {
                $errors[$field->id] = $field->validationMessage();
            }
        }

        return $errors;
    }

    /**
     * Answers to fields the visitor could not see are dropped rather than stored: a stale
     * answer from a branch that was later hidden is worse than no answer at all.
     *
     * @param array<string, string> $answers
     *
     * @return array<string, string>
     */
    public function visibleAnswers(array $answers): array
    {
        $visible = [];

        foreach ($this->fields as $field) {
            if ($field->isVisibleGiven($answers) && array_key_exists($field->id, $answers)) {
                $visible[$field->id] = trim($answers[$field->id]);
            }
        }

        return $visible;
    }
}
