<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * Everything a section may override.
 *
 * Naming the overridable surface as an enum is what lets the admin show an accurate
 * modified-indicator dot and offer a per-key revert, without either side keeping its own
 * hand-maintained list of field names.
 */
enum SectionOverrideKey: string
{
    case AccentSlug = 'accentSlug';
    case CustomAccent = 'customAccent';
    case PatternEnabled = 'patternEnabled';
    case PatternSlug = 'patternSlug';
    case PatternLightModeOpacity = 'patternLightModeOpacity';
    case PatternDarkModeOpacity = 'patternDarkModeOpacity';
    case PatternScale = 'patternScale';
    case BackgroundTone = 'backgroundTone';
    case MotionEnabled = 'motionEnabled';
    case MotionPreset = 'motionPreset';
    case TypographyPairingSlug = 'typographyPairingSlug';
    case SectionSpacingPixels = 'sectionSpacingPixels';
    case CornerRadiusPixels = 'cornerRadiusPixels';
}
