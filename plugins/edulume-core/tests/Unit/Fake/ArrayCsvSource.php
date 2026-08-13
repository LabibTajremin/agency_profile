<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\CsvSource;

/**
 * A CSV source backed by an array, yielding rows one at a time exactly as a file-backed one
 * does — including the 1-based row numbering the cursor depends on.
 */
final class ArrayCsvSource implements CsvSource
{
    /**
     * @param list<string> $header
     * @param list<list<string>> $rows
     */
    public function __construct(
        private readonly array $header,
        private readonly array $rows,
    ) {
    }

    public static function fromCsv(string $csv): self
    {
        $handle = fopen('php://memory', 'r+');

        if ($handle === false) {
            return new self([], []);
        }

        fwrite($handle, $csv);
        rewind($handle);

        $header = [];
        $rows = [];

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $cells = array_map(static fn (?string $cell): string => $cell ?? '', $row);

            if ($header === []) {
                $header = $cells;

                continue;
            }

            $rows[] = $cells;
        }

        fclose($handle);

        return new self($header, $rows);
    }

    public function header(): array
    {
        return $this->header;
    }

    public function rowsFrom(int $rowNumber): iterable
    {
        foreach ($this->rows as $index => $row) {
            $currentRowNumber = $index + 1;

            if ($currentRowNumber < $rowNumber) {
                continue;
            }

            yield $currentRowNumber => $row;
        }
    }
}
