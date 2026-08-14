<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Application\Port\CsvSource;
use RuntimeException;

/**
 * Reads an uploaded CSV off disk.
 *
 * Streams with a generator rather than reading the file into memory. A 3,000-row course
 * catalogue with long descriptions is comfortably large enough to exhaust a 128 MB limit if
 * you load it all at once, and the hosting this runs on is exactly where that limit lives.
 */
final class FileCsvSource implements CsvSource
{
    public function __construct(private readonly string $path)
    {
    }

    public function header(): array
    {
        $handle = $this->open();
        $header = fgetcsv($handle);

        fclose($handle);

        if (!is_array($header)) {
            return [];
        }

        return array_map(
            static fn (mixed $column): string => is_string($column) ? trim($column) : '',
            $header,
        );
    }

    public function rowsFrom(int $rowNumber): iterable
    {
        $handle = $this->open();

        // The header is row zero and is never yielded; row 1 is the first data row, which is
        // what the cursor counts.
        fgetcsv($handle);

        $current = 1;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null]) {
                // fgetcsv reports a blank line this way. Skipping it without counting keeps the
                // row numbers matching what a person sees in their spreadsheet.
                continue;
            }

            if ($current >= $rowNumber) {
                yield $current => array_map(
                    static fn (mixed $cell): string => is_string($cell) ? $cell : '',
                    $row,
                );
            }

            $current++;
        }

        fclose($handle);
    }

    /**
     * @return resource
     */
    private function open()
    {
        $handle = fopen($this->path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open %s for reading.', $this->path));
        }

        return $handle;
    }
}
