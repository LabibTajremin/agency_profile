<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Color\ColorSpace;
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

    /**
     * The modes invert, but they are no longer the same ramp read backwards.
     *
     * This used to assert `light.surface === dark.ink` exactly, which held while both modes were
     * built from one accent-tinted scale. Dark mode now has its own ramp so that its background
     * can be midnight blue and its text cream — and those two cannot come from the ends of a
     * single scale, because step 0 is simultaneously the light-mode background and the dark-mode
     * ink. What has to remain true is the inversion itself, which is what this asserts.
     */
    #[Test]
    public function it_inverts_surface_and_ink_between_modes(): void
    {
        $light = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Light);
        $dark = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Dark);

        $lightSurface = Srgb::fromHex($light[TokenCompiler::PREFIX . 'surface'])->relativeLuminance();
        $lightInk = Srgb::fromHex($light[TokenCompiler::PREFIX . 'ink'])->relativeLuminance();
        $darkSurface = Srgb::fromHex($dark[TokenCompiler::PREFIX . 'surface'])->relativeLuminance();
        $darkInk = Srgb::fromHex($dark[TokenCompiler::PREFIX . 'ink'])->relativeLuminance();

        $this->assertGreaterThan($lightInk, $lightSurface, 'light mode is dark ink on a light page');
        $this->assertGreaterThan($darkSurface, $darkInk, 'dark mode is light ink on a dark page');
        $this->assertGreaterThan($darkSurface, $lightSurface, 'the dark page is the darker of the two');
    }

    /**
     * A dark mode that bottoms out at black reads as black on an OLED panel, and pure white ink
     * on it produces the halo that makes long-form reading tiring. The brief asked for a
     * blackish midnight blue with cream text; these are the measurements of that.
     */
    #[Test]
    public function dark_mode_is_midnight_blue_rather_than_black(): void
    {
        $dark = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Dark);
        $surface = ColorSpace::srgbToOklch(Srgb::fromHex($dark[TokenCompiler::PREFIX . 'surface']));

        // Blue, by hue, rather than an incidentally cool grey.
        $this->assertGreaterThan(220.0, $surface->hue);
        $this->assertLessThan(290.0, $surface->hue);

        // Enough chroma to be seen as a colour, and light enough not to be black.
        $this->assertGreaterThan(0.02, $surface->chroma);
        $this->assertGreaterThan(0.10, $surface->lightness);
        $this->assertLessThan(0.30, $surface->lightness);
    }

    #[Test]
    public function dark_mode_text_is_cream_rather_than_white(): void
    {
        $dark = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Dark);
        $ink = ColorSpace::srgbToOklch(Srgb::fromHex($dark[TokenCompiler::PREFIX . 'ink']));

        // Warm: a yellow-orange hue, not the cool blue-white of the light-mode scale.
        $this->assertGreaterThan(40.0, $ink->hue);
        $this->assertLessThan(120.0, $ink->hue);

        // Off-white rather than white — full lightness is the glare this avoids.
        $this->assertGreaterThan(0.88, $ink->lightness);
        $this->assertLessThan(0.99, $ink->lightness);
        $this->assertGreaterThan(0.01, $ink->chroma);
    }

    #[Test]
    public function light_mode_text_stays_dark(): void
    {
        $light = $this->compiler->compile(ThemeSettings::defaults(), ThemeMode::Light);
        $ink = ColorSpace::srgbToOklch(Srgb::fromHex($light[TokenCompiler::PREFIX . 'ink']));

        $this->assertLessThan(0.30, $ink->lightness);
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
