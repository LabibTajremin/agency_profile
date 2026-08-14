<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Domain\Demo\DemoDefinition;

/**
 * Where demo content is written, and how it is taken back out.
 *
 * Every created item carries the demo's slug as a marker. That marker is the whole basis of a
 * clean rollback: without it, "remove the demo" has to guess, and guessing wrong deletes
 * something the site owner wrote.
 */
interface DemoStore
{
    /**
     * Creates one demo item and returns its id.
     *
     * @param array<string, mixed> $item
     */
    public function createItem(string $demoSlug, string $postType, array $item): int;

    /**
     * The item payloads for one post type, in the order they should be created.
     *
     * @return list<array<string, mixed>>
     */
    public function itemsFor(DemoDefinition $demo, string $postType): array;

    /**
     * @param array<string, mixed> $settings
     */
    public function applySettings(array $settings): void;

    /**
     * @param list<string> $menuLocations
     */
    public function assignMenus(string $demoSlug, array $menuLocations): void;

    public function assignHomepage(string $demoSlug, string $homepageTitle): void;

    /**
     * @param list<int> $itemIds
     */
    public function deleteItems(array $itemIds): void;

    /**
     * Every item previously imported from a demo, by its marker.
     *
     * @return list<int>
     */
    public function previouslyImportedIds(): array;

    public function hasAuthoredContent(): bool;
}
