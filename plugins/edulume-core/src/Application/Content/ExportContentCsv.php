<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Content;

use Edulume\Core\Application\Port\ContentReader;
use Edulume\Core\Domain\Content\CsvColumnMapping;
use Edulume\Core\Domain\Content\CsvMappedRow;
use Edulume\Core\Domain\Content\CsvTarget;

/**
 * Exports items as CSV using the same column mapping the importer understands, so an export
 * can be edited in a spreadsheet and imported straight back.
 *
 * The filters passed in are the ones the admin list was showing. An export that quietly
 * returns everything when the screen said "Postgraduate, Canada" is the kind of surprise that
 * gets noticed only after it has been mailed to a client.
 */
final class ExportContentCsv
{
    private const TERM_SEPARATOR = '|';
    private const LINE_ENDING = "\r\n";

    public function __construct(private readonly ContentReader $contentReader)
    {
    }

    /**
     * @param list<CsvColumnMapping> $mappings
     * @param array<string, string> $filters
     */
    public function __invoke(string $postTypeKey, array $mappings, array $filters = []): string
    {
        $csv = $this->line(array_map(
            static fn (CsvColumnMapping $mapping): string => $mapping->header,
            $mappings,
        ));

        foreach ($this->contentReader->read($postTypeKey, $filters) as $row) {
            $csv .= $this->line($this->cellsFor($row, $mappings));
        }

        return $csv;
    }

    /**
     * @param list<CsvColumnMapping> $mappings
     *
     * @return list<string>
     */
    private function cellsFor(CsvMappedRow $row, array $mappings): array
    {
        $cells = [];

        foreach ($mappings as $mapping) {
            $cells[] = match ($mapping->target) {
                CsvTarget::Title, CsvTarget::Content, CsvTarget::Excerpt, CsvTarget::Slug, CsvTarget::Status
                    => $row->fields[$mapping->target->value] ?? '',
                CsvTarget::Meta => $row->meta[$mapping->key] ?? '',
                CsvTarget::Taxonomy => implode(self::TERM_SEPARATOR, $row->terms[$mapping->key] ?? []),
                CsvTarget::Relationship => $row->relationships[$mapping->key] ?? '',
                CsvTarget::Ignore => '',
            };
        }

        return $cells;
    }

    /**
     * @param list<string> $cells
     */
    private function line(array $cells): string
    {
        return implode(',', array_map([$this, 'escape'], $cells)) . self::LINE_ENDING;
    }

    private function escape(string $value): string
    {
        if (preg_match('/[",\r\n]/', $value) !== 1) {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }
}
