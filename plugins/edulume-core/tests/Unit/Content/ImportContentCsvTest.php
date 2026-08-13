<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Content;

use Edulume\Core\Application\Content\ExportContentCsv;
use Edulume\Core\Application\Content\ImportContentCsv;
use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Content\CsvColumnMapping;
use Edulume\Core\Domain\Content\CsvImportCursor;
use Edulume\Core\Domain\Content\CsvRowMapper;
use Edulume\Core\Domain\Content\CsvTarget;
use Edulume\Core\Tests\Unit\Fake\ArrayCsvSource;
use Edulume\Core\Tests\Unit\Fake\InMemoryContentStore;
use Edulume\Core\Tests\Unit\Fake\CountingExecutionBudget;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImportContentCsvTest extends TestCase
{
    /**
     * @return list<CsvColumnMapping>
     */
    private static function mappings(): array
    {
        return [
            CsvColumnMapping::of('Title', CsvTarget::Title, '', true),
            CsvColumnMapping::of('Summary', CsvTarget::Excerpt),
            CsvColumnMapping::of('Tuition', CsvTarget::Meta, 'tuition_fee'),
            CsvColumnMapping::of('Study Level', CsvTarget::Taxonomy, 'edulume_study-level'),
            CsvColumnMapping::of('Internal Note', CsvTarget::Ignore),
        ];
    }

    /**
     * @param list<list<string>> $rows
     */
    private static function source(array $rows): ArrayCsvSource
    {
        return new ArrayCsvSource(['Title', 'Summary', 'Tuition', 'Study Level', 'Internal Note'], $rows);
    }

    /**
     * @return list<list<string>>
     */
    private static function thousandRows(): array
    {
        $rows = [];

        for ($index = 1; $index <= 1000; $index++) {
            $rows[] = [
                sprintf('MSc Course %d', $index),
                'A postgraduate course.',
                '24000',
                'Postgraduate|Masters',
                'ignored',
            ];
        }

        return $rows;
    }

    private function postTypeKey(): string
    {
        return ContentModel::postTypeKey('course');
    }

    #[Test]
    public function it_imports_every_row_when_there_is_time(): void
    {
        $store = new InMemoryContentStore();
        $import = new ImportContentCsv($store, new CountingExecutionBudget(10));

        $report = $import(
            $this->postTypeKey(),
            self::source([['MSc Data Science', 'Great course', '24000', 'Postgraduate', 'x']]),
            new CsvRowMapper(self::mappings()),
            CsvImportCursor::start(),
        );

        $this->assertTrue($report->cursor->isComplete);
        $this->assertSame(1, $report->cursor->importedCount);
        $this->assertFalse($report->hasErrors());
        $this->assertSame(['MSc Data Science'], $store->titles());
    }

    #[Test]
    public function it_maps_fields_meta_and_taxonomies(): void
    {
        $store = new InMemoryContentStore();
        $import = new ImportContentCsv($store, new CountingExecutionBudget(10));

        $import(
            $this->postTypeKey(),
            self::source([['MSc Data Science', 'Great course', '24000', 'Postgraduate|Masters', 'x']]),
            new CsvRowMapper(self::mappings()),
            CsvImportCursor::start(),
        );

        $row = $store->rows()[0];

        $this->assertSame('Great course', $row->fields[CsvTarget::Excerpt->value]);
        $this->assertSame('24000', $row->meta['tuition_fee']);
        $this->assertSame(['Postgraduate', 'Masters'], $row->terms['edulume_study-level']);
        $this->assertArrayNotHasKey('Internal Note', $row->meta);
    }

    /**
     * The classic shared-hosting failure point: 1,000 rows against a budget that runs out
     * long before the end. The run must stop cleanly, report where it got to, and finish on
     * the next pass without importing anything twice or skipping anything.
     */
    #[Test]
    public function it_imports_a_thousand_rows_across_several_time_limited_passes(): void
    {
        $store = new InMemoryContentStore();
        $mapper = new CsvRowMapper(self::mappings());
        $source = self::source(self::thousandRows());

        $cursor = CsvImportCursor::start();
        $passes = 0;

        while (!$cursor->isComplete) {
            $import = new ImportContentCsv($store, new CountingExecutionBudget(120));
            $cursor = $import($this->postTypeKey(), $source, $mapper, $cursor)->cursor;
            $passes++;

            $this->assertLessThan(20, $passes, 'The import made no progress and would never finish.');
        }

        $this->assertGreaterThan(1, $passes, 'The budget was never actually exhausted.');
        $this->assertSame(1000, $cursor->importedCount);
        $this->assertCount(1000, $store->rows());
        $this->assertSame('MSc Course 1', $store->titles()[0]);
        $this->assertSame('MSc Course 1000', $store->titles()[999]);
    }

    #[Test]
    public function it_resumes_from_the_exact_row_it_stopped_at(): void
    {
        $store = new InMemoryContentStore();
        $mapper = new CsvRowMapper(self::mappings());
        $source = self::source(self::thousandRows());

        $first = (new ImportContentCsv($store, new CountingExecutionBudget(10)))(
            $this->postTypeKey(),
            $source,
            $mapper,
            CsvImportCursor::start(),
        );

        $this->assertFalse($first->cursor->isComplete);
        $this->assertSame(11, $first->cursor->nextRowNumber);
        $this->assertSame('MSc Course 10', $store->titles()[9]);

        $second = (new ImportContentCsv($store, new CountingExecutionBudget(10)))(
            $this->postTypeKey(),
            $source,
            $mapper,
            $first->cursor,
        );

        $this->assertSame('MSc Course 11', $store->titles()[10]);
        $this->assertSame(20, $second->cursor->importedCount);
    }

    #[Test]
    public function it_reports_a_malformed_row_instead_of_dropping_it_silently(): void
    {
        $store = new InMemoryContentStore();
        $import = new ImportContentCsv($store, new CountingExecutionBudget(10));

        $report = $import(
            $this->postTypeKey(),
            self::source([
                ['MSc Data Science', 'Great course', '24000', 'Postgraduate', 'x'],
                ['', 'No title at all', '18000', 'Undergraduate', 'x'],
                ['BA Economics', 'Another good one', '19000', 'Undergraduate', 'x'],
            ]),
            new CsvRowMapper(self::mappings()),
            CsvImportCursor::start(),
        );

        $this->assertSame(2, $report->cursor->importedCount);
        $this->assertSame(1, $report->cursor->failedCount);
        $this->assertTrue($report->hasErrors());
        $this->assertStringContainsString('Row 2', $report->errorMessages()[0]);
        $this->assertSame(['MSc Data Science', 'BA Economics'], $store->titles());
    }

    #[Test]
    public function it_never_lets_one_bad_row_abort_the_run(): void
    {
        $store = new InMemoryContentStore();
        $import = new ImportContentCsv($store, new CountingExecutionBudget(10));

        $report = $import(
            $this->postTypeKey(),
            self::source([['', '', '', '', ''], ['BA Economics', '', '', '', '']]),
            new CsvRowMapper(self::mappings()),
            CsvImportCursor::start(),
        );

        $this->assertTrue($report->cursor->isComplete);
        $this->assertSame(1, $report->cursor->importedCount);
        $this->assertSame(2, $report->cursor->processedCount());
    }

    #[Test]
    public function it_does_not_double_the_catalogue_when_the_same_file_is_imported_twice(): void
    {
        $store = new InMemoryContentStore();
        $mapper = new CsvRowMapper(self::mappings());
        $source = self::source([
            ['MSc Data Science', 'Great course', '24000', 'Postgraduate', 'x'],
            ['BA Economics', 'Another', '19000', 'Undergraduate', 'x'],
        ]);

        (new ImportContentCsv($store, new CountingExecutionBudget(10)))(
            $this->postTypeKey(),
            $source,
            $mapper,
            CsvImportCursor::start(),
        );
        (new ImportContentCsv($store, new CountingExecutionBudget(10)))(
            $this->postTypeKey(),
            $source,
            $mapper,
            CsvImportCursor::start(),
        );

        $this->assertCount(2, $store->rows());
    }

    #[Test]
    public function it_round_trips_an_export_back_through_the_importer(): void
    {
        $store = new InMemoryContentStore();
        $mapper = new CsvRowMapper(self::mappings());

        (new ImportContentCsv($store, new CountingExecutionBudget(10)))(
            $this->postTypeKey(),
            self::source([
                ['MSc Data Science', 'Great, comma-laden course', '24000', 'Postgraduate|Masters', 'x'],
                ['BA "Quoted" Economics', "Two\nlines", '19000', 'Undergraduate', 'x'],
            ]),
            $mapper,
            CsvImportCursor::start(),
        );

        $exported = (new ExportContentCsv($store))($this->postTypeKey(), self::mappings());

        $reimportStore = new InMemoryContentStore();

        (new ImportContentCsv($reimportStore, new CountingExecutionBudget(10)))(
            $this->postTypeKey(),
            ArrayCsvSource::fromCsv($exported),
            $mapper,
            CsvImportCursor::start(),
        );

        $this->assertSame($store->titles(), $reimportStore->titles());
        $this->assertSame($store->rows()[0]->meta, $reimportStore->rows()[0]->meta);
        $this->assertSame($store->rows()[0]->terms, $reimportStore->rows()[0]->terms);
        $this->assertSame($store->rows()[1]->fields, $reimportStore->rows()[1]->fields);
    }

    #[Test]
    public function it_exports_only_what_the_filters_selected(): void
    {
        $store = new InMemoryContentStore();

        (new ImportContentCsv($store, new CountingExecutionBudget(10)))(
            $this->postTypeKey(),
            self::source([
                ['MSc Data Science', '', '24000', 'Postgraduate', 'x'],
                ['BA Economics', '', '19000', 'Undergraduate', 'x'],
            ]),
            new CsvRowMapper(self::mappings()),
            CsvImportCursor::start(),
        );

        $exported = (new ExportContentCsv($store))(
            $this->postTypeKey(),
            self::mappings(),
            ['edulume_study-level' => 'Undergraduate'],
        );

        $this->assertStringContainsString('BA Economics', $exported);
        $this->assertStringNotContainsString('MSc Data Science', $exported);
    }

    #[Test]
    public function it_writes_a_header_row_the_importer_understands(): void
    {
        $exported = (new ExportContentCsv(new InMemoryContentStore()))($this->postTypeKey(), self::mappings());

        $this->assertSame("Title,Summary,Tuition,Study Level,Internal Note\r\n", $exported);
    }

    #[Test]
    public function it_treats_a_column_with_no_key_as_ignored(): void
    {
        $mapping = CsvColumnMapping::of('Tuition', CsvTarget::Meta);

        $this->assertSame(CsvTarget::Ignore, $mapping->target);
        $this->assertSame(CsvTarget::Ignore, CsvColumnMapping::ignored('Anything')->target);
    }

    #[Test]
    public function it_records_where_it_got_to_in_a_resumable_form(): void
    {
        $cursor = CsvImportCursor::of(41, 30, 10, false);

        $this->assertSame([
            'nextRowNumber' => 41,
            'importedCount' => 30,
            'failedCount' => 10,
            'isComplete' => false,
        ], $cursor->toArray());

        $this->assertSame(1, CsvImportCursor::of(-5, -1, -1, false)->nextRowNumber);
    }
}
