<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Color;

use Edulume\Core\Domain\Color\Oklab;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OklabTest extends TestCase
{
    #[Test]
    public function it_keeps_the_components_it_was_given(): void
    {
        $color = Oklab::fromComponents(0.62, -0.05, 0.12);

        $this->assertSame(0.62, $color->lightness);
        $this->assertSame(-0.05, $color->greenRedAxis);
        $this->assertSame(0.12, $color->blueYellowAxis);
    }

    #[Test]
    public function it_converts_to_polar_coordinates(): void
    {
        $polar = Oklab::fromComponents(0.5, 0.1, 0.0)->toOklch();

        $this->assertEqualsWithDelta(0.5, $polar->lightness, 1.0e-12);
        $this->assertEqualsWithDelta(0.1, $polar->chroma, 1.0e-12);
        $this->assertEqualsWithDelta(0.0, $polar->hue, 1.0e-12);
    }

    #[Test]
    public function it_wraps_a_negative_polar_angle_into_the_positive_turn(): void
    {
        $polar = Oklab::fromComponents(0.5, 0.0, -0.1)->toOklch();

        $this->assertEqualsWithDelta(270.0, $polar->hue, 1.0e-9);
    }

    #[Test]
    public function it_reports_an_achromatic_colour_as_hueless(): void
    {
        $polar = Oklab::fromComponents(0.5, 0.0, 0.0)->toOklch();

        $this->assertSame(0.0, $polar->chroma);
        $this->assertSame(0.0, $polar->hue);
    }
}
