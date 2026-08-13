<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * One thing wrong with one row.
 *
 * Errors are collected per row and reported, never used to abort the run. A single malformed
 * row in a five-thousand-row export must not cost a site owner the other 4,999.
 */
final class CsvRowError
{
    private function __construct(
        public readonly int $rowNumber,
        public readonly string $column,
        public readonly string $message,
    ) {
    }

    public static function of(int $rowNumber, string $column, string $message): self
    {
        return new self($rowNumber, $column, $message);
    }

    public function toString(): string
    {
        return sprintf('Row %d, column "%s": %s', $this->rowNumber, $this->column, $this->message);
    }
}
