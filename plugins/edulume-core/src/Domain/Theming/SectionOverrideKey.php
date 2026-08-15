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
    /**
     * Whether the section renders at all.
     *
     * First in the list because it is the one override that makes the rest moot: a section
     * switched off has no accent, no pattern and no spacing worth resolving.
     */
    case Enabled = 'enabled';

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
