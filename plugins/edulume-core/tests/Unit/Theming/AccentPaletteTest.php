<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\AccentPalette;
use Edulume\Core\Domain\Theming\InvalidPaletteException;
use Edulume\Core\Domain\Theming\ThemeMode;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccentPaletteTest extends TestCase
{
    /**
     * @return list<Srgb>
     */
    private static function elevenSteps(string $hex): array
    {
        return array_fill(0, AccentPalette::STEP_COUNT, Srgb::fromHex($hex));
    }

    private static function palette(): AccentPalette
    {
        return AccentPalette::fromSteps(
            self::elevenSteps('#1a5fb4'),
            self::elevenSteps('#ffffff'),
            [
                ThemeMode::Light->value => Srgb::fromHex('#123a6b'),
                ThemeMode::Dark->value => Srgb::fromHex('#8cafdc'),
            ],
        );
    }

    #[Test]
    public function it_rejects_a_step_list_of_the_wrong_length(): void
    {
        $this->expectException(InvalidPaletteException::class);

        AccentPalette::fromSteps([Srgb::fromHex('#000000')], self::elevenSteps('#ffffff'), []);
    }

    #[Test]
    public function it_rejects_a_foreground_list_of_the_wrong_length(): void
    {
        $this->expectException(InvalidPaletteException::class);

        AccentPalette::fromSteps(self::elevenSteps('#1a5fb4'), [Srgb::fromHex('#ffffff')], []);
    }

    #[Test]
    public function it_exposes_every_step(): void
    {
        $this->assertCount(AccentPalette::STEP_COUNT, self::palette()->steps());
        $this->assertSame('#1a5fb4', self::palette()->step(0)->toHex());
    }

    #[Test]
    public function it_rejects_a_step_that_does_not_exist(): void
    {
        $this->expectException(InvalidPaletteException::class);

        self::palette()->step(AccentPalette::STEP_COUNT);
    }

    #[Test]
    public function it_rejects_a_foreground_for_a_step_that_does_not_exist(): void
    {
        $this->expectException(InvalidPaletteException::class);

        self::palette()->foregroundOn(-1);
    }

    #[Test]
    public function it_pairs_a_step_with_its_foreground(): void
    {
        $pair = self::palette()->contrastOn(3);

        $this->assertSame('#1a5fb4', $pair->background->toHex());
        $this->assertSame('#ffffff', $pair->foreground->toHex());
    }

    #[Test]
    public function it_uses_a_brighter_fill_step_in_dark_mode(): void
    {
        $palette = self::palette();

        $this->assertSame(7, $palette->fillStep(ThemeMode::Light));
        $this->assertSame(4, $palette->fillStep(ThemeMode::Dark));
    }

    #[Test]
    public function it_resolves_the_fill_and_its_foreground_per_mode(): void
    {
        $palette = self::palette();

        $this->assertSame('#1a5fb4', $palette->fill(ThemeMode::Light)->toHex());
        $this->assertSame('#ffffff', $palette->onFill(ThemeMode::Dark)->toHex());
    }

    #[Test]
    public function it_returns_a_different_accent_text_colour_per_mode(): void
    {
        $palette = self::palette();

        $this->assertSame('#123a6b', $palette->text(ThemeMode::Light)->toHex());
        $this->assertSame('#8cafdc', $palette->text(ThemeMode::Dark)->toHex());
    }
}
