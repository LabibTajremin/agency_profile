<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\PatternAttachment;
use Edulume\Core\Domain\Theming\PatternBlendMode;
use Edulume\Core\Domain\Theming\PatternColorSource;
use Edulume\Core\Domain\Theming\PatternSettings;
use Edulume\Core\Domain\Theming\ThemeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PatternSettingsTest extends TestCase
{
    private static function settings(
        string $slug = 'dot-grid',
        float $lightOpacity = 0.06,
        float $darkOpacity = 0.10,
        float $scale = 1.0,
        int $rotation = 0
    ): PatternSettings {
        return PatternSettings::of(
            $slug,
            $lightOpacity,
            $darkOpacity,
            $scale,
            PatternColorSource::Accent,
            $rotation,
            PatternBlendMode::Normal,
            PatternAttachment::Scroll,
        );
    }

    #[Test]
    public function it_defaults_to_a_heavier_opacity_in_dark_mode(): void
    {
        $settings = PatternSettings::defaultsFor('dot-grid');

        $this->assertGreaterThan(
            $settings->opacityFor(ThemeMode::Light),
            $settings->opacityFor(ThemeMode::Dark),
            'The value that whispers on white is invisible on near-black.',
        );
    }

    #[Test]
    public function it_starts_enabled_when_given_a_pattern(): void
    {
        $this->assertTrue(PatternSettings::defaultsFor('dot-grid')->enabled);
    }

    #[Test]
    public function it_can_be_switched_off_entirely(): void
    {
        $this->assertFalse(PatternSettings::disabled()->enabled);
    }

    #[Test]
    public function it_falls_back_to_the_default_pattern_when_the_slug_is_unknown(): void
    {
        $this->assertSame(PatternSettings::DEFAULT_PATTERN_SLUG, self::settings('plaid-of-doom')->patternSlug);
        $this->assertSame(PatternSettings::DEFAULT_PATTERN_SLUG, PatternSettings::defaultsFor('')->patternSlug);
    }

    #[Test]
    public function it_keeps_a_slug_it_recognises(): void
    {
        $this->assertSame('compass-rose', self::settings('compass-rose')->patternSlug);
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function opacityProvider(): array
    {
        return [
            'below zero' => [-0.5, 0.0],
            'above one' => [4.0, 1.0],
            'inside the range' => [0.42, 0.42],
        ];
    }

    #[Test]
    #[DataProvider('opacityProvider')]
    public function it_coerces_opacity_into_the_unit_range(float $given, float $expected): void
    {
        $this->assertSame($expected, self::settings(lightOpacity: $given)->lightModeOpacity);
        $this->assertSame($expected, self::settings(darkOpacity: $given)->darkModeOpacity);
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function scaleProvider(): array
    {
        return [
            'below the floor' => [0.1, PatternSettings::MINIMUM_SCALE],
            'above the ceiling' => [9.0, PatternSettings::MAXIMUM_SCALE],
            'inside the range' => [1.75, 1.75],
        ];
    }

    #[Test]
    #[DataProvider('scaleProvider')]
    public function it_coerces_the_scale_into_the_supported_range(float $given, float $expected): void
    {
        $this->assertSame($expected, self::settings(scale: $given)->scale);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function rotationProvider(): array
    {
        return [
            'beyond a full turn' => [405, 45],
            'negative' => [-90, 270],
            'inside the turn' => [135, 135],
        ];
    }

    #[Test]
    #[DataProvider('rotationProvider')]
    public function it_wraps_the_rotation_into_a_single_turn(int $given, int $expected): void
    {
        $this->assertSame($expected, self::settings(rotation: $given)->rotation);
    }

    #[Test]
    public function it_resolves_the_pattern_it_points_at(): void
    {
        $this->assertSame('compass-rose', self::settings('compass-rose')->pattern()->slug);
    }

    #[Test]
    public function it_keeps_the_presentation_choices_it_was_given(): void
    {
        $settings = PatternSettings::of(
            'weave',
            0.05,
            0.12,
            2.0,
            PatternColorSource::Neutral,
            45,
            PatternBlendMode::Multiply,
            PatternAttachment::Fixed,
        );

        $this->assertSame(PatternColorSource::Neutral, $settings->colorSource);
        $this->assertSame('multiply', $settings->blendMode->toCssValue());
        $this->assertSame('fixed', $settings->attachment->toCssValue());
    }
}
