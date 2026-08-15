<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\Srgb;

/**
 * A section with every value settled: nothing here is nullable, and nothing still needs to
 * look at the global settings.
 */
final class ResolvedSection
{
    private function __construct(
        public readonly SectionId $id,
        public readonly bool $isEnabled,
        public readonly Srgb $accentSeed,
        public readonly SectionBackgroundTone $backgroundTone,
        public readonly PatternSettings $pattern,
        public readonly MotionSettings $motion,
        public readonly TypographySettings $typography,
        public readonly int $sectionSpacingPixels,
        public readonly int $cornerRadiusPixels,
    ) {
    }

    public static function of(
        SectionId $id,
        Srgb $accentSeed,
        SectionBackgroundTone $backgroundTone,
        PatternSettings $pattern,
        MotionSettings $motion,
        TypographySettings $typography,
        int $sectionSpacingPixels,
        int $cornerRadiusPixels,
        bool $isEnabled = true
    ): self {
        return new self(
            $id,
            $isEnabled,
            $accentSeed,
            $backgroundTone,
            $pattern,
            $motion,
            $typography,
            $sectionSpacingPixels,
            $cornerRadiusPixels,
        );
    }

    /**
     * A flat comparable form, used by tests and by the admin's modified indicator to tell
     * two resolutions apart without reaching into every sub-object.
     *
     * @return array<string, mixed>
     */
    public function toComparableArray(): array
    {
        return [
            'isEnabled' => $this->isEnabled,
            'accentSeed' => $this->accentSeed->toHex(),
            'backgroundTone' => $this->backgroundTone->value,
            'pattern' => $this->pattern->toArray(),
            'motion' => $this->motion->toArray(),
            'typography' => $this->typography->toArray(),
            'sectionSpacingPixels' => $this->sectionSpacingPixels,
            'cornerRadiusPixels' => $this->cornerRadiusPixels,
        ];
    }
}
