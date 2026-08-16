<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Chrome;

use Edulume\Core\Domain\Chrome\ChromeSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Whether the logo wall prints partner names.
 *
 * On by default, because a wall of unlabelled marks is a recognition test the visitor did not
 * ask to sit: it works for household brands and fails for a regional accreditor, which is most
 * of what a consultancy lists.
 */
final class PartnerDisplayTest extends TestCase
{
    #[Test]
    public function names_are_shown_unless_a_site_says_otherwise(): void
    {
        self::assertTrue(ChromeSettings::fromArray([])->showsPartnerNames);
        self::assertTrue((new ChromeSettings())->showsPartnerNames);
    }

    #[Test]
    public function a_site_can_switch_the_names_off(): void
    {
        self::assertFalse(ChromeSettings::fromArray(['showsPartnerNames' => false])->showsPartnerNames);
    }

    /**
     * The value arrives from REST, from a form post and from imported JSON, so it is coerced
     * rather than trusted — `"0"` from a checkbox must mean off, not "a non-empty string".
     *
     * @param mixed $stored
     */
    #[Test]
    #[DataProvider('falsyValues')]
    public function a_stored_value_is_coerced(mixed $stored): void
    {
        self::assertFalse(ChromeSettings::fromArray(['showsPartnerNames' => $stored])->showsPartnerNames);
    }

    /**
     * @return list<array{mixed}>
     */
    public static function falsyValues(): array
    {
        return [[false], [0], ['0'], [''], ['false']];
    }

    /**
     * The setting has to survive a save/load cycle, or switching it off appears to work and
     * then reverts the next time the settings are read.
     */
    #[Test]
    public function it_survives_a_round_trip_through_storage(): void
    {
        $off = ChromeSettings::fromArray(['showsPartnerNames' => false]);

        self::assertFalse(ChromeSettings::fromArray($off->toArray())->showsPartnerNames);

        $on = ChromeSettings::fromArray(['showsPartnerNames' => true]);

        self::assertTrue(ChromeSettings::fromArray($on->toArray())->showsPartnerNames);
    }

    #[Test]
    public function it_is_exported_so_the_admin_can_read_it_back(): void
    {
        self::assertArrayHasKey('showsPartnerNames', (new ChromeSettings())->toArray());
    }

    #[Test]
    public function switching_it_off_changes_nothing_else(): void
    {
        $on = ChromeSettings::fromArray([])->toArray();
        $off = ChromeSettings::fromArray(['showsPartnerNames' => false])->toArray();

        unset($on['showsPartnerNames'], $off['showsPartnerNames']);

        self::assertSame($on, $off);
    }
}
