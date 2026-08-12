<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Color;

/**
 * Answers the three contrast questions the rest of the product asks:
 * what is the ratio, which candidate should sit on this background, and — when nothing
 * complies — what is the nearest colour that would.
 */
final class ContrastEngine
{
    private const LIGHTNESS_SEARCH_ITERATIONS = 24;
    private const DARKEST_LIGHTNESS = 0.0;
    private const LIGHTEST_LIGHTNESS = 1.0;

    public function ratio(Srgb $background, Srgb $foreground): float
    {
        return ContrastPair::of($background, $foreground)->ratio;
    }

    public function meets(Srgb $background, Srgb $foreground, ContrastRequirement $requirement): bool
    {
        return ContrastPair::of($background, $foreground)->meets($requirement);
    }

    /**
     * Returns the first candidate that satisfies the requirement, falling back to the
     * highest-contrast candidate when none does. Order carries design intent, so a
     * compliant early candidate always wins over a higher-contrast later one.
     *
     * @param non-empty-list<Srgb> $candidates
     *
     * @throws InvalidColorException when the candidate list is empty
     */
    public function pickForeground(Srgb $background, array $candidates, ContrastRequirement $requirement): ContrastPair
    {
        if ($candidates === []) {
            throw InvalidColorException::forEmptyCandidateList();
        }

        $best = null;

        foreach ($candidates as $candidate) {
            $pair = ContrastPair::of($background, $candidate);

            if ($pair->meets($requirement)) {
                return $pair;
            }

            if ($best === null || $pair->ratio > $best->ratio) {
                $best = $pair;
            }
        }

        return $best;
    }

    /**
     * Moves the foreground along the OKLCH lightness axis until it satisfies the requirement,
     * changing it as little as possible. Hue and chroma are preserved, so the suggestion still
     * reads as the colour the user asked for. When neither direction can satisfy the
     * requirement, the best achievable colour is returned — never a silent pass.
     */
    public function nearestCompliantForeground(
        Srgb $background,
        Srgb $foreground,
        ContrastRequirement $requirement
    ): Srgb {
        if ($this->meets($background, $foreground, $requirement)) {
            return $foreground;
        }

        $origin = ColorSpace::srgbToOklch($foreground);

        return ColorSpace::oklchToSrgb(
            $origin->withLightness($this->nearestCompliantLightness($background, $origin, $requirement))
        );
    }

    private function nearestCompliantLightness(
        Srgb $background,
        Oklch $origin,
        ContrastRequirement $requirement
    ): float {
        $compliant = [];

        foreach ([self::DARKEST_LIGHTNESS, self::LIGHTEST_LIGHTNESS] as $extreme) {
            if ($this->meetsAtLightness($background, $origin, $extreme, $requirement)) {
                $compliant[] = $this->closestCompliantLightness($background, $origin, $extreme, $requirement);
            }
        }

        if ($compliant === []) {
            return $this->bestAchievableLightness($background, $origin);
        }

        usort(
            $compliant,
            static fn (float $first, float $second): int
                => abs($first - $origin->lightness) <=> abs($second - $origin->lightness)
        );

        return $compliant[0];
    }

    private function bestAchievableLightness(Srgb $background, Oklch $origin): float
    {
        $darkRatio = $this->ratioAtLightness($background, $origin, self::DARKEST_LIGHTNESS);
        $lightRatio = $this->ratioAtLightness($background, $origin, self::LIGHTEST_LIGHTNESS);

        return $darkRatio >= $lightRatio ? self::DARKEST_LIGHTNESS : self::LIGHTEST_LIGHTNESS;
    }

    private function closestCompliantLightness(
        Srgb $background,
        Oklch $origin,
        float $compliantLightness,
        ContrastRequirement $requirement
    ): float {
        $failing = $origin->lightness;
        $passing = $compliantLightness;

        for ($iteration = 0; $iteration < self::LIGHTNESS_SEARCH_ITERATIONS; $iteration++) {
            $midpoint = ($failing + $passing) / 2;

            if ($this->meetsAtLightness($background, $origin, $midpoint, $requirement)) {
                $passing = $midpoint;
                continue;
            }

            $failing = $midpoint;
        }

        return $passing;
    }

    private function meetsAtLightness(
        Srgb $background,
        Oklch $origin,
        float $lightness,
        ContrastRequirement $requirement
    ): bool {
        return $requirement->isSatisfiedBy($this->ratioAtLightness($background, $origin, $lightness));
    }

    private function ratioAtLightness(Srgb $background, Oklch $origin, float $lightness): float
    {
        return $this->ratio($background, ColorSpace::oklchToSrgb($origin->withLightness($lightness)));
    }
}
