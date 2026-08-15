<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Demo;

use Edulume\Core\Domain\Demo\DemoLibrary;
use Edulume\Core\Infrastructure\Demo\WpDemoStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The demo payloads resolve from disk, and hold what the library says they hold.
 *
 * `WpDemoStore::itemsFor()` resolved its path two directories up instead of three, landing on
 * `<plugin>/src/demos`, which does not exist. Every import then created nothing at all — and
 * reported success, because an unreadable file returns an empty list and an importer with
 * nothing to import finishes immediately. A site owner ran the command, saw "Imported
 * Boutique practice", and got an empty site.
 *
 * Nothing caught it because every other demo test used a fake store. This one reads the real
 * files through the real class, which is the only thing that can prove a path is right.
 */
final class DemoPayloadTest extends TestCase
{
    #[Test]
    public function the_boutique_demo_resolves_every_post_type_it_declares(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        $store = new WpDemoStore();

        foreach ($demo->itemCounts as $postType => $declared) {
            self::assertCount(
                $declared,
                $store->itemsFor($demo, $postType),
                sprintf('%s should carry %d item(s)', $postType, $declared),
            );
        }
    }

    #[Test]
    public function the_boutique_demo_totals_what_the_library_advertises(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        $store = new WpDemoStore();
        $found = 0;

        foreach (array_keys($demo->itemCounts) as $postType) {
            $found += count($store->itemsFor($demo, $postType));
        }

        self::assertSame($demo->totalItems(), $found);
    }

    /**
     * An item with no title imports as an untitled row, which is worse than not importing it:
     * it looks like content until somebody opens it.
     */
    #[Test]
    public function every_boutique_item_carries_a_title(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        $store = new WpDemoStore();

        foreach (array_keys($demo->itemCounts) as $postType) {
            foreach ($store->itemsFor($demo, $postType) as $index => $item) {
                self::assertArrayHasKey('title', $item, $postType . '[' . $index . ']');
                self::assertNotSame('', trim((string) $item['title']), $postType . '[' . $index . ']');
            }
        }
    }

    #[Test]
    public function an_unknown_post_type_yields_nothing_rather_than_failing(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        self::assertSame([], (new WpDemoStore())->itemsFor($demo, 'edulume_not_a_type'));
    }
}
