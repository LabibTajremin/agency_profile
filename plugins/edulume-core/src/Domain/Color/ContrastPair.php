<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Color;

/**
 * A background and the foreground drawn on it, together with their WCAG contrast ratio.
 */
final class ContrastPair
{
    private const LUMINANCE_OFFSET = 0.05;

    private function __construct(
        public readonly Srgb $background,
        public readonly Srgb $foreground,
        public readonly float $ratio,
    ) {
    }

    public static function of(Srgb $background, Srgb $foreground): self
    {
        return new self($background, $foreground, self::computeRatio($background, $foreground));
    }

    public function meets(ContrastRequirement $requirement): bool
    {
        return $requirement->isSatisfiedBy($this->ratio);
    }

    private static function computeRatio(Srgb $background, Srgb $foreground): float
    {
        $backgroundLuminance = $background->relativeLuminance();
        $foregroundLuminance = $foreground->relativeLuminance();

        $lighter = max($backgroundLuminance, $foregroundLuminance);
        $darker = min($backgroundLuminance, $foregroundLuminance);

        return ($lighter + self::LUMINANCE_OFFSET) / ($darker + self::LUMINANCE_OFFSET);
    }
}
