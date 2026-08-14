<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Blocks;

/**
 * One block, described as data.
 *
 * Registered by the plugin rather than the theme, which is the whole reason this lives here:
 * a block registered by a theme disappears when the theme is switched, taking every post that
 * used it down to an "this block contains unexpected content" error. Content has to outlive
 * the design.
 */
final class BlockDefinition
{
    public const NAMESPACE = 'edulume';

    /**
     * @param list<BlockVariation> $variations
     * @param list<string> $features modules the block needs on the page, e.g. `carousel`
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $description,
        public readonly BlockGroup $group,
        public readonly array $variations,
        public readonly bool $isAccentAware = true,
        public readonly bool $isMotionAware = true,
        public readonly array $features = [],
    ) {
    }

    /** The full block name as WordPress knows it. */
    public function name(): string
    {
        return self::NAMESPACE . '/' . $this->slug;
    }

    public function defaultVariation(): BlockVariation
    {
        foreach ($this->variations as $variation) {
            if ($variation->isDefault) {
                return $variation;
            }
        }

        return $this->variations[0];
    }

    /** @return list<string> */
    public function variationSlugs(): array
    {
        return array_map(static fn (BlockVariation $variation): string => $variation->slug, $this->variations);
    }

    public function hasVariation(string $slug): bool
    {
        return in_array($slug, $this->variationSlugs(), true);
    }

    /**
     * The CSS class the front end renders, which is what makes a variation a style rather than
     * a second copy of the block: the markup is identical, the class is not.
     */
    public function classFor(string $variationSlug): string
    {
        $slug = $this->hasVariation($variationSlug) ? $variationSlug : $this->defaultVariation()->slug;

        return sprintf('edulume-block edulume-block--%s is-style-%s', $this->slug, $slug);
    }
}
