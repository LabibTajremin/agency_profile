<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The twenty-eight tileable patterns shipped with the product, including the four this
 * niche actually asks for: passport stamps, globe meridians, graduation caps and a compass
 * rose.
 *
 * Every tile is vector, seamless, and carries a single `{{color}}` placeholder.
 */
final class PatternLibrary
{
    public const PATTERN_COUNT = 28;

    private const OPEN = '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d">';

    /**
     * @var array<string, array{string, PatternGroup, int, int, string}>
     */
    private const TILES = [
        'dot-grid' => ['Dot Grid', PatternGroup::Dots, 16, 16, '<circle cx="8" cy="8" r="1.5" fill="{{color}}"/>'],
        'dot-offset' => ['Offset Dots', PatternGroup::Dots, 24, 24,
            '<circle cx="6" cy="6" r="1.5" fill="{{color}}"/><circle cx="18" cy="18" r="1.5" fill="{{color}}"/>'],
        'dot-scatter' => ['Scattered Dots', PatternGroup::Dots, 48, 48,
            '<circle cx="9" cy="14" r="1.6" fill="{{color}}"/><circle cx="33" cy="7" r="1.1" fill="{{color}}"/>'
            . '<circle cx="41" cy="31" r="1.8" fill="{{color}}"/><circle cx="17" cy="39" r="1.2" fill="{{color}}"/>'],
        'ring-grid' => ['Ring Grid', PatternGroup::Dots, 24, 24,
            '<circle cx="12" cy="12" r="5" fill="none" stroke="{{color}}" stroke-width="1"/>'],

        'thin-stripes' => ['Thin Stripes', PatternGroup::Lines, 8, 8, '<rect width="8" height="1" fill="{{color}}"/>'],
        'diagonal-stripes' => ['Diagonal Stripes', PatternGroup::Lines, 16, 16,
            '<path d="M-4 4L4 -4M0 16L16 0M12 20L20 12" stroke="{{color}}" stroke-width="1.5" fill="none"/>'],
        'crosshatch' => ['Crosshatch', PatternGroup::Lines, 16, 16,
            '<path d="M0 16L16 0M0 0L16 16" stroke="{{color}}" stroke-width="1" fill="none"/>'],
        'grid-lines' => ['Grid Lines', PatternGroup::Lines, 24, 24,
            '<path d="M0 0H24M0 0V24" stroke="{{color}}" stroke-width="1" fill="none"/>'],
        'dashed-rules' => ['Dashed Rules', PatternGroup::Lines, 24, 24,
            '<path d="M0 12H10" stroke="{{color}}" stroke-width="1" fill="none"/>'],

        'squares' => ['Squares', PatternGroup::Geometric, 16, 16, '<rect x="4" y="4" width="8" height="8" fill="{{color}}"/>'],
        'triangles' => ['Triangles', PatternGroup::Geometric, 24, 24, '<path d="M12 4L21 20H3Z" fill="{{color}}"/>'],
        'honeycomb' => ['Honeycomb', PatternGroup::Geometric, 28, 24,
            '<path d="M7 2L21 2L28 12L21 22L7 22L0 12Z" fill="none" stroke="{{color}}" stroke-width="1"/>'],
        'diamonds' => ['Diamonds', PatternGroup::Geometric, 24, 24,
            '<path d="M12 2L22 12L12 22L2 12Z" fill="none" stroke="{{color}}" stroke-width="1"/>'],
        'chevrons' => ['Chevrons', PatternGroup::Geometric, 24, 12,
            '<path d="M0 11L12 1L24 11" fill="none" stroke="{{color}}" stroke-width="2"/>'],
        'plus-signs' => ['Plus Signs', PatternGroup::Geometric, 20, 20,
            '<path d="M10 6V14M6 10H14" stroke="{{color}}" stroke-width="2" fill="none"/>'],

        'sine-waves' => ['Sine Waves', PatternGroup::Waves, 48, 24,
            '<path d="M0 12Q12 0 24 12T48 12" fill="none" stroke="{{color}}" stroke-width="1.5"/>'],
        'arcs' => ['Arcs', PatternGroup::Waves, 24, 24,
            '<path d="M0 24A24 24 0 0 1 24 0" fill="none" stroke="{{color}}" stroke-width="1.5"/>'],
        'scallops' => ['Scallops', PatternGroup::Waves, 16, 16,
            '<path d="M0 12A8 8 0 0 1 16 12" fill="none" stroke="{{color}}" stroke-width="1.5"/>'],

        'specks' => ['Specks', PatternGroup::Texture, 32, 32,
            '<rect x="4" y="7" width="1.5" height="1.5" fill="{{color}}"/><rect x="21" y="3" width="1" height="1" fill="{{color}}"/>'
            . '<rect x="13" y="19" width="1.5" height="1.5" fill="{{color}}"/><rect x="27" y="24" width="1" height="1" fill="{{color}}"/>'],
        'weave' => ['Weave', PatternGroup::Texture, 16, 16,
            '<rect x="0" y="6" width="16" height="4" fill="{{color}}"/><rect x="6" y="0" width="4" height="16" fill="{{color}}"/>'],
        'herringbone' => ['Herringbone', PatternGroup::Texture, 24, 24,
            '<path d="M0 12L12 0M12 24L24 12" stroke="{{color}}" stroke-width="2" fill="none"/>'],
        'terrazzo' => ['Terrazzo', PatternGroup::Texture, 40, 40,
            '<path d="M6 8L11 6L12 12Z" fill="{{color}}"/><path d="M28 5L33 9L27 12Z" fill="{{color}}"/>'
            . '<path d="M18 26L24 24L23 31Z" fill="{{color}}"/><circle cx="34" cy="30" r="2" fill="{{color}}"/>'],

        'halftone' => ['Halftone', PatternGroup::Editorial, 32, 32,
            '<circle cx="8" cy="8" r="3" fill="{{color}}"/><circle cx="24" cy="8" r="2" fill="{{color}}"/>'
            . '<circle cx="8" cy="24" r="2" fill="{{color}}"/><circle cx="24" cy="24" r="1" fill="{{color}}"/>'],
        'column-rules' => ['Column Rules', PatternGroup::Editorial, 32, 32,
            '<path d="M8 0V32" stroke="{{color}}" stroke-width="1" fill="none"/><circle cx="24" cy="16" r="1.5" fill="{{color}}"/>'],

        'passport-stamps' => ['Passport Stamps', PatternGroup::Travel, 48, 48,
            '<rect x="6" y="9" width="20" height="14" rx="2" fill="none" stroke="{{color}}" stroke-width="1.2"'
            . ' transform="rotate(-12 16 16)"/><circle cx="34" cy="33" r="7" fill="none" stroke="{{color}}"'
            . ' stroke-width="1.2" stroke-dasharray="3 2"/>'],
        'globe-meridians' => ['Globe Meridians', PatternGroup::Travel, 48, 48,
            '<circle cx="24" cy="24" r="15" fill="none" stroke="{{color}}" stroke-width="1.2"/>'
            . '<ellipse cx="24" cy="24" rx="7" ry="15" fill="none" stroke="{{color}}" stroke-width="1"/>'
            . '<path d="M9 24H39M12 16H36M12 32H36" stroke="{{color}}" stroke-width="1" fill="none"/>'],
        'graduation-caps' => ['Graduation Cap Scatter', PatternGroup::Travel, 48, 48,
            '<path d="M4 14L14 10L24 14L14 18Z" fill="{{color}}"/><path d="M22 15V21" stroke="{{color}}" stroke-width="1"/>'
            . '<path d="M26 34L34 31L42 34L34 37Z" fill="{{color}}"/><path d="M40 34.5V39" stroke="{{color}}" stroke-width="1"/>'],
        'compass-rose' => ['Compass Rose', PatternGroup::Travel, 48, 48,
            '<path d="M24 6L28 20L42 24L28 28L24 42L20 28L6 24L20 20Z" fill="none" stroke="{{color}}" stroke-width="1.2"/>'
            . '<circle cx="24" cy="24" r="2" fill="{{color}}"/>'],
    ];

