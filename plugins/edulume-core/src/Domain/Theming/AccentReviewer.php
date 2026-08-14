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
 * Judges a custom accent hex before it is accepted, on both page surfaces.
 */
final class AccentReviewer
{
    private const REQUIREMENT = ContrastRequirement::NormalTextAa;
    private const LIGHT_SURFACE_STEP = 0;
    private const DARK_SURFACE_STEP = NeutralScale::STEP_COUNT - 1;

    public function __construct(private readonly ContrastEngine $contrastEngine)
    {
    }

    public function review(Srgb $seed): AccentReview
    {
        $neutrals = NeutralScale::fromHue(ColorSpace::srgbToOklch($seed)->hue);

        $onLightSurface = ContrastPair::of($neutrals->step(self::LIGHT_SURFACE_STEP), $seed);
        $onDarkSurface = ContrastPair::of($neutrals->step(self::DARK_SURFACE_STEP), $seed);

        return AccentReview::of(
            $seed,
            $onLightSurface,
            $onDarkSurface,
            $this->suggestionFor($onLightSurface),
            $this->suggestionFor($onDarkSurface),
        );
    }

    private function suggestionFor(ContrastPair $pair): ?Srgb
    {
        if ($pair->meets(self::REQUIREMENT)) {
            return null;
        }

        return $this->contrastEngine->nearestCompliantForeground(
            $pair->background,
            $pair->foreground,
            self::REQUIREMENT,
        );
    }
}
