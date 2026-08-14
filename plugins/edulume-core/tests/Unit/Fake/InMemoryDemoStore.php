<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\DemoStore;
use Edulume\Core\Domain\Demo\DemoDefinition;

/**
 * A demo store that keeps everything in arrays.
 *
 * A fake rather than a mock: the interesting assertions are about what ended up stored after a
 * resumed import, and a mock that records calls cannot answer "is the site in the right state".
 */
final class InMemoryDemoStore implements DemoStore
{
    /** @var array<int, array{demo: string, postType: string, item: array<string, mixed>}> */
    public array $items = [];

    /** @var array<string, mixed> */
    public array $settings = [];

    /** @var list<string> */
    public array $assignedMenus = [];

    public string $homepage = '';

    /** @var list<int> */
    public array $deleted = [];

    /** @var list<int> */
    public array $preExistingDemoIds = [];

    public bool $hasAuthored = false;

    private int $nextId = 100;

    /**
     * How many items each post type should yield. Set per test rather than derived, so a test
     * can use a two-item demo without the library's real 180-course one.
     *
     * @var array<string, int>
     */
    public array $availableCounts = [];

    public function createItem(string $demoSlug, string $postType, array $item): int
    {
        $id = $this->nextId++;

        $this->items[$id] = ['demo' => $demoSlug, 'postType' => $postType, 'item' => $item];

        return $id;
    }

    public function itemsFor(DemoDefinition $demo, string $postType): array
    {
        $count = $this->availableCounts[$postType] ?? $demo->itemCountFor($postType);
        $items = [];

        for ($index = 0; $index < $count; $index++) {
            $items[] = ['title' => sprintf('%s %d', $postType, $index + 1)];
        }

        return $items;
    }

    public function applySettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function assignMenus(string $demoSlug, array $menuLocations): void
    {
        $this->assignedMenus = $menuLocations;
    }

    public function assignHomepage(string $demoSlug, string $homepageTitle): void
    {
        $this->homepage = $homepageTitle;
    }

    public function deleteItems(array $itemIds): void
    {
        foreach ($itemIds as $id) {
            $this->deleted[] = $id;
            unset($this->items[$id]);
        }
    }

    public function previouslyImportedIds(): array
    {
        return $this->preExistingDemoIds;
    }

    public function hasAuthoredContent(): bool
    {
        return $this->hasAuthored;
    }

    /**
     * @return list<string>
     */
    public function createdPostTypes(): array
    {
        return array_values(array_map(
            static fn (array $row): string => $row['postType'],
            $this->items,
        ));
    }
}
