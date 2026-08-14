<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The twenty-two self-hosted families shipped with the product.
 *
 * Every one is SIL Open Font Licence 1.1, which permits redistribution inside a commercial
 * theme. Each has a matching row in CREDITS.md; a family without one is a failing pull
 * request.
 */
final class FontLibrary
{
    public const FAMILY_COUNT = 22;

    public const OPEN_FONT_LICENCE = 'SIL Open Font License 1.1';
    public const OPEN_FONT_LICENCE_URL = 'https://openfontlicense.org/';

    private const TEXT_WEIGHTS = [400, 500, 600, 700];
    private const DISPLAY_WEIGHTS = [400, 500, 600, 700, 800];
    private const CODE_WEIGHTS = [400, 500, 700];

    private const LATIN = [FontSubset::Latin, FontSubset::LatinExtended];
    private const LATIN_PLUS_CYRILLIC = [FontSubset::Latin, FontSubset::LatinExtended, FontSubset::Cyrillic];
    private const LATIN_PLUS_VIETNAMESE = [FontSubset::Latin, FontSubset::LatinExtended, FontSubset::Vietnamese];

    /**
     * @var array<string, array{string, FontCategory, list<int>, list<FontSubset>}>
     */
    private const FAMILIES = [
        'inter' => ['Inter', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN_PLUS_CYRILLIC],
        'manrope' => ['Manrope', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN],
        'plus-jakarta-sans' => ['Plus Jakarta Sans', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN],
        'dm-sans' => ['DM Sans', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN],
        'work-sans' => ['Work Sans', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN_PLUS_VIETNAMESE],
        'source-sans-3' => ['Source Sans 3', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN_PLUS_CYRILLIC],
        'ibm-plex-sans' => ['IBM Plex Sans', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN_PLUS_CYRILLIC],
        'figtree' => ['Figtree', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN],
        'outfit' => ['Outfit', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN],
        'sora' => ['Sora', FontCategory::SansSerif, self::TEXT_WEIGHTS, self::LATIN],
        'noto-sans-arabic' => ['Noto Sans Arabic', FontCategory::SansSerif, self::TEXT_WEIGHTS,
            [FontSubset::Latin, FontSubset::Arabic]],
        'noto-sans-bengali' => ['Noto Sans Bengali', FontCategory::SansSerif, self::TEXT_WEIGHTS,
            [FontSubset::Latin, FontSubset::Bengali]],
        'noto-sans-devanagari' => ['Noto Sans Devanagari', FontCategory::SansSerif, self::TEXT_WEIGHTS,
            [FontSubset::Latin, FontSubset::Devanagari]],

        'lora' => ['Lora', FontCategory::Serif, self::TEXT_WEIGHTS, self::LATIN_PLUS_CYRILLIC],
        'merriweather' => ['Merriweather', FontCategory::Serif, self::TEXT_WEIGHTS, self::LATIN_PLUS_CYRILLIC],
        'source-serif-4' => ['Source Serif 4', FontCategory::Serif, self::TEXT_WEIGHTS, self::LATIN],
        'libre-baskerville' => ['Libre Baskerville', FontCategory::Serif, [400, 700], self::LATIN],
        'eb-garamond' => ['EB Garamond', FontCategory::Serif, self::TEXT_WEIGHTS, self::LATIN_PLUS_CYRILLIC],
        'playfair-display' => ['Playfair Display', FontCategory::Display, self::DISPLAY_WEIGHTS, self::LATIN_PLUS_CYRILLIC],
        'fraunces' => ['Fraunces', FontCategory::Display, self::DISPLAY_WEIGHTS, self::LATIN],

        'roboto-slab' => ['Roboto Slab', FontCategory::Slab, self::TEXT_WEIGHTS, self::LATIN_PLUS_CYRILLIC],

        'jetbrains-mono' => ['JetBrains Mono', FontCategory::Monospace, self::CODE_WEIGHTS, self::LATIN_PLUS_CYRILLIC],
    ];

    /**
     * @return list<FontFamily>
     */
    public static function all(): array
    {
        $families = [];

        foreach (self::FAMILIES as $slug => $family) {
            $families[] = self::build($slug, $family);
        }

        return $families;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::FAMILIES);
    }

    public static function has(string $slug): bool
    {
        return array_key_exists($slug, self::FAMILIES);
    }

    /**
     * @throws InvalidTypographyException when the slug is not bundled
     */
    public static function get(string $slug): FontFamily
    {
        if (!array_key_exists($slug, self::FAMILIES)) {
            throw InvalidTypographyException::forUnknownFamily($slug);
        }

        return self::build($slug, self::FAMILIES[$slug]);
    }

    /**
     * @return list<FontFamily>
     */
    public static function inCategory(FontCategory $category): array
    {
        $families = [];

        foreach (self::FAMILIES as $slug => $family) {
            if ($family[1] === $category) {
                $families[] = self::build($slug, $family);
            }
        }

        return $families;
    }

    /**
     * @param array{string, FontCategory, list<int>, list<FontSubset>} $family
     */
    private static function build(string $slug, array $family): FontFamily
    {
        [$name, $category, $weights, $subsets] = $family;

        /** @var non-empty-list<int> $weights */
        /** @var non-empty-list<FontSubset> $subsets */
        return FontFamily::of(
            $slug,
            $name,
            $category,
            self::OPEN_FONT_LICENCE,
            self::OPEN_FONT_LICENCE_URL,
            $weights,
            $subsets,
        );
    }
}
