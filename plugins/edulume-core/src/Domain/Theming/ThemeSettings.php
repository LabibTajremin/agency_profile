<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Support\Guard;

/**
 * The whole theme configuration as one aggregate, carrying its own schema version.
 *
 * `fromArray()` never throws. The blob it is handed may come from the REST API, from a JSON
 * file a client exported eighteen months ago, or from a row written by a version that did not
 * have half these keys. Every path coerces to a valid default instead.
 */
final class ThemeSettings
{
    public const CURRENT_SCHEMA_VERSION = 2;

    public const DEFAULT_ACCENT_SLUG = 'oxford-blue';

    private function __construct(
        public readonly string $accentSlug,
        public readonly ?Srgb $customAccent,
        public readonly ThemePreference $themePreference,
        public readonly bool $offersModeToggle,
        public readonly TypographySettings $typography,
        public readonly PatternSettings $pattern,
        public readonly MotionSettings $motion,
        public readonly LayoutSettings $layout,
    ) {
    }

    public static function defaults(): self
    {
        return new self(
            self::DEFAULT_ACCENT_SLUG,
            null,
            ThemePreference::Auto,
            true,
            TypographySettings::defaults(),
            PatternSettings::defaultsFor(PatternSettings::DEFAULT_PATTERN_SLUG),
            MotionSettings::defaults(),
            LayoutSettings::defaults(),
        );
    }

    public static function of(
        string $accentSlug,
        ?Srgb $customAccent,
        ThemePreference $themePreference,
        bool $offersModeToggle,
        TypographySettings $typography,
        PatternSettings $pattern,
        MotionSettings $motion,
        LayoutSettings $layout
    ): self {
        return new self(
            AccentLibrary::has($accentSlug) ? $accentSlug : self::DEFAULT_ACCENT_SLUG,
            $customAccent,
            $themePreference,
            $offersModeToggle,
            $typography,
            $pattern,
            $motion,
            $layout,
        );
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $defaults = self::defaults();

        return self::of(
            Guard::toString($stored['accentSlug'] ?? null, $defaults->accentSlug),
            self::coerceCustomAccent($stored['customAccent'] ?? null),
            Guard::toEnum(ThemePreference::class, $stored['themePreference'] ?? null, $defaults->themePreference),
            Guard::toBool($stored['offersModeToggle'] ?? null, $defaults->offersModeToggle),
            TypographySettings::fromArray(Guard::toArray($stored['typography'] ?? null)),
            PatternSettings::fromArray(Guard::toArray($stored['pattern'] ?? null)),
            MotionSettings::fromArray(Guard::toArray($stored['motion'] ?? null)),
            LayoutSettings::fromArray(Guard::toArray($stored['layout'] ?? null)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schemaVersion' => self::CURRENT_SCHEMA_VERSION,
            'accentSlug' => $this->accentSlug,
            'customAccent' => $this->customAccent?->toHex(),
            'themePreference' => $this->themePreference->value,
            'offersModeToggle' => $this->offersModeToggle,
            'typography' => $this->typography->toArray(),
            'pattern' => $this->pattern->toArray(),
            'motion' => $this->motion->toArray(),
            'layout' => $this->layout->toArray(),
        ];
    }

    /**
     * The colour every palette is generated from: the custom hex when one is set, otherwise
     * the curated accent's seed.
     */
    public function accentSeed(): Srgb
    {
        return $this->customAccent ?? AccentLibrary::get($this->accentSlug)->seed;
    }

    public function withCustomAccent(?Srgb $customAccent): self
    {
        return new self(
            $this->accentSlug,
            $customAccent,
            $this->themePreference,
            $this->offersModeToggle,
            $this->typography,
            $this->pattern,
            $this->motion,
            $this->layout,
        );
    }

    public function withMotion(MotionSettings $motion): self
    {
        return new self(
            $this->accentSlug,
            $this->customAccent,
            $this->themePreference,
            $this->offersModeToggle,
            $this->typography,
            $this->pattern,
            $motion,
            $this->layout,
        );
    }

    private static function coerceCustomAccent(mixed $stored): ?Srgb
    {
        if (!is_string($stored) || trim($stored) === '') {
            return null;
        }

        try {
            return Srgb::fromHex($stored);
        } catch (\Edulume\Core\Domain\Color\InvalidColorException) {
            return null;
        }
    }
}
