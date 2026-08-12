<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The twenty-four curated accents shipped with the product.
 *
 * Seeds are chosen at a lightness the ramp can expand in both directions. A seed that is
 * already near-black leaves nothing to darken toward and produces a flat palette.
 */
final class AccentLibrary
{
    public const ACCENT_COUNT = 24;

    private const SEEDS = [
        'oxford-blue' => ['Oxford Blue', '#123a6b'],
        'aegean-blue' => ['Aegean Blue', '#1a5fb4'],
        'harbour-cyan' => ['Harbour Cyan', '#0e7490'],
        'cambridge-teal' => ['Cambridge Teal', '#0f6f72'],
        'emerald-grant' => ['Emerald Grant', '#0f766e'],
        'ivy-green' => ['Ivy Green', '#1f5c3d'],
        'fern-meadow' => ['Fern Meadow', '#3f7d20'],
        'olive-quad' => ['Olive Quad', '#5f6b1f'],
        'golden-seal' => ['Golden Seal', '#a67c00'],
        'sunrise-saffron' => ['Sunrise Saffron', '#c2830b'],
        'gulf-gold' => ['Gulf Gold', '#8f6b16'],
        'departure-amber' => ['Departure Amber', '#b4610d'],
        'terracotta-brick' => ['Terracotta Brick', '#b8452a'],
        'visa-crimson' => ['Visa Crimson', '#b3202b'],
        'application-rose' => ['Application Rose', '#be123c'],
        'royal-maroon' => ['Royal Maroon', '#6d1f2e'],
        'bursary-magenta' => ['Bursary Magenta', '#a81f68'],
        'campus-plum' => ['Campus Plum', '#7a2e6b'],
        'passport-violet' => ['Passport Violet', '#6d28d9'],
        'meridian-indigo' => ['Meridian Indigo', '#3b3ba8'],
        'scholar-navy' => ['Scholar Navy', '#1b2a4a'],
        'nordic-slate' => ['Nordic Slate', '#3f5b73'],
        'graphite-ink' => ['Graphite Ink', '#3d4451'],
        'desert-sand' => ['Desert Sand', '#8a6a3f'],
    ];

    /**
     * @return list<AccentDefinition>
     */
    public static function all(): array
    {
        $definitions = [];

        foreach (self::SEEDS as $slug => [$name, $seedHex]) {
            $definitions[] = AccentDefinition::of($slug, $name, $seedHex);
        }

        return $definitions;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::SEEDS);
    }

    public static function has(string $slug): bool
    {
        return array_key_exists($slug, self::SEEDS);
    }

    /**
     * @throws UnknownAccentException when the slug is not curated
     */
    public static function get(string $slug): AccentDefinition
    {
        if (!array_key_exists($slug, self::SEEDS)) {
            throw UnknownAccentException::forSlug($slug);
        }

        [$name, $seedHex] = self::SEEDS[$slug];

        return AccentDefinition::of($slug, $name, $seedHex);
    }
}
