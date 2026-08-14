<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\ContentReader;
use Edulume\Core\Application\Port\ContentWriter;
use Edulume\Core\Domain\Content\CsvMappedRow;

/**
 * A store that behaves as the WordPress one must: an item is matched by title, so importing
 * the same file twice updates rather than duplicates, and reading honours the filters it was
 * given.
 */
final class InMemoryContentStore implements ContentWriter, ContentReader
{
    /** @var array<string, array<string, CsvMappedRow>> */
    private array $items = [];

    private int $nextId = 1;

    /** @var array<string, int> */
    private array $ids = [];

    public function upsert(string $postTypeKey, CsvMappedRow $row): int
    {
        $key = $postTypeKey . '|' . $row->title();

        $this->items[$postTypeKey][$row->title()] = $row;

        if (!array_key_exists($key, $this->ids)) {
            $this->ids[$key] = $this->nextId++;
        }

        return $this->ids[$key];
    }

    public function read(string $postTypeKey, array $filters): iterable
    {
        foreach ($this->items[$postTypeKey] ?? [] as $row) {
            if ($this->matches($row, $filters)) {
                yield $row;
            }
        }
    }

    /**
     * @return list<CsvMappedRow>
     */
    public function rows(): array
    {
        $rows = [];

        foreach ($this->items as $itemsForType) {
            foreach ($itemsForType as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function titles(): array
    {
        return array_map(static fn (CsvMappedRow $row): string => $row->title(), $this->rows());
    }

    /**
     * @param array<string, string> $filters
     */
    private function matches(CsvMappedRow $row, array $filters): bool
    {
        foreach ($filters as $taxonomyKey => $term) {
            if (!in_array($term, $row->terms[$taxonomyKey] ?? [], true)) {
                return false;
            }
        }

        return true;
    }
}
