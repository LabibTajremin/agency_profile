<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Color;

use Edulume\Core\Domain\Color\ContrastPair;
use Edulume\Core\Domain\Color\ContrastRequirement;
use Edulume\Core\Domain\Color\Srgb;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContrastPairTest extends TestCase
{
    #[Test]
    public function it_rates_black_on_white_as_exactly_twenty_one(): void
    {
        $pair = ContrastPair::of(Srgb::fromHex('#ffffff'), Srgb::fromHex('#000000'));

        $this->assertSame(21.0, $pair->ratio);
    }

    #[Test]
    public function it_rates_the_same_colour_on_itself_as_exactly_one(): void
    {
        $pair = ContrastPair::of(Srgb::fromHex('#1a5fb4'), Srgb::fromHex('#1a5fb4'));

        $this->assertSame(1.0, $pair->ratio);
    }

    #[Test]
    public function it_gives_the_same_ratio_whichever_colour_is_the_background(): void
    {
        $white = Srgb::fromHex('#ffffff');
        $blue = Srgb::fromHex('#1a5fb4');

        $this->assertSame(ContrastPair::of($white, $blue)->ratio, ContrastPair::of($blue, $white)->ratio);
    }

    #[Test]
    public function it_keeps_the_colours_it_was_given(): void
    {
        $background = Srgb::fromHex('#ffffff');
        $foreground = Srgb::fromHex('#000000');

        $pair = ContrastPair::of($background, $foreground);

        $this->assertSame($background, $pair->background);
        $this->assertSame($foreground, $pair->foreground);
    }

    #[Test]
    public function it_reports_whether_it_meets_a_requirement(): void
    {
        $pair = ContrastPair::of(Srgb::fromHex('#ffffff'), Srgb::fromHex('#767676'));

        $this->assertTrue($pair->meets(ContrastRequirement::NormalTextAa));
        $this->assertFalse($pair->meets(ContrastRequirement::NormalTextAaa));
    }
}
