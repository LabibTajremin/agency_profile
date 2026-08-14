<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Color;

/**
 * A colour in the cylindrical OKLCH space: perceptual lightness, chroma, and hue in degrees.
 *
 * This is the space every palette decision is made in. Ramping lightness here keeps hue and
 * saturation perceptually stable, which naive HSL ramping does not.
 */
final class Oklch
{
    public const DEGREES_IN_TURN = 360.0;

    private function __construct(
        public readonly float $lightness,
        public readonly float $chroma,
        public readonly float $hue,
    ) {
    }

    public static function fromComponents(float $lightness, float $chroma, float $hue): self
    {
        return new self(
            max(0.0, min(1.0, $lightness)),
            max(0.0, $chroma),
            self::normaliseHue($hue),
        );
    }

    public function toOklab(): Oklab
    {
        $radians = deg2rad($this->hue);

        return Oklab::fromComponents(
            $this->lightness,
            $this->chroma * cos($radians),
            $this->chroma * sin($radians),
        );
    }

    public function withLightness(float $lightness): self
    {
        return self::fromComponents($lightness, $this->chroma, $this->hue);
    }

    public function withChroma(float $chroma): self
    {
        return self::fromComponents($this->lightness, $chroma, $this->hue);
    }

    public static function normaliseHue(float $hue): float
    {
        $wrapped = fmod($hue, self::DEGREES_IN_TURN);

        return $wrapped < 0.0 ? $wrapped + self::DEGREES_IN_TURN : $wrapped;
    }
}
