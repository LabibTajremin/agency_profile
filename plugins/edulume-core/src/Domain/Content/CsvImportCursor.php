<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * How far an import got, so the next request can pick it up exactly there.
 *
 * Shared hosting routinely caps `max_execution_time` at 30 seconds. An importer that cannot
 * stop and resume simply cannot import a real course catalogue on the hosting most of these
 * sites run on.
 */
final class CsvImportCursor
{
    private function __construct(
        public readonly int $nextRowNumber,
        public readonly int $importedCount,
        public readonly int $failedCount,
        public readonly bool $isComplete,
    ) {
    }

    public static function start(): self
    {
        return new self(1, 0, 0, false);
    }

    public static function of(int $nextRowNumber, int $importedCount, int $failedCount, bool $isComplete): self
    {
        return new self(max(1, $nextRowNumber), max(0, $importedCount), max(0, $failedCount), $isComplete);
    }

    public function advanced(int $importedRows, int $failedRows, bool $isComplete): self
    {
        return new self(
            $this->nextRowNumber + $importedRows + $failedRows,
            $this->importedCount + $importedRows,
            $this->failedCount + $failedRows,
            $isComplete,
        );
    }

    public function processedCount(): int
    {
        return $this->importedCount + $this->failedCount;
    }

    /**
     * @return array<string, int|bool>
     */
    public function toArray(): array
    {
        return [
            'nextRowNumber' => $this->nextRowNumber,
            'importedCount' => $this->importedCount,
            'failedCount' => $this->failedCount,
            'isComplete' => $this->isComplete,
        ];
    }
}
