<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Color\ContrastRequirement;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\AccentReviewer;
use Edulume\Core\Domain\Theming\ThemeMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccentReviewerTest extends TestCase
{
    private AccentReviewer $reviewer;
    private ContrastEngine $contrastEngine;

    protected function setUp(): void
    {
        $this->contrastEngine = new ContrastEngine();
        $this->reviewer = new AccentReviewer($this->contrastEngine);
    }

    #[Test]
    public function it_accepts_a_dark_custom_hex_on_the_light_surface(): void
    {
        $review = $this->reviewer->review(Srgb::fromHex('#123a6b'));

        $this->assertTrue($review->isCompliantIn(ThemeMode::Light));
        $this->assertNull($review->suggestionFor(ThemeMode::Light));
    }

    #[Test]
    public function it_rejects_a_dark_custom_hex_on_the_dark_surface(): void
    {
        $review = $this->reviewer->review(Srgb::fromHex('#123a6b'));

        $this->assertFalse($review->isCompliantIn(ThemeMode::Dark));
        $this->assertTrue($review->needsAttention());
    }

    #[Test]
    public function it_accepts_a_pale_custom_hex_on_the_dark_surface(): void
    {
        $review = $this->reviewer->review(Srgb::fromHex('#7cb0e8'));

        $this->assertTrue($review->isCompliantIn(ThemeMode::Dark));
        $this->assertFalse($review->isCompliantIn(ThemeMode::Light));
    }

    /**
     * @return array<string, array{string, ThemeMode}>
     */
    public static function failingHexProvider(): array
    {
        return [
            'pale blue in light mode' => ['#7cb0e8', ThemeMode::Light],
            'near white in light mode' => ['#e8e8e8', ThemeMode::Light],
            'muted khaki in light mode' => ['#8a7a3f', ThemeMode::Light],
            'muted khaki in dark mode' => ['#8a7a3f', ThemeMode::Dark],
            'deep navy in dark mode' => ['#123a6b', ThemeMode::Dark],
        ];
    }

    #[Test]
    #[DataProvider('failingHexProvider')]
    public function it_suggests_a_colour_that_actually_passes(string $hex, ThemeMode $mode): void
    {
        $review = $this->reviewer->review(Srgb::fromHex($hex));
        $suggestion = $review->suggestionFor($mode);

        $this->assertNotNull($suggestion, sprintf('%s was silently accepted in %s mode.', $hex, $mode->value));
        $this->assertTrue($this->contrastEngine->meets(
            $review->pairFor($mode)->background,
            $suggestion,
            ContrastRequirement::NormalTextAa,
        ));
    }

    #[Test]
    public function it_reports_a_hex_that_fails_in_both_modes(): void
    {
        $review = $this->reviewer->review(Srgb::fromHex('#8a7a3f'));

        $this->assertFalse($review->isCompliantIn(ThemeMode::Light));
        $this->assertFalse($review->isCompliantIn(ThemeMode::Dark));
        $this->assertNotNull($review->lightModeSuggestion);
        $this->assertNotNull($review->darkModeSuggestion);
    }

    #[Test]
    public function it_keeps_the_seed_it_reviewed(): void
    {
        $this->assertSame('#7cb0e8', $this->reviewer->review(Srgb::fromHex('#7cb0e8'))->seed->toHex());
    }

    #[Test]
    public function it_reports_the_ratio_on_each_surface(): void
    {
        $review = $this->reviewer->review(Srgb::fromHex('#123a6b'));

        $this->assertGreaterThan(4.5, $review->onLightSurface->ratio);
        $this->assertLessThan(4.5, $review->onDarkSurface->ratio);
        $this->assertSame($review->onLightSurface, $review->pairFor(ThemeMode::Light));
        $this->assertSame($review->onDarkSurface, $review->pairFor(ThemeMode::Dark));
    }
}
