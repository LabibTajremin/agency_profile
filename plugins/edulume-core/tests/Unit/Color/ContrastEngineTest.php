<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Color;

use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\ContrastRequirement;
use Edulume\Core\Domain\Color\InvalidColorException;
use Edulume\Core\Domain\Color\Srgb;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContrastEngineTest extends TestCase
{
    private ContrastEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new ContrastEngine();
    }

    #[Test]
    public function it_rates_black_on_white_as_exactly_twenty_one(): void
    {
        $this->assertSame(21.0, $this->engine->ratio(Srgb::fromHex('#ffffff'), Srgb::fromHex('#000000')));
    }

    #[Test]
    public function it_reports_whether_a_pair_meets_a_requirement(): void
    {
        $white = Srgb::fromHex('#ffffff');

        $this->assertTrue($this->engine->meets($white, Srgb::fromHex('#595959'), ContrastRequirement::NormalTextAa));
        $this->assertFalse($this->engine->meets($white, Srgb::fromHex('#bbbbbb'), ContrastRequirement::NormalTextAa));
    }

    #[Test]
    public function it_picks_the_first_candidate_that_meets_the_requirement(): void
    {
        $picked = $this->engine->pickForeground(
            Srgb::fromHex('#ffffff'),
            [Srgb::fromHex('#cccccc'), Srgb::fromHex('#595959'), Srgb::fromHex('#000000')],
            ContrastRequirement::NormalTextAa,
        );

        $this->assertSame('#595959', $picked->foreground->toHex());
    }

    #[Test]
    public function it_falls_back_to_the_highest_contrast_candidate_when_none_complies(): void
    {
        $picked = $this->engine->pickForeground(
            Srgb::fromHex('#ffffff'),
            [Srgb::fromHex('#eeeeee'), Srgb::fromHex('#999999'), Srgb::fromHex('#cccccc')],
            ContrastRequirement::NormalTextAaa,
        );

        $this->assertSame('#999999', $picked->foreground->toHex());
    }

    #[Test]
    public function it_refuses_an_empty_candidate_list(): void
    {
        $this->expectException(InvalidColorException::class);

        $this->engine->pickForeground(Srgb::fromHex('#ffffff'), [], ContrastRequirement::NormalTextAa);
    }

    #[Test]
    public function it_leaves_a_compliant_foreground_alone(): void
    {
        $foreground = Srgb::fromHex('#000000');

        $suggested = $this->engine->nearestCompliantForeground(
            Srgb::fromHex('#ffffff'),
            $foreground,
            ContrastRequirement::NormalTextAa,
        );

        $this->assertSame($foreground, $suggested);
    }

    /**
     * @return array<string, array{string, string, ContrastRequirement}>
     */
    public static function nonCompliantPairProvider(): array
    {
        return [
            'pale blue on white' => ['#ffffff', '#7cb0e8', ContrastRequirement::NormalTextAa],
            'mid grey on white' => ['#ffffff', '#9a9a9a', ContrastRequirement::NormalTextAaa],
            'dark blue on black' => ['#000000', '#123a6b', ContrastRequirement::NormalTextAa],
            'olive on dark slate' => ['#1f2933', '#3f4a2a', ContrastRequirement::LargeTextAa],
            'crimson on white' => ['#ffffff', '#e0455f', ContrastRequirement::NormalTextAa],
        ];
    }

    #[Test]
    #[DataProvider('nonCompliantPairProvider')]
    public function it_suggests_a_nearest_compliant_foreground(
        string $backgroundHex,
        string $foregroundHex,
        ContrastRequirement $requirement
    ): void {
        $background = Srgb::fromHex($backgroundHex);
        $foreground = Srgb::fromHex($foregroundHex);

        $this->assertFalse($this->engine->meets($background, $foreground, $requirement));

        $suggested = $this->engine->nearestCompliantForeground($background, $foreground, $requirement);

        $this->assertTrue(
            $this->engine->meets($background, $suggested, $requirement),
            sprintf('Suggested %s on %s still fails the requirement.', $suggested->toHex(), $backgroundHex),
        );
    }

    #[Test]
    public function it_moves_the_foreground_no_further_than_it_has_to(): void
    {
        $background = Srgb::fromHex('#ffffff');
        $foreground = Srgb::fromHex('#7cb0e8');

        $suggested = $this->engine->nearestCompliantForeground(
            $background,
            $foreground,
            ContrastRequirement::NormalTextAa,
        );

        $this->assertLessThan(
            6.0,
            $this->engine->ratio($background, $suggested),
            'The suggestion overshot the 4.5 threshold instead of stopping at the nearest compliant colour.',
        );
    }

    #[Test]
    public function it_returns_the_best_achievable_colour_when_the_requirement_is_unreachable(): void
    {
        $background = Srgb::fromHex('#767676');

        $suggested = $this->engine->nearestCompliantForeground(
            $background,
            Srgb::fromHex('#808080'),
            ContrastRequirement::NormalTextAaa,
        );

        $this->assertFalse($this->engine->meets($background, $suggested, ContrastRequirement::NormalTextAaa));
        $this->assertGreaterThan(4.0, $this->engine->ratio($background, $suggested));
    }

    #[Test]
    public function it_lightens_when_lightening_is_the_shorter_move(): void
    {
        $background = Srgb::fromHex('#8d8d8d');
        $foreground = Srgb::fromHex('#cfcfcf');

        $suggested = $this->engine->nearestCompliantForeground(
            $background,
            $foreground,
            ContrastRequirement::LargeTextAa,
        );

        $this->assertTrue($this->engine->meets($background, $suggested, ContrastRequirement::LargeTextAa));
        $this->assertGreaterThan($foreground->relativeLuminance(), $suggested->relativeLuminance());
    }

    #[Test]
    public function it_darkens_when_darkening_is_the_shorter_move(): void
    {
        $background = Srgb::fromHex('#8d8d8d');
        $foreground = Srgb::fromHex('#6b6b6b');

        $suggested = $this->engine->nearestCompliantForeground(
            $background,
            $foreground,
            ContrastRequirement::LargeTextAa,
        );

        $this->assertTrue($this->engine->meets($background, $suggested, ContrastRequirement::LargeTextAa));
        $this->assertLessThan($foreground->relativeLuminance(), $suggested->relativeLuminance());
    }

    #[Test]
    public function it_darkens_when_only_darkening_can_comply(): void
    {
        $background = Srgb::fromHex('#8d8d8d');
        $foreground = Srgb::fromHex('#9d9d9d');

        $suggested = $this->engine->nearestCompliantForeground(
            $background,
            $foreground,
            ContrastRequirement::NormalTextAa,
        );

        $this->assertTrue($this->engine->meets($background, $suggested, ContrastRequirement::NormalTextAa));
        $this->assertLessThan($foreground->relativeLuminance(), $suggested->relativeLuminance());
    }

    #[Test]
    public function it_settles_on_the_lighter_extreme_when_that_is_the_best_available(): void
    {
        $background = Srgb::fromHex('#666666');

        $suggested = $this->engine->nearestCompliantForeground(
            $background,
            Srgb::fromHex('#6e6e6e'),
            ContrastRequirement::NormalTextAaa,
        );

        $this->assertFalse($this->engine->meets($background, $suggested, ContrastRequirement::NormalTextAaa));
        $this->assertGreaterThan($background->relativeLuminance(), $suggested->relativeLuminance());
    }
}
