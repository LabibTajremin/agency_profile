<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Color;

/**
 * A colour in the OKLab perceptual space.
 *
 * `lightness` runs 0–1. The two opponent axes are unbounded in principle but sit
 * within roughly ±0.4 for colours that exist inside the sRGB gamut.
 */
final class Oklab
{
    private const DEGREES_IN_TURN = 360.0;
    private const ACHROMATIC_CHROMA_EPSILON = 1.0e-9;

    private function __construct(
        public readonly float $lightness,
        public readonly float $greenRedAxis,
        public readonly float $blueYellowAxis,
    ) {
    }

    public static function fromComponents(float $lightness, float $greenRedAxis, float $blueYellowAxis): self
    {
        return new self($lightness, $greenRedAxis, $blueYellowAxis);
    }

    public function toOklch(): Oklch
    {
        $chroma = sqrt($this->greenRedAxis ** 2 + $this->blueYellowAxis ** 2);

        if ($chroma < self::ACHROMATIC_CHROMA_EPSILON) {
            return Oklch::fromComponents($this->lightness, 0.0, 0.0);
        }

        $hue = rad2deg(atan2($this->blueYellowAxis, $this->greenRedAxis));

        return Oklch::fromComponents($this->lightness, $chroma, fmod($hue + self::DEGREES_IN_TURN, self::DEGREES_IN_TURN));
    }
}
