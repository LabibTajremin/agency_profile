<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\ContrastRequirement;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\AccentLibrary;
use Edulume\Core\Domain\Theming\AdminTheme;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Domain\Theming\ThemeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AdminThemeTest extends TestCase
{
    private AdminTheme $adminTheme;

    protected function setUp(): void
    {
        $contrastEngine = new ContrastEngine();

        $this->adminTheme = new AdminTheme(new PaletteGenerator($contrastEngine), $contrastEngine);
    }

    /**
     * Every curated accent, in both admin modes — the matrix the acceptance criterion names.
     *
     * @return array<string, array{string, ThemeMode}>
     */
    public static function accentAndModeProvider(): array
    {
        $cases = [];

        foreach (AccentLibrary::all() as $accent) {
            foreach ([ThemeMode::Light, ThemeMode::Dark] as $mode) {
                $cases[$accent->slug . ' / ' . $mode->value] = [$accent->slug, $mode];
            }
        }

        return $cases;
    }

    #[Test]
    public function it_covers_all_twenty_four_accents_in_both_admin_modes(): void
    {
        $this->assertCount(AccentLibrary::ACCENT_COUNT * 2, self::accentAndModeProvider());
    }

    #[Test]
    #[DataProvider('accentAndModeProvider')]
    public function it_meets_aa_on_every_admin_pair_for_every_accent(string $slug, ThemeMode $mode): void
    {
        $seed = AccentLibrary::get($slug)->seed;

        foreach ($this->adminTheme->auditPairs($seed, $mode) as $name => $pair) {
            $this->assertTrue(
                $pair->meets(ContrastRequirement::NormalTextAa),
                sprintf(
                    '%s in %s mode: %s renders %s on %s at only %.2f:1.',
                    $slug,
                    $mode->value,
                    $name,
                    $pair->foreground->toHex(),
                    $pair->background->toHex(),
                    $pair->ratio,
                ),
            );
        }
    }

    #[Test]
    #[DataProvider('accentAndModeProvider')]
    public function it_gives_the_focus_ring_enough_contrast_to_be_findable(string $slug, ThemeMode $mode): void
    {
        $pair = $this->adminTheme->focusRingContrast(AccentLibrary::get($slug)->seed, $mode);

        $this->assertTrue(
            $pair->meets(ContrastRequirement::NonText),
            sprintf('%s in %s mode: the focus ring sits at only %.2f:1.', $slug, $mode->value, $pair->ratio),
        );
    }

    #[Test]
    #[DataProvider('accentAndModeProvider')]
    public function it_reports_itself_compliant_for_every_accent(string $slug, ThemeMode $mode): void
    {
        $this->assertTrue($this->adminTheme->meetsContrastRequirements(AccentLibrary::get($slug)->seed, $mode));
    }

    #[Test]
    #[DataProvider('accentAndModeProvider')]
    public function it_prefixes_and_fills_every_admin_token(string $slug, ThemeMode $mode): void
    {
        $tokens = $this->adminTheme->compile(AccentLibrary::get($slug)->seed, $mode);

        $this->assertCount(9, $tokens);

        foreach ($tokens as $name => $value) {
            $this->assertStringStartsWith(AdminTheme::PREFIX, $name);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $value);
        }
    }

    /**
     * The admin's light/dark choice is its own. Someone editing at night wants a dark admin
     * whether or not the public site offers one.
     */
    #[Test]
    public function it_keeps_the_admin_mode_independent_of_the_public_site(): void
    {
        $seed = AccentLibrary::get('oxford-blue')->seed;

        $light = $this->adminTheme->compile($seed, ThemeMode::Light);
        $dark = $this->adminTheme->compile($seed, ThemeMode::Dark);

        $this->assertNotSame($light[AdminTheme::PREFIX . 'surface'], $dark[AdminTheme::PREFIX . 'surface']);
        $this->assertSame($light[AdminTheme::PREFIX . 'surface'], $dark[AdminTheme::PREFIX . 'ink']);
    }

    #[Test]
    public function it_holds_aa_for_a_custom_accent_too(): void
    {
        foreach (['#8a7a3f', '#e8e8e8', '#101010', '#7cb0e8'] as $hex) {
            foreach ([ThemeMode::Light, ThemeMode::Dark] as $mode) {
                $this->assertTrue(
                    $this->adminTheme->meetsContrastRequirements(Srgb::fromHex($hex), $mode),
                    sprintf('Custom accent %s failed in %s mode.', $hex, $mode->value),
                );
            }
        }
    }

    #[Test]
    public function it_exposes_the_pair_an_audit_checks_first(): void
    {
        $pair = $this->adminTheme->bodyTextContrast(AccentLibrary::get('gulf-gold')->seed, ThemeMode::Light);

        $this->assertTrue($pair->meets(ContrastRequirement::NormalTextAaa));
    }
}
