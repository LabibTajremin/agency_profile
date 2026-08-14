<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\InvalidPatternException;
use Edulume\Core\Domain\Theming\Pattern;
use Edulume\Core\Domain\Theming\PatternGroup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * `Pattern::of()` refuses markup it cannot tint.
 *
 * The bundled library obviously satisfies both guards, which is why they went untested — every
 * existing test builds patterns from `PatternLibrary`. The guards exist for the tile someone
 * adds later, and a guard nothing exercises is a guard nobody knows is inverted.
 */
final class PatternValidationTest extends TestCase
{
    private const TINTABLE = '<svg xmlns="http://www.w3.org/2000/svg"><rect fill="{{color}}"/></svg>';

    #[Test]
    public function it_accepts_markup_that_is_an_svg_carrying_the_tint_placeholder(): void
    {
        $pattern = Pattern::of('dot-grid', 'Dot grid', PatternGroup::Dots, self::TINTABLE);

        self::assertSame('dot-grid', $pattern->slug);
    }

    #[Test]
    public function it_rejects_markup_that_is_not_a_single_svg_element(): void
    {
        $this->expectException(InvalidPatternException::class);
        $this->expectExceptionMessage('must be a single <svg> element');

        Pattern::of('wrapped', 'Wrapped', PatternGroup::Dots, '<div>' . self::TINTABLE . '</div>');
    }

    #[Test]
    public function it_rejects_an_svg_that_stops_short_of_a_closing_tag(): void
    {
        $this->expectException(InvalidPatternException::class);

        Pattern::of('truncated', 'Truncated', PatternGroup::Lines, '<svg><rect fill="{{color}}"/>');
    }

    /**
     * Without a placeholder the tile would render, and would render in whatever colour it was
     * authored in — the same silent wrongness a missing design token produces.
     */
    #[Test]
    public function it_rejects_markup_with_no_tint_placeholder(): void
    {
        $this->expectException(InvalidPatternException::class);
        $this->expectExceptionMessage('contains no {{color}} placeholder');

        Pattern::of('fixed', 'Fixed', PatternGroup::Geometric, '<svg><rect fill="#ff0000"/></svg>');
    }

    #[Test]
    public function it_names_the_offending_slug_so_the_error_points_somewhere(): void
    {
        $this->expectExceptionMessage('"fixed"');

        Pattern::of('fixed', 'Fixed', PatternGroup::Geometric, '<svg><rect fill="#ff0000"/></svg>');
    }

    #[Test]
    public function it_returns_the_markup_untinted_with_the_placeholder_still_in_place(): void
    {
        $pattern = Pattern::of('dot-grid', 'Dot grid', PatternGroup::Dots, self::TINTABLE);

        self::assertSame(self::TINTABLE, $pattern->untintedMarkup());
        self::assertStringContainsString(Pattern::TINT_PLACEHOLDER, $pattern->untintedMarkup());
    }

    #[Test]
    public function tinting_replaces_the_placeholder_and_leaves_the_stored_markup_alone(): void
    {
        $pattern = Pattern::of('dot-grid', 'Dot grid', PatternGroup::Dots, self::TINTABLE);
        $tinted = $pattern->markupTintedWith(Srgb::fromHex('#365e92'));

        self::assertStringContainsString('#365e92', $tinted);
        self::assertStringNotContainsString(Pattern::TINT_PLACEHOLDER, $tinted);
        self::assertStringContainsString(Pattern::TINT_PLACEHOLDER, $pattern->untintedMarkup());
    }
}
