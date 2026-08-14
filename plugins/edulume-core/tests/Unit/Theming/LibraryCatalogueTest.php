<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Theming;

use Edulume\Core\Domain\Theming\FontLibrary;
use Edulume\Core\Domain\Theming\FontPairingLibrary;
use Edulume\Core\Domain\Theming\MotionEffect;
use Edulume\Core\Domain\Theming\StylePresetLibrary;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The catalogue accessors the admin lists things with.
 *
 * `slugs()` is what populates every picker in the configurator. Each library was tested through
 * `all()` and `get()` and never through `slugs()`, so a library whose keys drifted from its
 * entries would have passed the suite and shipped an empty dropdown.
 */
final class LibraryCatalogueTest extends TestCase
{
    #[Test]
    public function every_font_slug_resolves_to_a_family_in_the_library(): void
    {
        $slugs = FontLibrary::slugs();

        self::assertCount(count(FontLibrary::all()), $slugs);

        foreach ($slugs as $slug) {
            self::assertTrue(FontLibrary::has($slug), $slug);
        }
    }

    #[Test]
    public function the_font_library_ships_the_twenty_two_families_the_brief_asks_for(): void
    {
        self::assertCount(22, FontLibrary::slugs());
    }

    #[Test]
    public function every_pairing_slug_resolves_to_a_pairing_in_the_library(): void
    {
        $slugs = FontPairingLibrary::slugs();

        self::assertCount(count(FontPairingLibrary::all()), $slugs);

        foreach ($slugs as $slug) {
            self::assertTrue(FontPairingLibrary::has($slug), $slug);
        }
    }

    #[Test]
    public function the_pairing_library_ships_the_ten_pairings_the_brief_asks_for(): void
    {
        self::assertCount(10, FontPairingLibrary::slugs());
    }

    #[Test]
    public function every_preset_slug_resolves_to_a_preset_in_the_library(): void
    {
        $slugs = StylePresetLibrary::slugs();

        self::assertCount(count(StylePresetLibrary::all()), $slugs);

        foreach ($slugs as $slug) {
            self::assertTrue(StylePresetLibrary::has($slug), $slug);
        }
    }

    #[Test]
    public function the_preset_library_ships_the_ten_presets_the_brief_asks_for(): void
    {
        self::assertCount(10, StylePresetLibrary::slugs());
    }

    /**
     * Two effects hijack input the visitor owns — the scroll position and the pointer — so they
     * are off whatever preset is chosen. This is a product rule, not a default, which is why it
     * is asserted against the whole enum rather than against those two cases.
     */
    #[Test]
    public function only_smooth_scroll_and_the_custom_cursor_ship_switched_off(): void
    {
        $off = array_values(array_filter(
            MotionEffect::cases(),
            static fn (MotionEffect $effect): bool => $effect->shipsOff(),
        ));

        self::assertSame([MotionEffect::SmoothScroll, MotionEffect::CustomCursor], $off);
    }

    #[Test]
    public function every_other_effect_is_free_to_ship_on(): void
    {
        self::assertFalse(MotionEffect::FadeIn->shipsOff());
        self::assertFalse(MotionEffect::HoverLift->shipsOff());
        self::assertFalse(MotionEffect::ScrollProgressBar->shipsOff());
    }
}
