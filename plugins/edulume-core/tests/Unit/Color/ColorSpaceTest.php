<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Color;

use Edulume\Core\Domain\Color\ColorSpace;
use Edulume\Core\Domain\Color\Oklch;
use Edulume\Core\Domain\Color\Srgb;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ColorSpaceTest extends TestCase
{
    private const LATTICE_CHANNELS = [0, 51, 102, 153, 204, 255];

    /**
     * A deterministic 6×6×6 lattice — 216 colours, comfortably past the hundred the
     * acceptance criterion asks for, and reproducible on every machine.
     *
     * @return list<Srgb>
     */
    private static function latticeColors(): array
    {
        $colors = [];

        foreach (self::LATTICE_CHANNELS as $red) {
            foreach (self::LATTICE_CHANNELS as $green) {
                foreach (self::LATTICE_CHANNELS as $blue) {
                    $colors[] = Srgb::fromChannels($red, $green, $blue);
                }
            }
        }

        return $colors;
    }

    #[Test]
    public function it_round_trips_every_sampled_colour_through_oklch_without_drift(): void
    {
        $colors = self::latticeColors();

        $this->assertGreaterThanOrEqual(100, count($colors));

        foreach ($colors as $color) {
            $roundTripped = ColorSpace::oklchToSrgb(ColorSpace::srgbToOklch($color));

            $this->assertSame($color->toHex(), $roundTripped->toHex());
        }
    }

    #[Test]
    public function it_places_white_at_the_top_of_the_lightness_axis(): void
    {
        $white = ColorSpace::srgbToOklch(Srgb::fromHex('#ffffff'));

        $this->assertEqualsWithDelta(1.0, $white->lightness, 1.0e-6);
        $this->assertEqualsWithDelta(0.0, $white->chroma, 1.0e-6);
    }

    #[Test]
    public function it_places_black_at_the_bottom_of_the_lightness_axis(): void
    {
        $black = ColorSpace::srgbToOklab(Srgb::fromHex('#000000'));

        $this->assertEqualsWithDelta(0.0, $black->lightness, 1.0e-9);
        $this->assertEqualsWithDelta(0.0, $black->greenRedAxis, 1.0e-9);
        $this->assertEqualsWithDelta(0.0, $black->blueYellowAxis, 1.0e-9);
    }

    #[Test]
    public function it_reports_a_realistic_colour_as_inside_the_gamut(): void
    {
        $this->assertTrue(ColorSpace::isInGamut(ColorSpace::srgbToOklch(Srgb::fromHex('#1a5fb4'))));
    }

    #[Test]
    public function it_reports_a_colour_that_overshoots_the_bright_end_as_outside_the_gamut(): void
    {
        $this->assertFalse(ColorSpace::isInGamut(Oklch::fromComponents(0.99, 0.4, 90.0)));
    }

    #[Test]
    public function it_reports_a_colour_that_undershoots_the_dark_end_as_outside_the_gamut(): void
    {
        $this->assertFalse(ColorSpace::isInGamut(Oklch::fromComponents(0.2, 0.4, 140.0)));
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function impossibleColorProvider(): array
    {
        return [
            'bright yellow' => [0.95, 90.0],
            'deep blue' => [0.25, 264.0],
            'vivid green' => [0.70, 145.0],
            'hot pink' => [0.60, 350.0],
            'cyan' => [0.85, 195.0],
        ];
    }

    #[Test]
    #[DataProvider('impossibleColorProvider')]
    public function it_maps_an_unreachable_chroma_back_inside_the_gamut(float $lightness, float $hue): void
    {
        $requested = Oklch::fromComponents($lightness, 0.5, $hue);

        $mapped = ColorSpace::mapIntoGamut($requested);

        $this->assertTrue(ColorSpace::isInGamut($mapped));
        $this->assertLessThan($requested->chroma, $mapped->chroma);
        $this->assertSame($requested->lightness, $mapped->lightness);
        $this->assertSame($requested->hue, $mapped->hue);
    }

    #[Test]
    public function it_leaves_an_in_gamut_colour_untouched(): void
    {
        $inGamut = Oklch::fromComponents(0.6, 0.05, 250.0);

        $this->assertSame($inGamut, ColorSpace::mapIntoGamut($inGamut));
    }

    #[Test]
    #[DataProvider('impossibleColorProvider')]
    public function it_returns_a_renderable_colour_for_an_unreachable_request(float $lightness, float $hue): void
    {
        $rendered = ColorSpace::oklchToSrgb(Oklch::fromComponents($lightness, 0.5, $hue));

        $this->assertTrue(ColorSpace::isInGamut(ColorSpace::srgbToOklch($rendered)));
    }
}
