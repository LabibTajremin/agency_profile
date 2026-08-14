<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Demo;

use Edulume\Core\Application\Port\DemoStore;
use Edulume\Core\Application\Port\ExecutionBudget;
use Edulume\Core\Domain\Demo\DemoDefinition;
use Edulume\Core\Domain\Demo\DemoImportCursor;
use Edulume\Core\Domain\Demo\DemoImportMode;
use Edulume\Core\Domain\Demo\ExistingContentChoice;

/**
 * Imports a starter demo, one budgeted chunk at a time.
 *
 * The loop stops as soon as the execution budget runs out and hands back a cursor. Nothing here
 * knows about wall-clock time — the budget is injected, which is what makes "it stops in time"
 * a thing a test can assert in milliseconds instead of a thing you hope about in production.
 */
final class ImportDemo
{
    public function __construct(private readonly DemoStore $store)
    {
    }

    public function run(
        DemoDefinition $demo,
        DemoImportMode $mode,
        ExistingContentChoice $choice,
        ExecutionBudget $budget,
        ?DemoImportCursor $resumeFrom = null,
    ): DemoImportCursor {
        $cursor = $resumeFrom ?? $this->begin($demo, $choice);

        if ($cursor->isComplete) {
            return $cursor;
        }

        if ($mode->importsSettings() && !$cursor->settingsApplied) {
            $this->store->applySettings($demo->settings);
            $cursor = $cursor->withSettingsApplied();
            $budget->consume();
        }

        if (!$mode->importsContent()) {
            return $cursor->completed();
        }

        return $this->importContent($demo, $budget, $cursor);
    }

    /**
     * The first request: honour the merge-or-fresh choice before creating anything.
     */
    private function begin(DemoDefinition $demo, ExistingContentChoice $choice): DemoImportCursor
    {
        if ($choice->removesPreviousDemo()) {
            // Only previously imported items — they carry the marker. Authored content has
            // none and is never in scope, which is what makes "Replace" safe to click.
            $this->store->deleteItems($this->store->previouslyImportedIds());
        }

        return DemoImportCursor::start($demo->slug);
    }

    private function importContent(
        DemoDefinition $demo,
        ExecutionBudget $budget,
        DemoImportCursor $cursor,
    ): DemoImportCursor {
        $postTypes = $demo->postTypes();

        while ($budget->hasTimeRemaining()) {
            $postType = $postTypes[$cursor->postTypeIndex] ?? null;

            if ($postType === null) {
                return $this->finish($demo, $cursor);
            }

            $items = $this->store->itemsFor($demo, $postType);

            if (!array_key_exists($cursor->itemIndex, $items)) {
                $cursor = $cursor->withNextPostType();

                continue;
            }

            $id = $this->store->createItem($demo->slug, $postType, $items[$cursor->itemIndex]);
            $cursor = $cursor->withItem($id);
            $budget->consume();
        }

        return $cursor;
    }

    /**
     * The last step, and deliberately the last: menus and the homepage assignment point at
     * content, so doing them earlier would point them at pages that do not exist yet.
     */
    private function finish(DemoDefinition $demo, DemoImportCursor $cursor): DemoImportCursor
    {
        $this->store->assignMenus($demo->slug, $demo->menus);
        $this->store->assignHomepage($demo->slug, $demo->homepageTitle);

        return $cursor->completed();
    }

    /**
     * Whether the site owner has to be asked about existing content before this can start.
     */
    public function needsExistingContentChoice(): bool
    {
        return $this->store->hasAuthoredContent();
    }

    /**
     * Undoes an import completely, using the ids the cursor recorded.
     *
     * Driven by the cursor rather than by a fresh query, so a rollback removes exactly what this
     * import created and nothing that happened to look similar.
     */
    public function rollback(DemoImportCursor $cursor): int
    {
        $this->store->deleteItems($cursor->createdIds);

        return $cursor->createdCount();
    }
}
