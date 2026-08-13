<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\FontRole;
use Edulume\Core\Domain\Theming\LayoutDensity;
use Edulume\Core\Domain\Theming\LayoutSettings;
use Edulume\Core\Domain\Theming\MotionSettings;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Domain\Theming\PatternAttachment;
use Edulume\Core\Domain\Theming\PatternBlendMode;
use Edulume\Core\Domain\Theming\PatternColorSource;
use Edulume\Core\Domain\Theming\PatternSettings;
use Edulume\Core\Domain\Theming\ThemeMode;
use Edulume\Core\Domain\Theming\ThemePreference;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\TokenCompiler;
use Edulume\Core\Domain\Theming\TypographySettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TokenCompilerTest extends TestCase
{
    private TokenCompiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new TokenCompiler(new PaletteGenerator(new ContrastEngine()));
    }

    /**
     * @return array<string, array{ThemeMode}>
     */
    public static function modeProvider(): array
    {
        return ['light' => [ThemeMode::Light], 'dark' => [ThemeMode::Dark]];
    }

    #[Test]
    #[DataProvider('modeProvider')]
    public function it_prefixes_every_token(ThemeMode $mode): void
    {
        foreach (array_keys($this->compiler->compile(ThemeSettings::defaults(), $mode)) as $name) {
            $this->assertStringStartsWith(TokenCompiler::PREFIX, $name);
        }
    }

    #[Test]
    #[DataProvider('modeProvider')]
    public function it_never_emits_an_empty_value(ThemeMode $mode): void
    {
        foreach ($this->compiler->compile(ThemeSettings::defaults(), $mode) as $name => $value) {
            $this->assertNotSame('', trim($value), sprintf('%s came out empty.', $name));
        }
    }

    #[Test]
    public function it_emits_every_accent_step_with_its_foreground(): void
    {
        $tokens = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Light);

        for ($index = 0; $index < 11; $index++) {
            $this->assertArrayHasKey(TokenCompiler::PREFIX . 'accent-' . $index, $tokens);
            $this->assertArrayHasKey(TokenCompiler::PREFIX . 'on-accent-' . $index, $tokens);
        }
    }

    #[Test]
    public function it_flips_the_surface_and_ink_between_modes(): void
    {
        $light = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Light);
        $dark = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Dark);

        $this->assertSame($light[TokenCompiler::PREFIX . 'surface'], $dark[TokenCompiler::PREFIX . 'ink']);
        $this->assertSame($light[TokenCompiler::PREFIX . 'ink'], $dark[TokenCompiler::PREFIX . 'surface']);
    }

    #[Test]
    public function it_emits_a_family_for_every_typographic_role(): void
    {
        $tokens = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Light);

        foreach (FontRole::cases() as $role) {
            $this->assertArrayHasKey(TokenCompiler::PREFIX . 'font-' . $role->value, $tokens);
        }
    }

    #[Test]
    public function it_names_negative_type_steps_without_a_minus_sign(): void
    {
        $tokens = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Light);

        $this->assertArrayHasKey(TokenCompiler::PREFIX . 'font-size-n2', $tokens);
        $this->assertArrayHasKey(TokenCompiler::PREFIX . 'font-size-0', $tokens);
        $this->assertArrayHasKey(TokenCompiler::PREFIX . 'font-size-6', $tokens);
    }

    #[Test]
    public function it_emits_lengths_in_rem_not_pixels(): void
    {
        $tokens = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Light);

        foreach (['content-width', 'wide-width', 'gutter', 'section-spacing', 'radius', 'border-width'] as $name) {
            $this->assertStringEndsWith('rem', $tokens[TokenCompiler::PREFIX . $name]);
        }
    }

    #[Test]
    public function it_scales_the_section_spacing_by_density(): void
    {
        $spacious = $this->settingsWithLayout(LayoutDensity::Spacious);
        $compact = $this->settingsWithLayout(LayoutDensity::Compact);

        $spaciousToken = $this->compiler->compile($spacious, ThemeMode::Light)[TokenCompiler::PREFIX . 'section-spacing'];
        $compactToken = $this->compiler->compile($compact, ThemeMode::Light)[TokenCompiler::PREFIX . 'section-spacing'];

        $this->assertGreaterThan((float) $compactToken, (float) $spaciousToken);
    }

    #[Test]
    public function it_zeroes_the_motion_tokens_when_motion_is_off(): void
    {
        $settings = ThemeSettings::defaults()->withMotion(MotionSettings::off());

        $tokens = $this->compiler->compile($settings, ThemeMode::Light);

        $this->assertSame('0ms', $tokens[TokenCompiler::PREFIX . 'duration-base']);
        $this->assertSame('0ms', $tokens[TokenCompiler::PREFIX . 'stagger-step']);
        $this->assertSame('0rem', $tokens[TokenCompiler::PREFIX . 'motion-travel']);
    }

    #[Test]
    public function it_emits_a_pattern_data_uri_wrapped_in_url(): void
    {
        $tokens = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Light);

        $this->assertStringStartsWith('url("data:image/svg+xml,', $tokens[TokenCompiler::PREFIX . 'pattern-image']);
    }

    #[Test]
    public function it_emits_no_pattern_image_when_the_pattern_is_off(): void
    {
        $stored = ThemeSettings::defaults()->toArray();
        $stored['pattern'] = PatternSettings::disabled()->toArray();

        $tokens = $this->compiler->compile(ThemeSettings::fromArray($stored), ThemeMode::Light);

        $this->assertSame('none', $tokens[TokenCompiler::PREFIX . 'pattern-image']);
        $this->assertSame('0', $tokens[TokenCompiler::PREFIX . 'pattern-opacity']);
    }

    #[Test]
    public function it_tints_a_neutral_pattern_against_the_mode(): void
    {
        $settings = $this->settingsWithPatternSource(PatternColorSource::Neutral);

        $light = $this->compiler->compile($settings, ThemeMode::Light)[TokenCompiler::PREFIX . 'pattern-image'];
        $dark = $this->compiler->compile($settings, ThemeMode::Dark)[TokenCompiler::PREFIX . 'pattern-image'];

        $this->assertNotSame($light, $dark);
    }

    #[Test]
    public function it_tints_an_accent_pattern_from_the_palette(): void
    {
        $settings = $this->settingsWithPatternSource(PatternColorSource::Accent);

        $tokens = $this->compiler->compile($settings, ThemeMode::Light);
        $expectedTint = rawurlencode($tokens[TokenCompiler::PREFIX . 'accent']);

        $this->assertStringContainsString($expectedTint, $tokens[TokenCompiler::PREFIX . 'pattern-image']);
    }

    #[Test]
    public function it_tints_a_custom_pattern_from_the_palette_too(): void
    {
        $settings = $this->settingsWithPatternSource(PatternColorSource::Custom);

        $this->assertStringStartsWith(
            'url("data:image/svg+xml,',
            $this->compiler->compile($settings, ThemeMode::Light)[TokenCompiler::PREFIX . 'pattern-image'],
        );
    }

    #[Test]
    public function it_follows_the_custom_accent_when_one_is_set(): void
    {
        $settings = ThemeSettings::defaults()->withCustomAccent(Srgb::fromHex('#7a2e6b'));

        $tokens = $this->compiler->compile($settings, ThemeMode::Light);

        $this->assertNotSame(
            $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Light)[TokenCompiler::PREFIX . 'accent'],
            $tokens[TokenCompiler::PREFIX . 'accent'],
        );
    }

    private function settingsWithLayout(LayoutDensity $density): ThemeSettings
    {
        $defaults = ThemeSettings::defaults();

        return ThemeSettings::of(
            $defaults->accentSlug,
            null,
            ThemePreference::Auto,
            true,
            TypographySettings::defaults(),
            $defaults->pattern,
            $defaults->motion,
            LayoutSettings::of(1200, 1440, 24, 96, 12, 1, $density),
        );
    }

    private function settingsWithPatternSource(PatternColorSource $source): ThemeSettings
    {
        $defaults = ThemeSettings::defaults();

        return ThemeSettings::of(
            $defaults->accentSlug,
            null,
            ThemePreference::Auto,
            true,
            $defaults->typography,
            PatternSettings::of(
                'dot-grid',
                0.06,
                0.1,
                1.0,
                $source,
                0,
                PatternBlendMode::Normal,
                PatternAttachment::Scroll,
            ),
            $defaults->motion,
            $defaults->layout,
        );
    }
}
