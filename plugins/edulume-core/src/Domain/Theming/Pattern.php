<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\Srgb;

/**
 * One tileable SVG pattern, tinted at render time.
 *
 * The markup carries a `{{color}}` placeholder instead of a fixed fill so a single tile
 * serves every accent. Output is a fully percent-encoded data URI: raster tiles cost a
 * request each and blur when scaled, and an unencoded SVG data URI breaks CSS `url()` the
 * moment the markup contains a `#`.
 */
final class Pattern
{
    public const TINT_PLACEHOLDER = '{{color}}';

    private const DATA_URI_PREFIX = 'data:image/svg+xml,';

    private function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly PatternGroup $group,
        private readonly string $markup,
    ) {
    }

    /**
     * @throws InvalidPatternException when the markup is not a tintable SVG element
     */
    public static function of(string $slug, string $name, PatternGroup $group, string $markup): self
    {
        if (!str_starts_with($markup, '<svg') || !str_ends_with($markup, '</svg>')) {
            throw InvalidPatternException::forMarkupThatIsNotSvg($slug);
        }

        if (!str_contains($markup, self::TINT_PLACEHOLDER)) {
            throw InvalidPatternException::forMissingTintPlaceholder($slug, self::TINT_PLACEHOLDER);
        }

        return new self($slug, $name, $group, $markup);
    }

    public function markupTintedWith(Srgb $tint): string
    {
        return str_replace(self::TINT_PLACEHOLDER, $tint->toHex(), $this->markup);
    }

    /**
     * Every byte outside the URI unreserved set is percent-encoded, so the result is safe
     * inside `url()` regardless of what the markup contains.
     */
    public function toDataUri(Srgb $tint): string
    {
        return self::DATA_URI_PREFIX . rawurlencode($this->markupTintedWith($tint));
    }

    public function untintedMarkup(): string
    {
        return $this->markup;
    }
}
