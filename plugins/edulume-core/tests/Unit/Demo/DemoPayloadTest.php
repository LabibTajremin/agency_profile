<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Demo;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Demo\DemoLibrary;
use Edulume\Core\Domain\Security\SvgSanitiser;
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

    /**
     * Demo content that announces itself as demo content is a defect, not a disclaimer.
     *
     * The first version of this pack shipped rows titled "Course 1" whose body read "Replace
     * this with your own copy before launch". That fails twice over: nobody evaluating the theme
     * can judge a section full of numbered stubs, and a site launched under time pressure
     * publishes the instruction-to-self as live copy on a real domain.
     */
    #[Test]
    public function no_item_carries_placeholder_copy(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        $store = new WpDemoStore();
        $banned = ['replace this', 'lorem ipsum', 'your own copy', 'placeholder', 'sample text', 'tbd'];

        foreach (array_keys($demo->itemCounts) as $postType) {
            foreach ($store->itemsFor($demo, $postType) as $index => $row) {
                $haystack = strtolower(implode(' ', [
                    (string) ($row['title'] ?? ''),
                    (string) ($row['excerpt'] ?? ''),
                    (string) ($row['content'] ?? ''),
                ]));

                foreach ($banned as $needle) {
                    self::assertStringNotContainsString(
                        $needle,
                        $haystack,
                        sprintf('%s[%d] reads as placeholder copy', $postType, $index),
                    );
                }
            }
        }
    }

    /**
     * "Course 1", "Institution 7" — a title that is a noun plus its index is a stub with a
     * number on it, and it is instantly recognisable as one on a live page.
     */
    #[Test]
    public function no_title_is_a_noun_with_an_index_stuck_on_it(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        $store = new WpDemoStore();

        foreach (array_keys($demo->itemCounts) as $postType) {
            foreach ($store->itemsFor($demo, $postType) as $row) {
                self::assertDoesNotMatchRegularExpression(
                    '/^[A-Za-z ]+\s\d+$/',
                    (string) ($row['title'] ?? ''),
                    sprintf('%s: "%s" is a numbered stub', $postType, (string) ($row['title'] ?? '')),
                );
            }
        }
    }

    /**
     * A section renders empty when its post type has no rows, so "every section is populated"
     * is a property of the pack covering the whole content model — not of any one file.
     */
    #[Test]
    public function the_pack_covers_every_post_type_the_content_model_registers(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        $registered = array_map(
            static fn (object $type): string => $type->key,
            \Edulume\Core\Domain\Content\ContentModel::postTypes(),
        );

        foreach ($registered as $key) {
            self::assertArrayHasKey(
                $key,
                $demo->itemCounts,
                sprintf('%s has no demo content, so its section imports empty', $key),
            );
        }
    }

    /**
     * Every image an item names must actually be on disk.
     *
     * A missing file does not throw — the importer skips it — so a typo here would show up as
     * one card without a picture on a live site, which is precisely the kind of thing nobody
     * notices until a client does.
     */
    #[Test]
    public function every_image_an_item_names_exists_in_the_media_directory(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        $store = new WpDemoStore();
        $media = dirname(__DIR__, 3) . '/demos/boutique/media';
        $withImages = 0;

        foreach (array_keys($demo->itemCounts) as $postType) {
            foreach ($store->itemsFor($demo, $postType) as $index => $row) {
                if (!isset($row['image'])) {
                    continue;
                }

                $withImages++;

                self::assertFileExists(
                    $media . '/' . (string) $row['image'],
                    sprintf('%s[%d] names an image that is not bundled', $postType, $index),
                );
            }
        }

        self::assertGreaterThan(100, $withImages, 'the pack should be largely illustrated');
    }

    /**
     * Bundled SVG survives the sanitiser the importer runs it through.
     *
     * If artwork were stripped to nothing on the way in, every import would silently produce
     * posts with no featured image and the cause would be three layers away from the symptom.
     */
    #[Test]
    public function every_bundled_svg_survives_sanitisation(): void
    {
        $sanitiser = new SvgSanitiser();
        $media = dirname(__DIR__, 3) . '/demos/boutique/media';
        $files = glob($media . '/*.svg');

        self::assertNotFalse($files);
        self::assertGreaterThan(0, count($files));

        foreach ($files as $file) {
            $sanitised = $sanitiser->sanitise((string) file_get_contents($file));

            self::assertNotSame('', $sanitised, basename($file));
            self::assertStringContainsString('<svg', $sanitised, basename($file));
        }
    }

    #[Test]
    public function the_credits_file_accounts_for_every_bundled_image(): void
    {
        $media = dirname(__DIR__, 3) . '/demos/boutique/media';
        $credits = json_decode((string) file_get_contents($media . '/../media-credits.json'), true);

        self::assertIsArray($credits);
        self::assertIsArray($credits['images'] ?? null);

        $recorded = array_map(static fn (array $row): string => (string) $row['file'], $credits['images']);
        $onDisk = array_map('basename', glob($media . '/*.svg') ?: []);

        sort($recorded);
        sort($onDisk);

        self::assertSame($onDisk, $recorded, 'every bundled image must have a licence recorded');
    }

    /**
     * A relationship declared in a pack has to name a real relationship, in the right direction,
     * pointing at an item the same pack ships.
     *
     * The institutions used to attach themselves to a country through `terms.edulume_destination`
     * — a taxonomy the content model never registers. `wp_set_object_terms` returns an error for
     * an unknown taxonomy and the importer moves on, so the import reported success and every
     * country page on the demo site read "University listings for this country are on their way"
     * with twenty-four universities sitting in the database.
     */
    #[Test]
    public function every_declared_relationship_points_at_something_the_pack_ships(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        $store = new WpDemoStore();
        $definitions = [];

        foreach (ContentModel::relationships() as $relationship) {
            $definitions[$relationship->key] = $relationship;
        }

        $titles = [];

        foreach (array_keys($demo->itemCounts) as $postType) {
            $titles[$postType] = array_map(
                static fn (array $row): string => (string) ($row['title'] ?? ''),
                $store->itemsFor($demo, $postType),
            );
        }

        $declared = 0;

        foreach (array_keys($demo->itemCounts) as $postType) {
            foreach ($store->itemsFor($demo, $postType) as $index => $row) {
                foreach (is_array($row['relationships'] ?? null) ? $row['relationships'] : [] as $key => $value) {
                    $where = sprintf('%s[%d].%s', $postType, $index, (string) $key);

                    self::assertArrayHasKey((string) $key, $definitions, $where . ' is not a relationship');

                    $definition = $definitions[(string) $key];

                    self::assertSame($postType, $definition->fromPostTypeKey, $where . ' runs the other way');

                    foreach (is_array($value) ? $value : [$value] as $title) {
                        $declared++;

                        self::assertContains(
                            $title,
                            $titles[$definition->toPostTypeKey] ?? [],
                            $where . ' names "' . (string) $title . '", which the pack does not ship',
                        );
                    }
                }
            }
        }

        self::assertGreaterThan(0, $declared, 'the pack wires nothing together');
    }

    /**
     * The country page needs a list before it can offer to filter one.
     */
    #[Test]
    public function at_least_one_country_has_several_institutions_attached_to_it(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        $store = new WpDemoStore();
        $perCountry = [];

        foreach ($store->itemsFor($demo, ContentModel::postTypeKey('institution')) as $row) {
            $country = is_array($row['relationships'] ?? null)
                ? (string) ($row['relationships']['institution_destination'] ?? '')
                : '';

            if ($country !== '') {
                $perCountry[$country] = ($perCountry[$country] ?? 0) + 1;
            }
        }

        self::assertNotSame([], $perCountry, 'no institution is attached to a country');
        self::assertGreaterThan(1, max($perCountry), 'no country lists more than one institution');
    }

    #[Test]
    public function an_unknown_post_type_yields_nothing_rather_than_failing(): void
    {
        $demo = DemoLibrary::find('boutique');
        self::assertNotNull($demo);

        self::assertSame([], (new WpDemoStore())->itemsFor($demo, 'edulume_not_a_type'));
    }
}
