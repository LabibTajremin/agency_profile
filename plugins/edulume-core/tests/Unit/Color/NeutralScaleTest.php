<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Color;

use Edulume\Core\Domain\Color\InvalidColorException;
use Edulume\Core\Domain\Color\NeutralScale;
use Edulume\Core\Domain\Color\Srgb;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NeutralScaleTest extends TestCase
{
    #[Test]
    public function it_produces_eleven_steps(): void
    {
        $this->assertCount(NeutralScale::STEP_COUNT, NeutralScale::fromHue(250.0)->steps());
    }

    #[Test]
    public function it_never_starts_at_pure_white_or_ends_at_pure_black(): void
    {
        $scale = NeutralScale::fromHue(250.0);

        $this->assertNotSame('#ffffff', $scale->lightestInk()->toHex());
        $this->assertNotSame('#000000', $scale->darkestInk()->toHex());
    }

    /**
     * @return array<string, array{float}>
     */
    public static function hueProvider(): array
    {
        return [
            'red' => [25.0],
            'yellow' => [95.0],
            'green' => [145.0],
            'cyan' => [195.0],
            'blue' => [255.0],
            'magenta' => [330.0],
        ];
    }

    #[Test]
    #[DataProvider('hueProvider')]
    public function it_descends_in_luminance_from_the_first_step_to_the_last(float $hue): void
    {
        $steps = NeutralScale::fromHue($hue)->steps();

        for ($index = 1; $index < count($steps); $index++) {
            $this->assertLessThan(
                $steps[$index - 1]->relativeLuminance(),
                $steps[$index]->relativeLuminance(),
                sprintf('Step %d is not darker than step %d.', $index, $index - 1),
            );
        }
    }

    #[Test]
    public function it_takes_its_hue_from_an_accent_colour(): void
    {
        $scale = NeutralScale::fromAccent(Srgb::fromHex('#1a5fb4'));

        $this->assertEqualsWithDelta(256.4, $scale->hue, 1.0);
    }

    #[Test]
    public function it_produces_a_pure_grey_when_the_tint_is_switched_off(): void
    {
        $step = NeutralScale::fromHue(250.0, 0.0)->step(5);

        $this->assertSame($step->redChannel(), $step->greenChannel());
        $this->assertSame($step->greenChannel(), $step->blueChannel());
    }

    #[Test]
    public function it_tints_the_inks_toward_the_hue_when_the_tint_is_on(): void
    {
        $step = NeutralScale::fromHue(250.0)->step(5);

        $this->assertGreaterThan($step->redChannel(), $step->blueChannel());
    }

    #[Test]
    public function it_clamps_the_tint_strength_into_the_unit_range(): void
    {
        $this->assertSame(1.0, NeutralScale::fromHue(250.0, 4.0)->tintStrength);
        $this->assertSame(0.0, NeutralScale::fromHue(250.0, -1.0)->tintStrength);
    }

    #[Test]
    public function it_normalises_the_hue_it_was_given(): void
    {
        $this->assertEqualsWithDelta(30.0, NeutralScale::fromHue(390.0)->hue, 1.0e-12);
    }

    #[Test]
    public function it_rejects_a_step_that_does_not_exist(): void
    {
        $this->expectException(InvalidColorException::class);

        NeutralScale::fromHue(250.0)->step(NeutralScale::STEP_COUNT);
    }
}
