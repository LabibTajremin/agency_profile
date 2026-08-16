<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Demo\DemoImportCursor;
use Edulume\Core\Domain\Demo\DemoImportMode;
use Edulume\Core\Domain\Demo\DemoLibrary;
use Edulume\Core\Domain\Demo\ExistingContentChoice;
use Edulume\Core\Infrastructure\Content\BatchBudget;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * The demo import, one batch of ten per request.
 *
 * The screen's plain form still works and still drives the import to completion in a single
 * request — that is the no-JavaScript path and it has to stay. But "a single request" is
 * exactly what shared hosting kills at thirty seconds with a demo of a thousand items, and the
 * owner sees a white page and no way to tell whether anything was written.
 *
 * So the enhanced path posts here repeatedly. Each call imports ten items, saves the cursor and
 * returns the count, which gives the browser something honest to draw a progress bar from.
 *
 * The cursor is held in a transient rather than passed back and forth: it carries the id of
 * every item created so far, and round-tripping a thousand integers through the browser on
 * every batch is both slow and a thing a client could tamper with.
 */
final class DemoImportAjax
{
    public const ACTION = 'edulume_import_demo_batch';
    public const NONCE = 'edulume_demo_batch';
    public const BATCH_SIZE = 10;

    private const CURSOR_TRANSIENT = 'edulume_demo_cursor_';
    private const CURSOR_LIFETIME = 3600;

    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        add_action('wp_ajax_' . self::ACTION, [$this, 'handle']);
    }

    public function handle(): void
    {
        if (!current_user_can(Capabilities::IMPORT_DEMO_CONTENT)) {
            wp_send_json_error(['message' => __('You are not allowed to import content.', 'edulume')], 403);

            return;
        }

        check_ajax_referer(self::NONCE);

        $slug = isset($_POST['demo']) ? sanitize_key(wp_unslash((string) $_POST['demo'])) : '';
        $demo = DemoLibrary::find($slug);

        if ($demo === null) {
            wp_send_json_error(['message' => __('That starter pack does not exist.', 'edulume')], 404);

            return;
        }

        $restart = isset($_POST['restart']) && (string) wp_unslash((string) $_POST['restart']) === '1';
        $cursor = $restart ? null : $this->readCursor($slug);

        $cursor = $this->container->importDemo()->run(
            $demo,
            DemoImportMode::Full,
            ExistingContentChoice::Merge,
            new BatchBudget($this->container->executionBudget(), self::BATCH_SIZE),
            $cursor
        );

        $this->writeCursor($slug, $cursor);

        wp_send_json_success([
            'complete' => $cursor->isComplete,
            'created' => $cursor->createdCount(),
            'total' => $demo->totalItems(),
        ]);
    }

    private function readCursor(string $slug): ?DemoImportCursor
    {
        $stored = get_transient(self::CURSOR_TRANSIENT . $slug);

        if (!is_array($stored)) {
            return null;
        }

        $cursor = DemoImportCursor::fromArray($stored);

        // A finished cursor is a finished import. Resuming from one would report success
        // without writing anything, which is how "0 items created" gets mistaken for a bug.
        return $cursor->isComplete ? null : $cursor;
    }

    private function writeCursor(string $slug, DemoImportCursor $cursor): void
    {
        if ($cursor->isComplete) {
            delete_transient(self::CURSOR_TRANSIENT . $slug);

            return;
        }

        set_transient(self::CURSOR_TRANSIENT . $slug, $cursor->toArray(), self::CURSOR_LIFETIME);
    }
}
