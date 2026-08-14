<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Application\Theming\CompiledStylesheet;
use Edulume\Core\Application\Theming\CompileStylesheet;
use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\SectionOverrideKey;
use Edulume\Core\Domain\Theming\SectionResolver;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\TokenCompiler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CompileStylesheetTest extends TestCase
{
    private CompileStylesheet $compile;

    protected function setUp(): void
    {
        $this->compile = new CompileStylesheet(
            new TokenCompiler(new PaletteGenerator(new ContrastEngine())),
            new SectionResolver(),
        );
    }

    /**
     * @param array<string, SectionOverride> $overrides
     */
    private function css(array $overrides = [], ?ThemeSettings $settings = null): string
    {
        return ($this->compile)($settings ?? ThemeSettings::defaults(), $overrides)->css;
    }

    #[Test]
    public function it_balances_every_brace(): void
    {
        $css = $this->css();

        $this->assertSame(substr_count($css, '{'), substr_count($css, '}'));
    }

    #[Test]
    public function it_terminates_every_declaration(): void
    {
        foreach (explode("\n", $this->css()) as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_ends_with($trimmed, '{') || $trimmed === '}' || str_ends_with($trimmed, ',')) {
                continue;
            }

            $this->assertStringEndsWith(';', $trimmed, sprintf('Unterminated declaration: %s', $trimmed));
        }
    }

    #[Test]
    public function it_never_emits_an_empty_rule(): void
    {
        $this->assertDoesNotMatchRegularExpression('/\{\s*\}/', $this->css());
    }

    #[Test]
    public function it_declares_every_custom_property_it_references(): void
    {
        $css = $this->css();

        preg_match_all('/var\((--edulume-[a-z0-9-]+)/', $css, $references);
        preg_match_all('/^\s*(--edulume-[a-z0-9-]+):/m', $css, $declarations);

        $this->assertNotEmpty($references[1]);

        foreach (array_unique($references[1]) as $reference) {
            $this->assertContains($reference, $declarations[1], sprintf('%s is used but never declared.', $reference));
        }
    }

    #[Test]
    public function it_opens_with_the_root_block(): void
    {
        $this->assertStringStartsWith(':root {', $this->css());
    }

    #[Test]
    public function it_re_declares_only_what_dark_mode_actually_changes(): void
    {
        $css = $this->css();

        $this->assertStringContainsString('[data-theme="dark"] {', $css);

        preg_match('/\[data-theme="dark"\] \{(.*?)\n\}/s', $css, $matches);
        preg_match('/^:root \{(.*?)\n\}/s', $css, $rootMatches);

        $this->assertNotEmpty($matches);
        $this->assertLessThan(
            substr_count($rootMatches[1], ';'),
            substr_count($matches[1], ';'),
            'Dark mode re-declared every token instead of only the ones that differ.',
        );
    }

    /**
     * Source order decides the winner in CSS, so a visitor who asked their operating system
     * for less motion must be the last word in the file.
     */
    #[Test]
    public function it_emits_the_reduced_motion_block_last(): void
    {
        $css = $this->css([
            'hero' => SectionOverride::inheritEverything()->with(SectionOverrideKey::AccentSlug, 'ivy-green'),
        ]);

        $position = strpos($css, '@media (prefers-reduced-motion: reduce)');

        $this->assertIsInt($position);
        $this->assertSame('', trim(substr($css, $position + strlen(substr($css, $position)))));
        $this->assertStringEndsWith("}\n", $css);
        $this->assertSame(strrpos($css, '@media'), $position);
    }

    #[Test]
    public function it_zeroes_the_motion_tokens_in_the_reduced_motion_block(): void
    {
        $css = $this->css();
        $block = substr($css, (int) strpos($css, '@media (prefers-reduced-motion: reduce)'));

        foreach (['duration-fast', 'duration-base', 'duration-slow', 'stagger-step'] as $token) {
            $this->assertStringContainsString(TokenCompiler::PREFIX . $token . ': 0ms;', $block);
        }
    }

    #[Test]
    public function it_scopes_an_overridden_section(): void
    {
        $css = $this->css([
            'hero' => SectionOverride::inheritEverything()->with(SectionOverrideKey::AccentSlug, 'ivy-green'),
        ]);

        $this->assertStringContainsString('[data-edulume-section="hero"] {', $css);
        $this->assertStringContainsString('[data-theme="dark"] [data-edulume-section="hero"] {', $css);
    }

    #[Test]
    public function it_emits_no_section_block_for_a_section_that_inherits_everything(): void
    {
        $css = $this->css(['hero' => SectionOverride::inheritEverything()]);

        $this->assertStringNotContainsString('data-edulume-section', $css);
    }

    #[Test]
    public function it_emits_a_section_block_only_for_the_sections_that_diverge(): void
    {
        $css = $this->css([
            'hero' => SectionOverride::inheritEverything()->with(SectionOverrideKey::CornerRadiusPixels, 32),
            'footer' => SectionOverride::inheritEverything(),
        ]);

        $this->assertStringContainsString('[data-edulume-section="hero"]', $css);
        $this->assertStringNotContainsString('[data-edulume-section="footer"]', $css);
    }

    #[Test]
    public function it_keeps_the_hash_stable_when_nothing_changes(): void
    {
        $first = ($this->compile)(ThemeSettings::defaults());
        $second = ($this->compile)(ThemeSettings::defaults());

        $this->assertSame($first->hash, $second->hash);
        $this->assertSame($first->fileName(), $second->fileName());
    }

    #[Test]
    public function it_changes_the_hash_when_a_setting_moves(): void
    {
        $before = ($this->compile)(ThemeSettings::defaults());
        $after = ($this->compile)(ThemeSettings::defaults()->withCustomAccent(Srgb::fromHex('#7a2e6b')));

        $this->assertNotSame($before->hash, $after->hash);
    }

    #[Test]
    public function it_changes_the_hash_when_a_section_override_appears(): void
    {
        $before = ($this->compile)(ThemeSettings::defaults());
        $after = ($this->compile)(ThemeSettings::defaults(), [
            'hero' => SectionOverride::inheritEverything()->with(SectionOverrideKey::CornerRadiusPixels, 32),
        ]);

        $this->assertNotSame($before->hash, $after->hash);
    }

    #[Test]
    public function it_names_the_file_after_the_content_hash(): void
    {
        $sheet = ($this->compile)(ThemeSettings::defaults());

        $this->assertSame(
            CompiledStylesheet::FILE_NAME_PREFIX . $sheet->hash . CompiledStylesheet::FILE_EXTENSION,
            $sheet->fileName(),
        );
        $this->assertSame(strlen($sheet->css), $sheet->byteLength());
    }

    #[Test]
    public function it_stays_small_enough_to_serve_as_a_static_file(): void
    {
        $this->assertLessThan(60_000, ($this->compile)(ThemeSettings::defaults())->byteLength());
    }
}
