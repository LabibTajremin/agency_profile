<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Demo;

use Edulume\Core\Application\Demo\ImportDemo;
use Edulume\Core\Domain\Demo\DemoDefinition;
use Edulume\Core\Domain\Demo\DemoImportCursor;
use Edulume\Core\Domain\Demo\DemoImportMode;
use Edulume\Core\Domain\Demo\DemoLibrary;
use Edulume\Core\Domain\Demo\ExistingContentChoice;
use Edulume\Core\Domain\Demo\MediaLicence;
use Edulume\Core\Tests\Unit\Fake\CountingExecutionBudget;
use Edulume\Core\Tests\Unit\Fake\InMemoryDemoStore;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DemoImportTest extends TestCase
{
    /** A small demo, so a test asserts the mechanism rather than waiting for 180 courses. */
    private function smallDemo(): DemoDefinition
    {
        return new DemoDefinition(
            'tiny',
            'Tiny',
            'Two post types, three items.',
            ['edulume_course' => 2, 'edulume_service' => 1],
            ['accent' => 'ocean'],
            ['primary'],
            'Home',
        );
    }

    #[Test]
    public function the_library_ships_the_four_demos_the_brief_asks_for(): void
    {
        self::assertSame(['gulf-premium', 'test-prep', 'guide-site', 'boutique'], DemoLibrary::slugs());
        self::assertSame('Boutique practice', DemoLibrary::find('boutique')?->name);
        self::assertNull(DemoLibrary::find('nope'));
    }

    #[Test]
    public function every_shipped_demo_brings_content_settings_menus_and_a_homepage(): void
    {
        foreach (DemoLibrary::all() as $demo) {
            self::assertGreaterThan(20, $demo->totalItems(), $demo->slug);
            self::assertNotSame([], $demo->settings, $demo->slug);
            self::assertContains('primary', $demo->menus, $demo->slug);
            self::assertNotSame('', $demo->homepageTitle, $demo->slug);
            self::assertTrue($demo->mediaIsRedistributable(), $demo->slug);
            self::assertGreaterThan(20, strlen($demo->description), $demo->slug);
        }
    }

    #[Test]
    public function a_demos_post_types_are_listed_in_a_stable_order(): void
    {
        $demo = DemoLibrary::find('boutique');

        self::assertNotNull($demo);
        self::assertSame($demo->postTypes(), $demo->postTypes());
        self::assertSame(30, $demo->itemCountFor('edulume_course'));
        self::assertSame(0, $demo->itemCountFor('edulume_partner'));
    }

    #[Test]
    public function media_that_cannot_be_redistributed_is_marked_as_placeholders(): void
    {
        self::assertFalse(MediaLicence::PlaceholderOnly->isRedistributable());
        self::assertTrue(MediaLicence::Cc0->isRedistributable());
        self::assertTrue(MediaLicence::RedistributableLicence->isRedistributable());

        foreach (MediaLicence::cases() as $licence) {
            self::assertNotSame('', $licence->label());
        }
    }

    #[Test]
    public function a_full_import_with_enough_budget_finishes_in_one_pass(): void
    {
        $store = new InMemoryDemoStore();
        $cursor = (new ImportDemo($store))->run(
            $this->smallDemo(),
            DemoImportMode::Full,
            ExistingContentChoice::Merge,
            new CountingExecutionBudget(50),
        );

        self::assertTrue($cursor->isComplete);
        self::assertSame(3, $cursor->createdCount());
        self::assertSame(['accent' => 'ocean'], $store->settings);
        self::assertSame(['primary'], $store->assignedMenus);
        self::assertSame('Home', $store->homepage);
    }

    #[Test]
    public function an_import_that_runs_out_of_budget_stops_and_resumes_exactly_where_it_stopped(): void
    {
        $store = new InMemoryDemoStore();
        $importer = new ImportDemo($store);
        $demo = $this->smallDemo();

        // Two units: one for the settings step, one for a single item.
        $first = $importer->run(
            $demo,
            DemoImportMode::Full,
            ExistingContentChoice::Merge,
            new CountingExecutionBudget(2),
        );

        self::assertFalse($first->isComplete);
        self::assertSame(1, $first->createdCount());

        $second = $importer->run(
            $demo,
            DemoImportMode::Full,
            ExistingContentChoice::Merge,
            new CountingExecutionBudget(50),
            $first,
        );

        self::assertTrue($second->isComplete);
        self::assertSame(3, $second->createdCount());
        self::assertSame(
            ['edulume_course', 'edulume_course', 'edulume_service'],
            $store->createdPostTypes(),
        );
    }

    #[Test]
    public function resuming_does_not_reapply_the_settings(): void
    {
        $store = new InMemoryDemoStore();
        $importer = new ImportDemo($store);
        $demo = $this->smallDemo();

        $first = $importer->run($demo, DemoImportMode::Full, ExistingContentChoice::Merge, new CountingExecutionBudget(1));

        self::assertTrue($first->settingsApplied);

        $store->settings = ['accent' => 'changed by the site owner'];

        $importer->run($demo, DemoImportMode::Full, ExistingContentChoice::Merge, new CountingExecutionBudget(50), $first);

        self::assertSame(['accent' => 'changed by the site owner'], $store->settings);
    }

    #[Test]
    public function a_completed_cursor_is_returned_untouched(): void
    {
        $store = new InMemoryDemoStore();
        $done = DemoImportCursor::start('tiny')->completed();

        $result = (new ImportDemo($store))->run(
            $this->smallDemo(),
            DemoImportMode::Full,
            ExistingContentChoice::Merge,
            new CountingExecutionBudget(50),
            $done,
        );

        self::assertSame($done, $result);
        self::assertSame([], $store->items);
    }

    #[Test]
    public function a_settings_only_import_creates_no_content(): void
    {
        $store = new InMemoryDemoStore();
        $cursor = (new ImportDemo($store))->run(
            $this->smallDemo(),
            DemoImportMode::SettingsOnly,
            ExistingContentChoice::Merge,
            new CountingExecutionBudget(50),
        );

        self::assertTrue($cursor->isComplete);
        self::assertSame(0, $cursor->createdCount());
        self::assertSame(['accent' => 'ocean'], $store->settings);
        self::assertSame('', $store->homepage);
    }

    #[Test]
    public function a_content_only_import_leaves_the_settings_alone(): void
    {
        $store = new InMemoryDemoStore();
        $store->settings = ['accent' => 'the site owner picked this'];

        $cursor = (new ImportDemo($store))->run(
            $this->smallDemo(),
            DemoImportMode::ContentOnly,
            ExistingContentChoice::Merge,
            new CountingExecutionBudget(50),
        );

        self::assertTrue($cursor->isComplete);
        self::assertSame(3, $cursor->createdCount());
        self::assertSame(['accent' => 'the site owner picked this'], $store->settings);
    }

    #[Test]
    public function merging_never_deletes_anything(): void
    {
        $store = new InMemoryDemoStore();
        $store->preExistingDemoIds = [7, 8];

        (new ImportDemo($store))->run(
            $this->smallDemo(),
            DemoImportMode::Full,
            ExistingContentChoice::Merge,
            new CountingExecutionBudget(50),
        );

        self::assertSame([], $store->deleted);
    }

    #[Test]
    public function a_fresh_import_removes_only_the_previous_demo(): void
    {
        $store = new InMemoryDemoStore();
        $store->preExistingDemoIds = [7, 8];

        (new ImportDemo($store))->run(
            $this->smallDemo(),
            DemoImportMode::Full,
            ExistingContentChoice::Fresh,
            new CountingExecutionBudget(50),
        );

        self::assertSame([7, 8], $store->deleted);
        self::assertTrue(ExistingContentChoice::Fresh->removesPreviousDemo());
        self::assertFalse(ExistingContentChoice::Merge->removesPreviousDemo());
    }

    #[Test]
    public function the_site_owner_is_asked_before_importing_over_authored_content(): void
    {
        $store = new InMemoryDemoStore();
        $importer = new ImportDemo($store);

        self::assertFalse($importer->needsExistingContentChoice());

        $store->hasAuthored = true;

        self::assertTrue($importer->needsExistingContentChoice());
    }

    #[Test]
    public function a_rollback_removes_exactly_what_the_import_created(): void
    {
        $store = new InMemoryDemoStore();
        $importer = new ImportDemo($store);
        $cursor = $importer->run(
            $this->smallDemo(),
            DemoImportMode::Full,
            ExistingContentChoice::Merge,
            new CountingExecutionBudget(50),
        );

        $removed = $importer->rollback($cursor);

        self::assertSame(3, $removed);
        self::assertSame($cursor->createdIds, $store->deleted);
        self::assertSame([], $store->items);
    }

    #[Test]
    public function the_cursor_round_trips_through_storage(): void
    {
        $cursor = DemoImportCursor::of('gulf-premium', 2, 5, [11, 12], true, false);

        self::assertEquals($cursor, DemoImportCursor::fromArray($cursor->toArray()));
        self::assertEquals($cursor, DemoImportCursor::fromArray([
            'demoSlug' => 'gulf-premium',
            'postTypeIndex' => '2',
            'itemIndex' => '5',
            'createdIds' => ['11', 12, 'nope', 0],
            'settingsApplied' => true,
            'isComplete' => false,
        ]));
    }

    #[Test]
    public function an_unreadable_stored_cursor_starts_over_rather_than_fataling(): void
    {
        $cursor = DemoImportCursor::fromArray(['demoSlug' => 5, 'postTypeIndex' => -9, 'createdIds' => 'nope']);

        self::assertSame('', $cursor->demoSlug);
        self::assertSame(0, $cursor->postTypeIndex);
        self::assertSame([], $cursor->createdIds);
        self::assertFalse($cursor->isComplete);
    }

    #[Test]
    public function each_import_mode_says_what_it_brings(): void
    {
        foreach (DemoImportMode::cases() as $mode) {
            self::assertNotSame('', $mode->label());
        }

        foreach (ExistingContentChoice::cases() as $choice) {
            self::assertNotSame('', $choice->label());
        }

        self::assertTrue(DemoImportMode::Full->importsContent());
        self::assertTrue(DemoImportMode::Full->importsSettings());
        self::assertFalse(DemoImportMode::SettingsOnly->importsContent());
        self::assertFalse(DemoImportMode::ContentOnly->importsSettings());
    }
}
