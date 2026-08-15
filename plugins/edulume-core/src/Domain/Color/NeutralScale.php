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

    /** Deep blue, not navy: dark enough to sit under body text at AA. */
    public const MIDNIGHT_HUE = 258.0;

    /** Warm off-white. Paper, not printer paper. */
    public const CREAM_HUE = 82.0;

    private const MIDNIGHT_LIGHTNESS_RAMP =
        [0.955, 0.918, 0.862, 0.792, 0.712, 0.624, 0.532, 0.437, 0.351, 0.276, 0.215];
    private const MIDNIGHT_CHROMA_RAMP =
        [0.0240, 0.0230, 0.0200, 0.0150, 0.0090, 0.0120, 0.0190, 0.0260, 0.0330, 0.0380, 0.0360];
    private const MIDNIGHT_HUE_RAMP =
        [82.0, 82.0, 84.0, 88.0, 120.0, 250.0, 254.0, 256.0, 257.0, 258.0, 258.0];

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
     * The dark-mode scale: midnight blue at the dark end, cream at the light end.
     *
     * Two problems with using one hue for both ends of a neutral ramp, and this fixes both.
     *
     * Dark mode built from the accent-tinted scale bottomed out at roughly `#111519` — a black
     * with a hint of blue in it, which on an OLED panel reads as plain black and, next to a
     * pure-white ink, produces the glare-and-halo effect that makes dark mode tiring to read.
     * The dark end here sits at L 0.215 with real chroma, so it is unmistakably a deep blue.
     *
     * The light end is warm rather than cool, so dark-mode text lands as cream instead of white.
     * That is not decoration: a warm off-white at slightly under full lightness cuts the
     * simultaneous-contrast halo against a dark ground, which is why print has used cream stock
     * for two centuries.
     *
     * The hue ramp jumps rather than sweeps. Interpolating 82° to 258° passes through green, and
     * a neutral scale with a green rung in the middle is a bug you notice only on a large flat
     * surface. Chroma is at its lowest across the jump, so the step is invisible.
     */
    public static function midnight(float $tintStrength = self::DEFAULT_TINT_STRENGTH): self
    {
        $clampedTint = max(0.0, min(1.0, $tintStrength));
        $steps = [];

        for ($index = 0; $index < self::STEP_COUNT; $index++) {
            $steps[] = ColorSpace::oklchToSrgb(Oklch::fromComponents(
                self::MIDNIGHT_LIGHTNESS_RAMP[$index],
                self::MIDNIGHT_CHROMA_RAMP[$index] * $clampedTint,
                self::MIDNIGHT_HUE_RAMP[$index],
            ));
        }

        return new self(self::MIDNIGHT_HUE, $clampedTint, $steps);
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
