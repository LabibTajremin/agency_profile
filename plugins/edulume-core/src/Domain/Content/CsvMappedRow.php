<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * One row, mapped and judged.
 */
final class CsvMappedRow
{
    /**
     * @param array<string, string> $fields
     * @param array<string, string> $meta
     * @param array<string, list<string>> $terms
     * @param array<string, string> $relationships
     * @param list<CsvRowError> $errors
     */
    private function __construct(
        public readonly int $rowNumber,
        public readonly array $fields,
        public readonly array $meta,
        public readonly array $terms,
        public readonly array $relationships,
        public readonly array $errors,
    ) {
    }

    /**
     * @param array<string, string> $fields
     * @param array<string, string> $meta
     * @param array<string, list<string>> $terms
     * @param array<string, string> $relationships
     * @param list<CsvRowError> $errors
     */
    public static function of(
        int $rowNumber,
        array $fields,
        array $meta,
        array $terms,
        array $relationships,
        array $errors
    ): self {
        return new self($rowNumber, $fields, $meta, $terms, $relationships, $errors);
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function title(): string
    {
        return $this->fields[CsvTarget::Title->value] ?? '';
    }
}
