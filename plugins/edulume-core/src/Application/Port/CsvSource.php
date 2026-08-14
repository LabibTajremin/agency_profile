<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

/**
 * The uploaded file, one row at a time.
 *
 * Rows are yielded rather than returned as an array: a 30,000-row course catalogue read into
 * memory at once is how a 128 MB shared-hosting PHP process dies.
 */
interface CsvSource
{
    /**
     * @return list<string> the header row
     */
    public function header(): array;

    /**
     * Yields data rows from the given 1-based row number onward, header excluded.
     *
     * @return iterable<int, list<string>>
     */
    public function rowsFrom(int $rowNumber): iterable;
}
