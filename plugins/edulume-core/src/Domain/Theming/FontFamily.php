<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * One bundled, self-hosted font family.
 *
 * Families are self-hosted WOFF2 by default. The Google CDN is an opt-in toggle, off by
 * default: a default-on CDN sends every visitor's IP to a third party, which is a GDPR
 * problem, and costs a connection to a second origin, which is a performance one.
 */
final class FontFamily
{
    private function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly FontCategory $category,
        public readonly string $licence,
        public readonly string $licenceUrl,
        /** @var non-empty-list<int> */
        public readonly array $weights,
        /** @var non-empty-list<FontSubset> */
        public readonly array $subsets,
    ) {
    }

    /**
     * @param non-empty-list<int> $weights
     * @param non-empty-list<FontSubset> $subsets
     */
    public static function of(
        string $slug,
        string $name,
        FontCategory $category,
        string $licence,
        string $licenceUrl,
        array $weights,
        array $subsets
    ): self {
        return new self($slug, $name, $category, $licence, $licenceUrl, $weights, $subsets);
    }

    /**
     * The CSS `font-family` value, quoted when the name contains a space, always closing with
     * the category's generic family.
     */
    public function toCssStack(): string
    {
        $quoted = str_contains($this->name, ' ') ? sprintf('"%s"', $this->name) : $this->name;

        return $quoted . ', ' . $this->category->cssFallback();
    }

    public function supports(FontSubset $subset): bool
    {
        return in_array($subset, $this->subsets, true);
    }

    /**
     * @return list<FontSubset>
     */
    public function subsetsWithin(FontSubset ...$requested): array
    {
        $available = [];

        foreach ($requested as $subset) {
            if ($this->supports($subset)) {
                $available[] = $subset;
            }
        }

        return $available;
    }
}
