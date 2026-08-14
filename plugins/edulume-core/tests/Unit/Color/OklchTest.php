<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Color;

use Edulume\Core\Domain\Color\Oklch;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OklchTest extends TestCase
{
    #[Test]
    public function it_clamps_lightness_into_the_unit_range(): void
    {
        $this->assertSame(0.0, Oklch::fromComponents(-0.5, 0.1, 20.0)->lightness);
        $this->assertSame(1.0, Oklch::fromComponents(1.5, 0.1, 20.0)->lightness);
    }

    #[Test]
    public function it_refuses_to_hold_a_negative_chroma(): void
    {
        $this->assertSame(0.0, Oklch::fromComponents(0.5, -0.2, 20.0)->chroma);
    }

    #[Test]
    public function it_normalises_a_hue_beyond_a_full_turn(): void
    {
        $this->assertEqualsWithDelta(30.0, Oklch::fromComponents(0.5, 0.1, 390.0)->hue, 1.0e-12);
    }

    #[Test]
    public function it_normalises_a_negative_hue(): void
    {
        $this->assertEqualsWithDelta(330.0, Oklch::normaliseHue(-30.0), 1.0e-12);
    }

    #[Test]
    public function it_converts_back_to_cartesian_coordinates(): void
    {
        $cartesian = Oklch::fromComponents(0.5, 0.1, 90.0)->toOklab();

        $this->assertEqualsWithDelta(0.5, $cartesian->lightness, 1.0e-12);
        $this->assertEqualsWithDelta(0.0, $cartesian->greenRedAxis, 1.0e-12);
        $this->assertEqualsWithDelta(0.1, $cartesian->blueYellowAxis, 1.0e-12);
    }

    #[Test]
    public function it_replaces_one_component_at_a_time(): void
    {
        $color = Oklch::fromComponents(0.5, 0.1, 200.0);

        $this->assertSame(0.8, $color->withLightness(0.8)->lightness);
        $this->assertSame(200.0, $color->withLightness(0.8)->hue);
        $this->assertSame(0.02, $color->withChroma(0.02)->chroma);
        $this->assertSame(0.5, $color->withChroma(0.02)->lightness);
    }
}
