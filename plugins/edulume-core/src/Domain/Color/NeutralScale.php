<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Color;

/**
 * An eleven-step scale of designed neutral inks, subtly tinted toward the accent hue.
 *
 * The extremes are deliberately not `#ffffff` and `#000000`. Pure white glares under the
 * warm lighting most people read on a phone under, and pure black on an OLED panel smears
 * text edges. Every premium design system ships designed inks instead; so does this one.
 */
final class NeutralScale
{
    public const STEP_COUNT = 11;

    private const LIGHTNESS_RAMP = [0.985, 0.964, 0.928, 0.874, 0.800, 0.703, 0.588, 0.478, 0.372, 0.276, 0.192];
    private const CHROMA_RAMP = [0.0026, 0.0038, 0.0052, 0.0068, 0.0084, 0.0100, 0.0112, 0.0120, 0.0124, 0.0118, 0.0104];

    private const DEFAULT_TINT_STRENGTH = 1.0;
    private const LIGHTEST_STEP = 0;
    private const DARKEST_STEP = self::STEP_COUNT - 1;

    /**
     * @param list<Srgb> $steps
     */
    private function __construct(
        public readonly float $hue,
        public readonly float $tintStrength,
        private readonly array $steps,
    ) {
    }

    public static function fromHue(float $hue, float $tintStrength = self::DEFAULT_TINT_STRENGTH): self
    {
        $normalisedHue = Oklch::normaliseHue($hue);
        $clampedTint = max(0.0, min(1.0, $tintStrength));

        $steps = [];

        for ($index = 0; $index < self::STEP_COUNT; $index++) {
            $steps[] = ColorSpace::oklchToSrgb(Oklch::fromComponents(
                self::LIGHTNESS_RAMP[$index],
                self::CHROMA_RAMP[$index] * $clampedTint,
                $normalisedHue,
            ));
        }

        return new self($normalisedHue, $clampedTint, $steps);
    }

    public static function fromAccent(Srgb $accent, float $tintStrength = self::DEFAULT_TINT_STRENGTH): self
    {
        return self::fromHue(ColorSpace::srgbToOklch($accent)->hue, $tintStrength);
    }

    /**
     * @throws InvalidColorException when the step does not exist
     */
    public function step(int $index): Srgb
    {
        if (!array_key_exists($index, $this->steps)) {
            throw InvalidColorException::forStepIndex($index, self::STEP_COUNT);
        }

        return $this->steps[$index];
    }

    public function lightestInk(): Srgb
    {
        return $this->step(self::LIGHTEST_STEP);
    }

    public function darkestInk(): Srgb
    {
        return $this->step(self::DARKEST_STEP);
    }

    /**
     * @return list<Srgb>
     */
    public function steps(): array
    {
        return $this->steps;
    }
}
