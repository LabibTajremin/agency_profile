<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * Turns one CSV row into the fields a post needs, collecting anything wrong with it rather
 * than throwing.
 */
final class CsvRowMapper
{
    private const TERM_SEPARATOR = '|';

    /**
     * @param list<CsvColumnMapping> $mappings
     */
    public function __construct(private readonly array $mappings)
    {
    }

    /**
     * @param list<string> $row
     */
    public function map(int $rowNumber, array $row): CsvMappedRow
    {
        $fields = [];
        $meta = [];
        $terms = [];
        $relationships = [];
        $errors = [];

        foreach ($this->mappings as $index => $mapping) {
            $value = trim($row[$index] ?? '');

            if ($value === '' && $mapping->isRequired) {
                $errors[] = CsvRowError::of($rowNumber, $mapping->header, 'a value is required');

                continue;
            }

            if ($value === '') {
                continue;
            }

            match ($mapping->target) {
                CsvTarget::Title, CsvTarget::Content, CsvTarget::Excerpt, CsvTarget::Slug, CsvTarget::Status
                    => $fields[$mapping->target->value] = $value,
                CsvTarget::Meta => $meta[$mapping->key] = $value,
                CsvTarget::Taxonomy => $terms[$mapping->key] = $this->splitTerms($value),
                CsvTarget::Relationship => $relationships[$mapping->key] = $value,
                CsvTarget::Ignore => null,
            };
        }

        if (!array_key_exists(CsvTarget::Title->value, $fields)) {
            $errors[] = CsvRowError::of($rowNumber, CsvTarget::Title->value, 'every row needs a title');
        }

        return CsvMappedRow::of($rowNumber, $fields, $meta, $terms, $relationships, $errors);
    }

    /**
     * @return list<string>
     */
    private function splitTerms(string $value): array
    {
        $terms = [];

        foreach (explode(self::TERM_SEPARATOR, $value) as $term) {
            $trimmed = trim($term);

            if ($trimmed !== '') {
                $terms[] = $trimmed;
            }
        }

        return $terms;
    }
}
