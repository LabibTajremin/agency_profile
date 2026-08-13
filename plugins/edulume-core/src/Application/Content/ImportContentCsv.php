<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Content;

use Edulume\Core\Application\Port\ContentWriter;
use Edulume\Core\Application\Port\CsvSource;
use Edulume\Core\Application\Port\ExecutionBudget;
use Edulume\Core\Domain\Content\CsvImportCursor;
use Edulume\Core\Domain\Content\CsvImportReport;
use Edulume\Core\Domain\Content\CsvRowMapper;

/**
 * Imports as much of a CSV as the request's time budget allows, then reports where it got to.
 *
 * Built for a 30-second `max_execution_time`, because that is the reality on the shared
 * hosting most of these sites run on, and because an importer that dies halfway through
 * leaving no cursor is worse than one that never started.
 *
 * A malformed row is recorded and skipped. It is never allowed to abort the run: one bad row
 * in five thousand must not cost the site owner the other 4,999.
 */
final class ImportContentCsv
{
    public function __construct(
        private readonly ContentWriter $contentWriter,
        private readonly ExecutionBudget $executionBudget,
    ) {
    }

    public function __invoke(
        string $postTypeKey,
        CsvSource $source,
        CsvRowMapper $rowMapper,
        CsvImportCursor $cursor
    ): CsvImportReport {
        $imported = 0;
        $failed = 0;
        $errors = [];
        $exhaustedTheFile = true;

        foreach ($source->rowsFrom($cursor->nextRowNumber) as $rowNumber => $row) {
            if (!$this->executionBudget->hasTimeRemaining()) {
                $exhaustedTheFile = false;

                break;
            }

            $mapped = $rowMapper->map($rowNumber, $row);

            if (!$mapped->isValid()) {
                $failed++;
                $errors = array_merge($errors, $mapped->errors);
                $this->executionBudget->consume();

                continue;
            }

            $this->contentWriter->upsert($postTypeKey, $mapped);
            $imported++;
            $this->executionBudget->consume();
        }

        return CsvImportReport::of($cursor->advanced($imported, $failed, $exhaustedTheFile), $errors);
    }
}
