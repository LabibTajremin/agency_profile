<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Finder;

/**
 * One filterable facet on a finder.
 *
 * Facets are declared rather than inferred from the request. A finder that filters on whatever
 * query parameters happen to arrive is a finder that can be asked to filter on an unindexed
 * meta key by anyone who can type in the address bar.
 */
final class FacetDefinition
{
    /**
     * @param list<string> $allowedValues an empty list means any value the source accepts
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly FacetSource $source,
        public readonly bool $isMultiple = true,
        public readonly array $allowedValues = [],
    ) {
    }

    /**
     * @return list<string> the values from the request this facet will actually use
     */
    public function accept(mixed $raw): array
    {
        $values = [];

        foreach (is_array($raw) ? $raw : [$raw] as $candidate) {
            $value = is_scalar($candidate) ? trim((string) $candidate) : '';

            if ($value === '' || in_array($value, $values, true)) {
                continue;
            }

            if ($this->allowedValues !== [] && !in_array($value, $this->allowedValues, true)) {
                continue;
            }

            $values[] = $value;
        }

        return $this->isMultiple ? $values : array_slice($values, 0, 1);
    }
}
