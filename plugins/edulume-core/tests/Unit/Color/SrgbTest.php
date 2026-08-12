<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Color;

use Edulume\Core\Domain\Color\InvalidColorException;
use Edulume\Core\Domain\Color\Srgb;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SrgbTest extends TestCase
{
    #[Test]
    public function it_parses_a_full_hex_string(): void
    {
        $color = Srgb::fromHex('#1A5FB4');

        $this->assertSame(0x1A, $color->redChannel());
        $this->assertSame(0x5F, $color->greenChannel());
        $this->assertSame(0xB4, $color->blueChannel());
    }

    #[Test]
    public function it_expands_a_short_hex_string(): void
    {
        $this->assertSame('#33aacc', Srgb::fromHex('#3ac')->toHex());
    }

    #[Test]
    public function it_accepts_a_hex_string_without_a_leading_hash(): void
    {
        $this->assertSame('#1a5fb4', Srgb::fromHex(' 1a5fb4 ')->toHex());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidHexProvider(): array
    {
        return [
            'empty' => [''],
            'four digits' => ['#1234'],
            'non hex characters' => ['#gggggg'],
            'seven digits' => ['#1234567'],
        ];
    }

    #[Test]
    #[DataProvider('invalidHexProvider')]
    public function it_rejects_a_string_that_is_not_a_hex_colour(string $hex): void
    {
        $this->expectException(InvalidColorException::class);

        Srgb::fromHex($hex);
    }

    #[Test]
    public function it_emits_lower_case_hex(): void
    {
        $this->assertSame('#ffffff', Srgb::fromHex('#FFFFFF')->toHex());
    }

    #[Test]
    public function it_builds_from_validated_components(): void
    {
        $color = Srgb::fromComponents(0.0, 0.5, 1.0);

        $this->assertSame(0, $color->redChannel());
        $this->assertSame(128, $color->greenChannel());
        $this->assertSame(255, $color->blueChannel());
    }

    /**
     * @return array<string, array{float, float, float}>
     */
    public static function outOfRangeComponentProvider(): array
    {
        return [
            'red below zero' => [-0.001, 0.5, 0.5],
            'green above one' => [0.5, 1.001, 0.5],
            'blue below zero' => [0.5, 0.5, -2.0],
        ];
    }

    #[Test]
    #[DataProvider('outOfRangeComponentProvider')]
    public function it_rejects_components_outside_the_unit_range(float $red, float $green, float $blue): void
    {
        $this->expectException(InvalidColorException::class);

        Srgb::fromComponents($red, $green, $blue);
    }

    #[Test]
    public function it_clamps_components_when_asked_to(): void
    {
        $color = Srgb::fromClampedComponents(-0.4, 0.5, 1.9);

        $this->assertSame('#0080ff', $color->toHex());
    }

    #[Test]
    public function it_clamps_channels_outside_the_byte_range(): void
    {
        $this->assertSame('#00ff00', Srgb::fromChannels(-12, 300, 0)->toHex());
    }

    #[Test]
    public function it_computes_relative_luminance_of_white_as_one(): void
    {
        $this->assertEqualsWithDelta(1.0, Srgb::fromHex('#ffffff')->relativeLuminance(), 1.0e-12);
    }

    #[Test]
    public function it_computes_relative_luminance_of_black_as_zero(): void
    {
        $this->assertSame(0.0, Srgb::fromHex('#000000')->relativeLuminance());
    }

    #[Test]
    public function it_exposes_linear_light_components(): void
    {
        [$red, $green, $blue] = Srgb::fromHex('#ffffff')->toLinearComponents();

        $this->assertEqualsWithDelta(1.0, $red, 1.0e-12);
        $this->assertEqualsWithDelta(1.0, $green, 1.0e-12);
        $this->assertEqualsWithDelta(1.0, $blue, 1.0e-12);
    }

    #[Test]
    public function it_treats_colours_with_the_same_channels_as_equal(): void
    {
        $this->assertTrue(Srgb::fromHex('#3ac')->equals(Srgb::fromHex('#33aacc')));
    }

    #[Test]
    public function it_treats_colours_with_different_channels_as_unequal(): void
    {
        $this->assertFalse(Srgb::fromHex('#33aacc')->equals(Srgb::fromHex('#33aacd')));
        $this->assertFalse(Srgb::fromHex('#33aacc')->equals(Srgb::fromHex('#33abcc')));
        $this->assertFalse(Srgb::fromHex('#33aacc')->equals(Srgb::fromHex('#34aacc')));
    }

    #[Test]
    public function it_applies_the_linear_segment_of_the_transfer_function_near_black(): void
    {
        $this->assertEqualsWithDelta(0.04 / 12.92, Srgb::toLinear(0.04), 1.0e-12);
        $this->assertEqualsWithDelta(0.003 * 12.92, Srgb::fromLinear(0.003), 1.0e-12);
    }

    /**
     * The published sRGB thresholds (0.04045 and 0.0031308) are rounded rather than exact
     * reciprocals, so the two branches disagree by about 3e-8 at the join. Sampling away
     * from the join keeps this test about the transfer function rather than about the kink.
     */
    #[Test]
    public function it_round_trips_the_transfer_function(): void
    {
        foreach ([0.0, 0.01, 0.03, 0.2, 0.5, 0.75, 1.0] as $encoded) {
            $this->assertEqualsWithDelta($encoded, Srgb::fromLinear(Srgb::toLinear($encoded)), 1.0e-12);
        }
    }
}
