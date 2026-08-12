<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\ColorSpace;
use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\ContrastRequirement;
use Edulume\Core\Domain\Color\NeutralScale;
use Edulume\Core\Domain\Color\Oklch;
use Edulume\Core\Domain\Color\Srgb;

/**
 * Builds an eleven-step accent palette from a single seed colour.
 *
 * The ramp runs along the OKLCH lightness axis with a chroma curve that peaks in the middle
 * and tapers at both ends. Ramping in HSL instead — the usual shortcut — swings hue and
 * perceived saturation wildly between steps and is why so many accent-driven themes look
 * muddy at the light end and neon at the dark end.
 */
final class PaletteGenerator
{
    private const LIGHTNESS_RAMP = [0.971, 0.936, 0.885, 0.822, 0.744, 0.657, 0.567, 0.478, 0.396, 0.318, 0.246];
    private const CHROMA_SHAPE = [0.16, 0.28, 0.44, 0.62, 0.79, 0.93, 1.00, 0.99, 0.92, 0.80, 0.66];

    private const FOREGROUND_REQUIREMENT = ContrastRequirement::NormalTextAa;
    private const TEXT_REQUIREMENT = ContrastRequirement::NormalTextAa;

    private const LIGHT_SURFACE_STEP = 0;
    private const DARK_SURFACE_STEP = NeutralScale::STEP_COUNT - 1;

    public function __construct(private readonly ContrastEngine $contrastEngine)
    {
    }

    public function generate(Srgb $seed): AccentPalette
    {
        $seedInOklch = ColorSpace::srgbToOklch($seed);
        $neutrals = NeutralScale::fromHue($seedInOklch->hue);

        $steps = $this->rampSteps($seedInOklch);
        $foregrounds = array_map(fn (Srgb $step): Srgb => $this->foregroundFor($step, $neutrals), $steps);

        return AccentPalette::fromSteps($steps, $foregrounds, [
            ThemeMode::Light->value => $this->textFor($steps, $neutrals->step(self::LIGHT_SURFACE_STEP), false),
            ThemeMode::Dark->value => $this->textFor($steps, $neutrals->step(self::DARK_SURFACE_STEP), true),
        ]);
    }

    /**
     * @return list<Srgb>
     */
    private function rampSteps(Oklch $seed): array
    {
        $steps = [];

        for ($index = 0; $index < AccentPalette::STEP_COUNT; $index++) {
            $steps[] = ColorSpace::oklchToSrgb(Oklch::fromComponents(
                self::LIGHTNESS_RAMP[$index],
                $seed->chroma * self::CHROMA_SHAPE[$index],
                $seed->hue,
            ));
        }

        return $steps;
    }

    /**
     * The tinted inks are tried first so the foreground still belongs to the palette. When
     * neither clears AA — which happens across the middle of every ramp, where no designed
     * ink can reach 4.5:1 — the better ink is pushed along its lightness axis until it does.
     */
    private function foregroundFor(Srgb $step, NeutralScale $neutrals): Srgb
    {
        $candidates = [$neutrals->darkestInk(), $neutrals->lightestInk()];

        $chosen = $this->contrastEngine->pickForeground($step, $candidates, self::FOREGROUND_REQUIREMENT);

        return $this->contrastEngine->nearestCompliantForeground(
            $step,
            $chosen->foreground,
            self::FOREGROUND_REQUIREMENT,
        );
    }

    /**
     * Picks the most vivid accent step that is still readable as text on the mode's surface,
     * scanning inward from the end of the ramp that suits the surface. The surface ink closes
     * the candidate list, so a compliant answer always exists.
     *
     * @param list<Srgb> $steps
     */
    private function textFor(array $steps, Srgb $surface, bool $scanFromDarkEnd): Srgb
    {
        $ordered = $scanFromDarkEnd ? array_reverse($steps) : $steps;
        $ordered[] = $this->contrastEngine->nearestCompliantForeground(
            $surface,
            $surface,
            self::TEXT_REQUIREMENT,
        );

        return $this->contrastEngine->pickForeground($surface, $ordered, self::TEXT_REQUIREMENT)->foreground;
    }
}
