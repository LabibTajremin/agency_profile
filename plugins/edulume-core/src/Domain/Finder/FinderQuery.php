<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Finder;

/**
 * A finder's state: which facets are selected, how the results are sorted, and which page.
 *
 * The whole state round-trips through the URL. That is the difference between a filter someone
 * can send to their parents and a filter that dies with the browser tab, and in this niche the
 * shared link is most of how the finder gets used.
 */
final class FinderQuery
{
    public const DEFAULT_PER_PAGE = 24;

    /** A hard ceiling. Nothing a visitor types can raise it. */
    public const MAX_PER_PAGE = 60;

    /**
     * @param array<string, list<string>> $selections facet key mapped to chosen values
     */
    public function __construct(
        public readonly string $postType,
        public readonly array $selections = [],
        public readonly string $search = '',
        public readonly FinderSort $sort = FinderSort::Relevance,
        public readonly int $page = 1,
        public readonly int $perPage = self::DEFAULT_PER_PAGE,
    ) {
    }

    /**
     * Builds the query from request parameters, keeping only what the finder declares.
     *
     * @param list<FacetDefinition> $facets
     * @param array<string, mixed> $request
     */
    public static function fromRequest(string $postType, array $facets, array $request): self
    {
        $selections = [];

        foreach ($facets as $facet) {
            $values = $facet->accept($request[$facet->key] ?? []);

            if ($values !== []) {
                $selections[$facet->key] = $values;
            }
        }

        $search = is_scalar($request['q'] ?? null) ? trim((string) $request['q']) : '';
        $sort = FinderSort::tryFrom(is_scalar($request['sort'] ?? null) ? (string) $request['sort'] : '')
            ?? FinderSort::Relevance;

        return new self(
            $postType,
            $selections,
            $search,
            $sort,
            self::boundedPage($request['page'] ?? 1),
            self::boundedPerPage($request['per_page'] ?? self::DEFAULT_PER_PAGE),
        );
    }

    /**
     * The query as URL parameters, in a stable order so the same filter always yields the same
     * link — which is what lets a shared URL be cached and compared.
     *
     * @return array<string, string>
     */
    public function toQueryParameters(): array
    {
        $parameters = [];
        $keys = array_keys($this->selections);

        sort($keys);

        foreach ($keys as $key) {
            $values = $this->selections[$key];

            sort($values);

            $parameters[$key] = implode(',', $values);
        }

        if ($this->search !== '') {
            $parameters['q'] = $this->search;
        }

        if ($this->sort !== FinderSort::Relevance) {
            $parameters['sort'] = $this->sort->value;
        }

        if ($this->page > 1) {
            $parameters['page'] = (string) $this->page;
        }

        if ($this->perPage !== self::DEFAULT_PER_PAGE) {
            $parameters['per_page'] = (string) $this->perPage;
        }

        return $parameters;
    }

    public function toQueryString(): string
    {
        return http_build_query($this->toQueryParameters(), '', '&', PHP_QUERY_RFC3986);
    }

    /** How many rows to skip. Derived rather than stored so it can never disagree with the page. */
    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasSelections(): bool
    {
        return $this->selections !== [] || $this->search !== '';
    }

    /** Changing a filter returns to page one; staying on page nine of the old result set is a bug. */
    public function withSelection(string $facetKey, array $values): self
    {
        $selections = $this->selections;

        if ($values === []) {
            unset($selections[$facetKey]);
        } else {
            $selections[$facetKey] = array_values($values);
        }

        return new self($this->postType, $selections, $this->search, $this->sort, 1, $this->perPage);
    }

    public function withPage(int $page): self
    {
        return new self(
            $this->postType,
            $this->selections,
            $this->search,
            $this->sort,
            self::boundedPage($page),
            $this->perPage,
        );
    }

    public function withSort(FinderSort $sort): self
    {
        return new self($this->postType, $this->selections, $this->search, $sort, 1, $this->perPage);
    }

    public function cleared(): self
    {
        return new self($this->postType, [], '', $this->sort, 1, $this->perPage);
    }

    private static function boundedPage(mixed $value): int
    {
        $page = is_numeric($value) ? (int) $value : 1;

        return max(1, $page);
    }

    /**
     * Clamped rather than validated-and-rejected: a hostile `per_page=100000` should quietly
     * become a normal page, not an error page, and certainly not an unbounded query.
     */
    private static function boundedPerPage(mixed $value): int
    {
        $perPage = is_numeric($value) ? (int) $value : self::DEFAULT_PER_PAGE;

        return max(1, min(self::MAX_PER_PAGE, $perPage));
    }
}
