<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\CubicBezier;
use Edulume\Core\Domain\Theming\FontRole;
use Edulume\Core\Domain\Theming\FontSubset;
use Edulume\Core\Domain\Theming\LayoutDensity;
use Edulume\Core\Domain\Theming\LayoutSettings;
use Edulume\Core\Domain\Theming\MotionEffect;
use Edulume\Core\Domain\Theming\MotionPreset;
use Edulume\Core\Domain\Theming\MotionSettings;
use Edulume\Core\Domain\Theming\PatternAttachment;
use Edulume\Core\Domain\Theming\PatternBlendMode;
use Edulume\Core\Domain\Theming\PatternColorSource;
use Edulume\Core\Domain\Theming\PatternSettings;
use Edulume\Core\Domain\Theming\ThemeMode;
use Edulume\Core\Domain\Theming\ThemePreference;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\TypeScale;
use Edulume\Core\Domain\Theming\TypographySettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ThemeSettingsTest extends TestCase
{
    private static function fullyPopulated(): ThemeSettings
    {
        return ThemeSettings::of(
            'gulf-gold',
            Srgb::fromHex('#8f6b16'),
            ThemePreference::Dark,
            false,
            TypographySettings::of(
                'gulf-premium',
                [FontRole::Code->value => 'jetbrains-mono'],
                TypeScale::of(1.333, 1.125),
                [FontSubset::Latin, FontSubset::Arabic],
                true,
            ),
            PatternSettings::of(
                'compass-rose',
                0.08,
                0.14,
                1.5,
                PatternColorSource::Neutral,
                45,
                PatternBlendMode::Multiply,
                PatternAttachment::Fixed,
            ),
            MotionSettings::of(
                MotionPreset::Cinematic,
                [MotionEffect::SmoothScroll->value => true],
                CubicBezier::of(0.2, 0.8, 0.4, 1.0),
            ),
            LayoutSettings::of(1120, 1360, 32, 120, 20, 2, LayoutDensity::Spacious),
        );
    }

    #[Test]
    public function it_round_trips_a_fully_populated_configuration_without_loss(): void
    {
        $original = self::fullyPopulated();

        $restored = ThemeSettings::fromArray($original->toArray());

        $this->assertSame($original->toArray(), $restored->toArray());
    }

    #[Test]
    public function it_round_trips_the_defaults_without_loss(): void
    {
        $defaults = ThemeSettings::defaults();

        $this->assertSame($defaults->toArray(), ThemeSettings::fromArray($defaults->toArray())->toArray());
    }

    #[Test]
    public function it_survives_a_round_trip_through_json(): void
    {
        $original = self::fullyPopulated();

        $encoded = json_encode($original->toArray());
        $this->assertIsString($encoded);

        /** @var array<array-key, mixed> $decoded */
        $decoded = json_decode($encoded, true);

        $this->assertSame($original->toArray(), ThemeSettings::fromArray($decoded)->toArray());
    }

    #[Test]
    public function it_stamps_the_current_schema_version(): void
    {
        $this->assertSame(ThemeSettings::CURRENT_SCHEMA_VERSION, ThemeSettings::defaults()->toArray()['schemaVersion']);
    }

    /**
     * Every key in turn is replaced with something no sane caller would send. None of them
     * may take the site down; all of them must land on a valid default.
     *
     * @return array<string, array{string, mixed}>
     */
    public static function corruptKeyProvider(): array
    {
        return [
            'accent slug is an object' => ['accentSlug', ['nope' => true]],
            'accent slug is unknown' => ['accentSlug', 'chartreuse-of-doom'],
            'custom accent is not a hex' => ['customAccent', 'rgb(1,2,3)'],
            'custom accent is a number' => ['customAccent', 42],
            'custom accent is empty' => ['customAccent', '   '],
            'theme preference is nonsense' => ['themePreference', 'ultraviolet'],
            'mode toggle is a string' => ['offersModeToggle', 'perhaps'],
            'typography is a string' => ['typography', 'inter'],
            'typography is null' => ['typography', null],
            'pattern is a list' => ['pattern', [1, 2, 3]],
            'motion is false' => ['motion', false],
            'layout is a float' => ['layout', 1.5],
            'schema version is a word' => ['schemaVersion', 'two'],
        ];
    }

    #[Test]
    #[DataProvider('corruptKeyProvider')]
    public function it_produces_valid_defaults_for_a_corrupt_key(string $key, mixed $corruptValue): void
    {
        $blob = ThemeSettings::defaults()->toArray();
        $blob[$key] = $corruptValue;

        $settings = ThemeSettings::fromArray($blob);

        $this->assertNotSame('', $settings->accentSlug);
        $this->assertSame(ThemeSettings::CURRENT_SCHEMA_VERSION, $settings->toArray()['schemaVersion']);
        $this->assertGreaterThanOrEqual(
            LayoutSettings::MINIMUM_CONTENT_WIDTH_PIXELS,
            $settings->layout->contentWidthPixels,
        );
    }

    #[Test]
    public function it_returns_the_defaults_for_an_entirely_empty_blob(): void
    {
        $this->assertSame(ThemeSettings::defaults()->toArray(), ThemeSettings::fromArray([])->toArray());
    }

    #[Test]
    public function it_drops_a_custom_easing_curve_css_would_reject(): void
    {
        $blob = ThemeSettings::defaults()->toArray();
        $blob['motion'] = ['preset' => 'refined', 'customEasing' => [1.4, 0.0, 0.2, 1.0]];

        $this->assertNull(ThemeSettings::fromArray($blob)->motion->customEasing);
    }

    #[Test]
    public function it_drops_a_custom_easing_curve_of_the_wrong_shape(): void
    {
        $blob = ThemeSettings::defaults()->toArray();
        $blob['motion'] = ['preset' => 'refined', 'customEasing' => [0.4, 0.0]];

        $this->assertNull(ThemeSettings::fromArray($blob)->motion->customEasing);
    }

    #[Test]
    public function it_keeps_a_custom_easing_curve_css_would_honour(): void
    {
        $blob = ThemeSettings::defaults()->toArray();
        $blob['motion'] = ['preset' => 'refined', 'customEasing' => [0.2, 0.8, 0.4, 1.0]];

        $this->assertSame(
            'cubic-bezier(0.2, 0.8, 0.4, 1)',
            ThemeSettings::fromArray($blob)->motion->easing()->toCssValue(),
        );
    }

    #[Test]
    public function it_generates_palettes_from_the_custom_accent_when_one_is_set(): void
    {
        $this->assertSame('#8f6b16', self::fullyPopulated()->accentSeed()->toHex());
    }

    #[Test]
    public function it_falls_back_to_the_curated_seed_when_no_custom_accent_is_set(): void
    {
        $settings = ThemeSettings::defaults();

        $this->assertSame('#123a6b', $settings->accentSeed()->toHex());
        $this->assertSame('#7cb0e8', $settings->withCustomAccent(Srgb::fromHex('#7cb0e8'))->accentSeed()->toHex());
        $this->assertNull($settings->withCustomAccent(null)->customAccent);
    }

    #[Test]
    public function it_replaces_the_motion_block_without_touching_the_rest(): void
    {
        $settings = self::fullyPopulated()->withMotion(MotionSettings::off());

        $this->assertFalse($settings->motion->enabled);
        $this->assertSame('compass-rose', $settings->pattern->patternSlug);
        $this->assertSame('gulf-premium', $settings->typography->pairingSlug);
    }

    #[Test]
    public function it_resolves_the_visitor_facing_mode_from_the_preference(): void
    {
        $this->assertSame(ThemeMode::Light, ThemePreference::Light->resolve(ThemeMode::Dark));
        $this->assertSame(ThemeMode::Dark, ThemePreference::Dark->resolve(ThemeMode::Light));
        $this->assertSame(ThemeMode::Dark, ThemePreference::Auto->resolve(ThemeMode::Dark));
        $this->assertSame(ThemeMode::Light, ThemePreference::Auto->resolve(ThemeMode::Light));
    }

    #[Test]
    public function it_keeps_a_switched_off_pattern_switched_off_across_a_round_trip(): void
    {
        $blob = ThemeSettings::defaults()->toArray();
        $blob['pattern'] = PatternSettings::disabled()->toArray();

        $this->assertFalse(ThemeSettings::fromArray($blob)->pattern->enabled);
    }

    #[Test]
    public function it_clamps_a_wide_width_below_the_content_width_up_to_it(): void
    {
        $layout = LayoutSettings::of(1200, 800, 24, 96, 12, 1, LayoutDensity::Comfortable);

        $this->assertSame(1200, $layout->wideWidthPixels);
    }

    #[Test]
    public function it_scales_spacing_by_density(): void
    {
        $this->assertLessThan(1.0, LayoutDensity::Compact->spacingMultiplier());
        $this->assertSame(1.0, LayoutDensity::Comfortable->spacingMultiplier());
        $this->assertGreaterThan(1.0, LayoutDensity::Spacious->spacingMultiplier());
    }
}
