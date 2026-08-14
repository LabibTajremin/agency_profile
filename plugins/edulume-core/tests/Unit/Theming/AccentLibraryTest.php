<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\AccentLibrary;
use Edulume\Core\Domain\Theming\UnknownAccentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccentLibraryTest extends TestCase
{
    #[Test]
    public function it_ships_twenty_four_curated_accents(): void
    {
        $this->assertCount(AccentLibrary::ACCENT_COUNT, AccentLibrary::all());
        $this->assertCount(AccentLibrary::ACCENT_COUNT, AccentLibrary::slugs());
    }

    #[Test]
    public function it_gives_every_accent_a_unique_slug_and_name(): void
    {
        $slugs = [];
        $names = [];

        foreach (AccentLibrary::all() as $accent) {
            $slugs[] = $accent->slug;
            $names[] = $accent->name;
        }

        $this->assertSame($slugs, array_values(array_unique($slugs)));
        $this->assertSame($names, array_values(array_unique($names)));
    }

    #[Test]
    public function it_uses_kebab_case_slugs(): void
    {
        foreach (AccentLibrary::slugs() as $slug) {
            $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+)*$/', $slug);
        }
    }

    #[Test]
    public function it_looks_an_accent_up_by_slug(): void
    {
        $accent = AccentLibrary::get('oxford-blue');

        $this->assertSame('Oxford Blue', $accent->name);
        $this->assertSame('#123a6b', $accent->seed->toHex());
    }

    #[Test]
    public function it_reports_whether_a_slug_is_curated(): void
    {
        $this->assertTrue(AccentLibrary::has('oxford-blue'));
        $this->assertFalse(AccentLibrary::has('taupe-surprise'));
    }

    #[Test]
    public function it_rejects_a_slug_it_does_not_know(): void
    {
        $this->expectException(UnknownAccentException::class);

        AccentLibrary::get('taupe-surprise');
    }
}
