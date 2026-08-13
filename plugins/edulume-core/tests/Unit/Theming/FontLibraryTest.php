<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\FontCategory;
use Edulume\Core\Domain\Theming\FontLibrary;
use Edulume\Core\Domain\Theming\FontSubset;
use Edulume\Core\Domain\Theming\InvalidTypographyException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FontLibraryTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function familyProvider(): array
    {
        $cases = [];

        foreach (FontLibrary::slugs() as $slug) {
            $cases[$slug] = [$slug];
        }

        return $cases;
    }

    #[Test]
    public function it_ships_twenty_two_families(): void
    {
        $this->assertCount(FontLibrary::FAMILY_COUNT, FontLibrary::all());
    }

    /**
     * The licence gate from the build rules, enforced by a test rather than by memory: a
     * bundled family with no CREDITS.md row is a failing build.
     */
    #[Test]
    #[DataProvider('familyProvider')]
    public function it_records_every_bundled_family_in_credits(string $slug): void
    {
        $credits = file_get_contents(dirname(__DIR__, 5) . '/CREDITS.md');

        $this->assertIsString($credits);
        $this->assertStringContainsString(FontLibrary::get($slug)->name, $credits);
    }

    #[Test]
    #[DataProvider('familyProvider')]
    public function it_licenses_every_family_for_redistribution(string $slug): void
    {
        $family = FontLibrary::get($slug);

        $this->assertSame(FontLibrary::OPEN_FONT_LICENCE, $family->licence);
        $this->assertStringStartsWith('https://', $family->licenceUrl);
    }

    #[Test]
    #[DataProvider('familyProvider')]
    public function it_gives_every_family_weights_and_subsets(string $slug): void
    {
        $family = FontLibrary::get($slug);

        $this->assertNotEmpty($family->weights);
        $this->assertContains(400, $family->weights);
        $this->assertTrue($family->supports(FontSubset::Latin));
    }

    #[Test]
    #[DataProvider('familyProvider')]
    public function it_closes_every_css_stack_with_a_generic_family(string $slug): void
    {
        $family = FontLibrary::get($slug);

        $this->assertStringEndsWith($family->category->cssFallback(), $family->toCssStack());
    }

    #[Test]
    public function it_quotes_a_family_name_containing_a_space(): void
    {
        $this->assertSame('"Plus Jakarta Sans", sans-serif', FontLibrary::get('plus-jakarta-sans')->toCssStack());
    }

    #[Test]
    public function it_leaves_a_single_word_family_name_unquoted(): void
    {
        $this->assertSame('Inter, sans-serif', FontLibrary::get('inter')->toCssStack());
    }

    #[Test]
    public function it_ships_a_family_for_arabic_bengali_and_devanagari(): void
    {
        $this->assertTrue(FontLibrary::get('noto-sans-arabic')->supports(FontSubset::Arabic));
        $this->assertTrue(FontLibrary::get('noto-sans-bengali')->supports(FontSubset::Bengali));
        $this->assertTrue(FontLibrary::get('noto-sans-devanagari')->supports(FontSubset::Devanagari));
    }

    #[Test]
    public function it_reports_only_the_subsets_a_family_can_serve(): void
    {
        $available = FontLibrary::get('inter')->subsetsWithin(FontSubset::Latin, FontSubset::Arabic);

        $this->assertSame([FontSubset::Latin], $available);
    }

    #[Test]
    public function it_groups_every_family_into_a_category(): void
    {
        $grouped = 0;

        foreach (FontCategory::cases() as $category) {
            $grouped += count(FontLibrary::inCategory($category));
        }

        $this->assertSame(FontLibrary::FAMILY_COUNT, $grouped);
    }

    #[Test]
    public function it_reports_whether_a_slug_is_bundled(): void
    {
        $this->assertTrue(FontLibrary::has('inter'));
        $this->assertFalse(FontLibrary::has('comic-neue-extreme'));
    }

    #[Test]
    public function it_rejects_a_family_it_does_not_bundle(): void
    {
        $this->expectException(InvalidTypographyException::class);

        FontLibrary::get('comic-neue-extreme');
    }
}
