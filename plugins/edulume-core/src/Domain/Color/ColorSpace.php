<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Color;

/**
 * Conversions between sRGB, OKLab and OKLCH, plus gamut mapping.
 *
 * The matrices are Björn Ottosson's published OKLab constants. They are kept here as named
 * tables rather than inline literals so the one place the maths lives is obvious.
 */
final class ColorSpace
{
    private const CUBE_ROOT_EXPONENT = 1 / 3;
    private const GAMUT_TOLERANCE = 1.0e-7;
    private const GAMUT_SEARCH_ITERATIONS = 32;

    private const LINEAR_SRGB_TO_CONE_RESPONSE = [
        [0.4122214708, 0.5363325363, 0.0514459929],
        [0.2119034982, 0.6806995451, 0.1073969566],
        [0.0883024619, 0.2817188376, 0.6299787005],
    ];

    private const CONE_RESPONSE_TO_OKLAB = [
        [0.2104542553, 0.7936177850, -0.0040720468],
        [1.9779984951, -2.4285922050, 0.4505937099],
        [0.0259040371, 0.7827717662, -0.8086757660],
    ];

    private const OKLAB_TO_CONE_RESPONSE = [
        [1.0, 0.3963377774, 0.2158037573],
        [1.0, -0.1055613458, -0.0638541728],
        [1.0, -0.0894841775, -1.2914855480],
    ];

    private const CONE_RESPONSE_TO_LINEAR_SRGB = [
        [4.0767416621, -3.3077115913, 0.2309699292],
        [-1.2684380046, 2.6097574011, -0.3413193965],
        [-0.0041960863, -0.7034186147, 1.7076147010],
    ];

    public static function srgbToOklab(Srgb $color): Oklab
    {
        [$red, $green, $blue] = $color->toLinearComponents();

        $cones = self::multiply(self::LINEAR_SRGB_TO_CONE_RESPONSE, [$red, $green, $blue]);
        $roots = [
            $cones[0] ** self::CUBE_ROOT_EXPONENT,
            $cones[1] ** self::CUBE_ROOT_EXPONENT,
            $cones[2] ** self::CUBE_ROOT_EXPONENT,
        ];

        [$lightness, $greenRed, $blueYellow] = self::multiply(self::CONE_RESPONSE_TO_OKLAB, $roots);

        return Oklab::fromComponents($lightness, $greenRed, $blueYellow);
    }

    public static function srgbToOklch(Srgb $color): Oklch
    {
        return self::srgbToOklab($color)->toOklch();
    }

    /**
     * Converts to sRGB, reducing chroma until the colour fits inside the gamut.
     */
    public static function oklchToSrgb(Oklch $color): Srgb
    {
        $mapped = self::mapIntoGamut($color);
        [$red, $green, $blue] = self::oklabToLinearComponents($mapped->toOklab());

        return Srgb::fromClampedComponents(
            Srgb::fromLinear($red),
            Srgb::fromLinear($green),
            Srgb::fromLinear($blue),
        );
    }

    public static function isInGamut(Oklch $color): bool
    {
        foreach (self::oklabToLinearComponents($color->toOklab()) as $component) {
            if ($component < -self::GAMUT_TOLERANCE || $component > 1.0 + self::GAMUT_TOLERANCE) {
                return false;
            }
        }

        return true;
    }

    /**
     * Binary-searches the chroma axis for the most saturated in-gamut colour at this
     * lightness and hue. Lightness and hue are preserved because they carry the design
     * intent; chroma is the axis a viewer notices least when it moves.
     */
    public static function mapIntoGamut(Oklch $color): Oklch
    {
        if (self::isInGamut($color)) {
            return $color;
        }

        $lowChroma = 0.0;
        $highChroma = $color->chroma;

        for ($iteration = 0; $iteration < self::GAMUT_SEARCH_ITERATIONS; $iteration++) {
            $midChroma = ($lowChroma + $highChroma) / 2;

            if (self::isInGamut($color->withChroma($midChroma))) {
                $lowChroma = $midChroma;
                continue;
            }

            $highChroma = $midChroma;
        }

        return $color->withChroma($lowChroma);
    }

    /**
     * @return array{float, float, float}
     */
    private static function oklabToLinearComponents(Oklab $color): array
    {
        $roots = self::multiply(
            self::OKLAB_TO_CONE_RESPONSE,
            [$color->lightness, $color->greenRedAxis, $color->blueYellowAxis],
        );

        $cones = [$roots[0] ** 3, $roots[1] ** 3, $roots[2] ** 3];

        return self::multiply(self::CONE_RESPONSE_TO_LINEAR_SRGB, $cones);
    }

    /**
     * @param array<int, array<int, float>> $matrix
     * @param array{float, float, float} $vector
     *
     * @return array{float, float, float}
     */
    private static function multiply(array $matrix, array $vector): array
    {
        $product = [];

        foreach ($matrix as $row) {
            $product[] = $row[0] * $vector[0] + $row[1] * $vector[1] + $row[2] * $vector[2];
        }

        return [$product[0], $product[1], $product[2]];
    }
}
