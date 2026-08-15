<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\InvalidColorException;
use Edulume\Core\Domain\Color\Srgb;

/**
 * Folds the global theme settings together with a section's sparse override into a section
 * where every value is settled.
 *
 * An override that sets nothing must resolve identically to the global settings, field for
 * field. That property is what makes inheritance-by-absence safe to rely on everywhere else.
 */
final class SectionResolver
{
    private const DEFAULT_BACKGROUND_TONE = SectionBackgroundTone::Surface;

    public function resolve(SectionId $id, ThemeSettings $global, SectionOverride $override): ResolvedSection
    {
        return ResolvedSection::of(
            $id,
            $this->resolveAccentSeed($global, $override),
            $override->enumOr(
                SectionOverrideKey::BackgroundTone,
                SectionBackgroundTone::class,
                self::DEFAULT_BACKGROUND_TONE,
            ),
            $this->resolvePattern($global, $override),
            $this->resolveMotion($global, $override),
            $this->resolveTypography($global, $override),
            $override->intOr(SectionOverrideKey::SectionSpacingPixels, $global->layout->sectionSpacingPixels),
            $override->intOr(SectionOverrideKey::CornerRadiusPixels, $global->layout->cornerRadiusPixels),
            $this->resolveEnabled($id, $override),
        );
    }

    /**
     * Sections are on unless a stored override says otherwise.
     *
     * Defaulting to on matters for upgrades: a site that has never opened the panel has no
     * stored value for any section, and defaulting to off would empty every homepage on the
     * release that introduced this.
     *
     * The header and footer ignore the override entirely rather than hiding the control and
     * trusting the UI. A value can reach this from imported JSON or a REST call that never went
     * near the admin, and the answer has to be the same wherever it came from.
     */
    private function resolveEnabled(SectionId $id, SectionOverride $override): bool
    {
        if (!$id->canBeSwitchedOff()) {
            return true;
        }

        return $override->boolOr(SectionOverrideKey::Enabled, $id->isEnabledByDefault());
    }

    private function resolveAccentSeed(ThemeSettings $global, SectionOverride $override): Srgb
    {
        if ($override->has(SectionOverrideKey::CustomAccent)) {
            $custom = $this->parseHex($override->stringOr(SectionOverrideKey::CustomAccent, ''));

            if ($custom !== null) {
                return $custom;
            }
        }

        $accentSlug = $override->stringOr(SectionOverrideKey::AccentSlug, '');

        if ($accentSlug !== '' && AccentLibrary::has($accentSlug)) {
            return AccentLibrary::get($accentSlug)->seed;
        }

        return $global->accentSeed();
    }

    private function resolvePattern(ThemeSettings $global, SectionOverride $override): PatternSettings
    {
        $inherited = $global->pattern;

        $resolved = PatternSettings::of(
            $override->stringOr(SectionOverrideKey::PatternSlug, $inherited->patternSlug),
            $override->floatOr(SectionOverrideKey::PatternLightModeOpacity, $inherited->lightModeOpacity),
            $override->floatOr(SectionOverrideKey::PatternDarkModeOpacity, $inherited->darkModeOpacity),
            $override->floatOr(SectionOverrideKey::PatternScale, $inherited->scale),
            $inherited->colorSource,
            $inherited->rotation,
            $inherited->blendMode,
            $inherited->attachment,
        );

        return $override->boolOr(SectionOverrideKey::PatternEnabled, $inherited->enabled)
            ? $resolved
            : $resolved->switchedOff();
    }

    private function resolveMotion(ThemeSettings $global, SectionOverride $override): MotionSettings
    {
        $inherited = $global->motion;

        $preset = $override->enumOr(SectionOverrideKey::MotionPreset, MotionPreset::class, $inherited->preset);
        $enabled = $override->boolOr(SectionOverrideKey::MotionEnabled, $inherited->enabled);

        if ($preset === $inherited->preset && $enabled === $inherited->enabled) {
            return $inherited;
        }

        $resolved = $inherited->withPreset($preset);

        return $enabled ? $resolved : $resolved->disabled();
    }

    private function resolveTypography(ThemeSettings $global, SectionOverride $override): TypographySettings
    {
        $inherited = $global->typography;
        $pairingSlug = $override->stringOr(SectionOverrideKey::TypographyPairingSlug, $inherited->pairingSlug);

        if ($pairingSlug === $inherited->pairingSlug) {
            return $inherited;
        }

        $stored = $inherited->toArray();
        $stored['pairingSlug'] = $pairingSlug;

        return TypographySettings::fromArray($stored);
    }

    private function parseHex(string $hex): ?Srgb
    {
        if (trim($hex) === '') {
            return null;
        }

        try {
            return Srgb::fromHex($hex);
        } catch (InvalidColorException) {
            return null;
        }
    }
}
