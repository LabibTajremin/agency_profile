<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * How a pattern is applied: per-mode opacity, scale, tint source, rotation, blend and
 * attachment.
 *
 * Light and dark carry separate opacities on purpose. The same value that whispers on a
 * white page is invisible on a near-black one, so a single opacity always looks wrong in
 * one of the two modes.
 *
 * Every value coerces rather than throws. These arrive from the REST API, from imported
 * JSON, and from rows written by an older schema; one bad key must never white-screen a
 * live site.
 */
final class PatternSettings
{
    public const DEFAULT_PATTERN_SLUG = 'dot-grid';

    public const MINIMUM_SCALE = 0.5;
    public const MAXIMUM_SCALE = 3.0;

    private const DEFAULT_LIGHT_MODE_OPACITY = 0.06;
    private const DEFAULT_DARK_MODE_OPACITY = 0.10;
    private const DEFAULT_SCALE = 1.0;
    private const DEFAULT_ROTATION = 0;

    private const DEGREES_IN_TURN = 360;

    private function __construct(
        public readonly bool $enabled,
        public readonly string $patternSlug,
        public readonly float $lightModeOpacity,
        public readonly float $darkModeOpacity,
        public readonly float $scale,
        public readonly PatternColorSource $colorSource,
        public readonly int $rotation,
        public readonly PatternBlendMode $blendMode,
        public readonly PatternAttachment $attachment,
    ) {
    }

    public static function disabled(): self
    {
        return new self(
            false,
            self::DEFAULT_PATTERN_SLUG,
            self::DEFAULT_LIGHT_MODE_OPACITY,
            self::DEFAULT_DARK_MODE_OPACITY,
            self::DEFAULT_SCALE,
            PatternColorSource::Accent,
            self::DEFAULT_ROTATION,
            PatternBlendMode::Normal,
            PatternAttachment::Scroll,
        );
    }

    public static function defaultsFor(string $patternSlug): self
    {
        return new self(
            true,
            self::coerceSlug($patternSlug),
            self::DEFAULT_LIGHT_MODE_OPACITY,
            self::DEFAULT_DARK_MODE_OPACITY,
            self::DEFAULT_SCALE,
            PatternColorSource::Accent,
            self::DEFAULT_ROTATION,
            PatternBlendMode::Normal,
            PatternAttachment::Scroll,
        );
    }

    public static function of(
        string $patternSlug,
        float $lightModeOpacity,
        float $darkModeOpacity,
        float $scale,
        PatternColorSource $colorSource,
        int $rotation,
        PatternBlendMode $blendMode,
        PatternAttachment $attachment
    ): self {
        return new self(
            true,
            self::coerceSlug($patternSlug),
            self::coerceOpacity($lightModeOpacity),
            self::coerceOpacity($darkModeOpacity),
            self::coerceScale($scale),
            $colorSource,
            self::coerceRotation($rotation),
            $blendMode,
            $attachment,
        );
    }

    public function opacityFor(ThemeMode $mode): float
    {
        return $mode === ThemeMode::Light ? $this->lightModeOpacity : $this->darkModeOpacity;
    }

    public function pattern(): Pattern
    {
        return PatternLibrary::get($this->patternSlug);
    }

    private static function coerceSlug(string $patternSlug): string
    {
        return PatternLibrary::has($patternSlug) ? $patternSlug : self::DEFAULT_PATTERN_SLUG;
    }

    private static function coerceOpacity(float $opacity): float
    {
        return max(0.0, min(1.0, $opacity));
    }

    private static function coerceScale(float $scale): float
    {
        return max(self::MINIMUM_SCALE, min(self::MAXIMUM_SCALE, $scale));
    }

    private static function coerceRotation(int $rotation): int
    {
        $wrapped = $rotation % self::DEGREES_IN_TURN;

        return $wrapped < 0 ? $wrapped + self::DEGREES_IN_TURN : $wrapped;
    }
}
