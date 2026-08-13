<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Domain\Content\CsvMappedRow;

/**
 * Reads items back out for export, honouring whatever filters the admin screen had applied.
 */
interface ContentReader
{
    /**
     * @param array<string, string> $filters the same filters the admin list was showing
     *
     * @return iterable<int, CsvMappedRow>
     */
    public function read(string $postTypeKey, array $filters): iterable;
}