    /**
     * @return list<Pattern>
     */
    public static function all(): array
    {
        $patterns = [];

        foreach (self::TILES as $slug => $tile) {
            $patterns[] = self::build($slug, $tile);
        }

        return $patterns;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::TILES);
    }

    public static function has(string $slug): bool
    {
        return array_key_exists($slug, self::TILES);
    }

    /**
     * @throws InvalidPatternException when the slug is not known
     */
    public static function get(string $slug): Pattern
    {
        if (!array_key_exists($slug, self::TILES)) {
            throw InvalidPatternException::forUnknownSlug($slug);
        }

        return self::build($slug, self::TILES[$slug]);
    }

    /**
     * @return list<Pattern>
     */
    public static function inGroup(PatternGroup $group): array
    {
        $patterns = [];

        foreach (self::TILES as $slug => $tile) {
            if ($tile[1] === $group) {
                $patterns[] = self::build($slug, $tile);
            }
        }

        return $patterns;
    }

    /**
     * @param array{string, PatternGroup, int, int, string} $tile
     */
    private static function build(string $slug, array $tile): Pattern
    {
        [$name, $group, $width, $height, $body] = $tile;

        return Pattern::of($slug, $name, $group, sprintf(self::OPEN, $width, $height) . $body . '</svg>');
    }
}
