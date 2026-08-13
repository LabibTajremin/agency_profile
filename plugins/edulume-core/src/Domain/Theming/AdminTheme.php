<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\ColorSpace;
use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\ContrastPair;
use Edulume\Core\Domain\Color\ContrastRequirement;
use Edulume\Core\Domain\Color\NeutralScale;
use Edulume\Core\Domain\Color\Srgb;

/**
 * The admin's own palette, derived from the site accent.
 *
 * The admin is themed by the site's accent so the configurator looks like the site it
 * configures — but its light/dark choice is its own. Someone editing at night wants a dark
 * admin whether or not the public site offers dark mode, and someone whose site is dark by
 * default still deserves a light admin if that is what they prefer.
 *
 * Every colour here is put through the contrast engine rather than picked by eye. An admin
 * that fails a contrast audit on eleven of the twenty-four accents is an admin nobody notices
 * is broken until a client does.
 */
final class AdminTheme
{
    public const PREFIX = '--edulume-admin-';

    private const REQUIREMENT = ContrastRequirement::NormalTextAa;
    private const CONTROL_REQUIREMENT = ContrastRequirement::NonText;

    private const SURFACE_STEP_LIGHT = 0;
    private const RAISED_SURFACE_STEP_LIGHT = 1;
    private const BORDER_STEP_LIGHT = 3;
    private const MUTED_INK_STEP_LIGHT = 7;
    private const INK_STEP_LIGHT = 10;

    private const SURFACE_STEP_DARK = 10;
    private const RAISED_SURFACE_STEP_DARK = 9;
    private const BORDER_STEP_DARK = 7;
    private const MUTED_INK_STEP_DARK = 4;
    private const INK_STEP_DARK = 0;

    public function __construct(
        private readonly PaletteGenerator $paletteGenerator,
        private readonly ContrastEngine $contrastEngine,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function compile(Srgb $accentSeed, ThemeMode $adminMode): array
    {
        $palette = $this->paletteGenerator->generate($accentSeed);
        $neutrals = NeutralScale::fromHue(ColorSpace::srgbToOklch($accentSeed)->hue);
        $isLight = $adminMode === ThemeMode::Light;

        $surface = $neutrals->step($isLight ? self::SURFACE_STEP_LIGHT : self::SURFACE_STEP_DARK);
        $raised = $neutrals->step($isLight ? self::RAISED_SURFACE_STEP_LIGHT : self::RAISED_SURFACE_STEP_DARK);

        return [
            self::PREFIX . 'surface' => $surface->toHex(),
            self::PREFIX . 'surface-raised' => $raised->toHex(),
            self::PREFIX . 'border' => $neutrals->step($isLight ? self::BORDER_STEP_LIGHT : self::BORDER_STEP_DARK)->toHex(),
            self::PREFIX . 'ink' => $neutrals->step($isLight ? self::INK_STEP_LIGHT : self::INK_STEP_DARK)->toHex(),
            self::PREFIX . 'ink-muted' => $this->legibleMutedInk($neutrals, $surface, $isLight)->toHex(),
            self::PREFIX . 'accent' => $palette->fill($adminMode)->toHex(),
            self::PREFIX . 'on-accent' => $palette->onFill($adminMode)->toHex(),
            self::PREFIX . 'accent-text' => $this->legibleAccentText($palette, $surface, $adminMode)->toHex(),
            self::PREFIX . 'focus-ring' => $this->legibleFocusRing($palette, $surface, $adminMode)->toHex(),
        ];
    }

    /**
     * The pair a contrast audit would check first: body text on the admin's own surface.
     */
    public function bodyTextContrast(Srgb $accentSeed, ThemeMode $adminMode): ContrastPair
    {
        $tokens = $this->compile($accentSeed, $adminMode);

        return ContrastPair::of(
            Srgb::fromHex($tokens[self::PREFIX . 'surface']),
            Srgb::fromHex($tokens[self::PREFIX . 'ink']),
        );
    }

    /**
     * @return array<string, ContrastPair>
     */
    public function auditPairs(Srgb $accentSeed, ThemeMode $adminMode): array
    {
        $tokens = $this->compile($accentSeed, $adminMode);
        $surface = Srgb::fromHex($tokens[self::PREFIX . 'surface']);
        $raised = Srgb::fromHex($tokens[self::PREFIX . 'surface-raised']);
        $accent = Srgb::fromHex($tokens[self::PREFIX . 'accent']);

        return [
            'ink on surface' => ContrastPair::of($surface, Srgb::fromHex($tokens[self::PREFIX . 'ink'])),
            'ink on raised surface' => ContrastPair::of($raised, Srgb::fromHex($tokens[self::PREFIX . 'ink'])),
            'muted ink on surface' => ContrastPair::of($surface, Srgb::fromHex($tokens[self::PREFIX . 'ink-muted'])),
            'accent text on surface' => ContrastPair::of($surface, Srgb::fromHex($tokens[self::PREFIX . 'accent-text'])),
            'label on accent' => ContrastPair::of($accent, Srgb::fromHex($tokens[self::PREFIX . 'on-accent'])),
        ];
    }

    /**
     * The focus ring is judged against the non-text threshold, which is what WCAG 1.4.11
     * actually asks of a UI component boundary — and a focus ring that fails it is a keyboard
     * user losing their place.
     */
    public function focusRingContrast(Srgb $accentSeed, ThemeMode $adminMode): ContrastPair
    {
        $tokens = $this->compile($accentSeed, $adminMode);

        return ContrastPair::of(
            Srgb::fromHex($tokens[self::PREFIX . 'surface']),
            Srgb::fromHex($tokens[self::PREFIX . 'focus-ring']),
        );
    }

    public function meetsContrastRequirements(Srgb $accentSeed, ThemeMode $adminMode): bool
    {
        foreach ($this->auditPairs($accentSeed, $adminMode) as $pair) {
            if (!$pair->meets(self::REQUIREMENT)) {
                return false;
            }
        }

        return $this->focusRingContrast($accentSeed, $adminMode)->meets(self::CONTROL_REQUIREMENT);
    }

    private function legibleMutedInk(NeutralScale $neutrals, Srgb $surface, bool $isLight): Srgb
    {
        $candidate = $neutrals->step($isLight ? self::MUTED_INK_STEP_LIGHT : self::MUTED_INK_STEP_DARK);

        return $this->contrastEngine->nearestCompliantForeground($surface, $candidate, self::REQUIREMENT);
    }

    private function legibleAccentText(AccentPalette $palette, Srgb $surface, ThemeMode $adminMode): Srgb
    {
        return $this->contrastEngine->nearestCompliantForeground(
            $surface,
            $palette->text($adminMode),
            self::REQUIREMENT,
        );
    }

    private function legibleFocusRing(AccentPalette $palette, Srgb $surface, ThemeMode $adminMode): Srgb
    {
        return $this->contrastEngine->nearestCompliantForeground(
            $surface,
            $palette->fill($adminMode),
            self::CONTROL_REQUIREMENT,
        );
    }
}
