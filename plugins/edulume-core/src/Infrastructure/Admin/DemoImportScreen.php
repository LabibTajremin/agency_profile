<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Demo\DemoImportMode;
use Edulume\Core\Domain\Demo\DemoLibrary;
use Edulume\Core\Domain\Demo\ExistingContentChoice;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * The Starter content screen, and the button that actually imports.
 *
 * The admin menu has had a "Starter demos" page since the first release and there was no way to
 * import from it: the React route existed, no REST route sat behind it, and the only working
 * entry point was `wp edulume demo import` on the command line. On shared hosting — which is
 * where most of these sites live — a site owner may have no shell at all, so the feature was
 * effectively unreachable for exactly the people it was built for.
 *
 * Rendered server-side rather than through the admin application, and posted to `admin-post.php`
 * rather than to REST. Both choices are about this being the screen somebody reaches when the
 * site is new and possibly half-configured: it has to work before the bundle loads, and it has
 * to work if the bundle fails to load at all.
 */
final class DemoImportScreen
{
    public const ACTION = 'edulume_import_demo';
    public const REMOVE_ACTION = 'edulume_remove_demo';
    public const NOTICE_PARAMETER = 'edulume_demo_notice';

    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handleImport']);
        add_action('admin_post_' . self::REMOVE_ACTION, [$this, 'handleRemoval']);
    }

    public function handleImport(): void
    {
        $this->assertAllowed();

        $slug = isset($_POST['demo']) ? sanitize_key(wp_unslash((string) $_POST['demo'])) : '';
        $demo = DemoLibrary::find($slug);

        if ($demo === null) {
            $this->redirect(__('That starter pack does not exist.', 'edulume'));

            return;
        }

        $importer = $this->container->importDemo();
        $cursor = null;
        $passes = 0;

        /*
         * Driven to completion in a loop, exactly as the CLI command does. The importer stops
         * when its execution budget runs out — that is what keeps a large pack from hitting the
         * PHP time limit on cheap hosting — so a single call would import a fraction and report
         * itself finished.
         */
        do {
            $cursor = $importer->run(
                $demo,
                DemoImportMode::Full,
                ExistingContentChoice::Merge,
                $this->container->executionBudget(),
                $cursor
            );
            $passes++;
        } while (!$cursor->isComplete && $passes < 100);

        $this->redirect(sprintf(
            /* translators: 1: the demo's name, 2: how many items were created. */
            __('Imported %1$s — %2$d items created.', 'edulume'),
            $demo->name,
            $cursor->createdCount()
        ));
    }

    public function handleRemoval(): void
    {
        $this->assertAllowed();

        $store = $this->container->demoStore();
        $ids = $store->previouslyImportedIds();

        $store->deleteItems($ids);

        $this->redirect(sprintf(
            /* translators: %d: how many items were removed. */
            __('Removed %d imported item(s).', 'edulume'),
            count($ids)
        ));
    }

    /**
     * Renders the screen.
     */
    public function render(): void
    {
        if (!current_user_can(Capabilities::IMPORT_DEMO_CONTENT)) {
            return;
        }

        $this->renderNotice();

        $existing = count($this->container->demoStore()->previouslyImportedIds());

        echo '<div class="edulume-demos">';

        foreach (DemoLibrary::all() as $demo) {
            $total = $demo->totalItems();

            printf(
                '<div class="edulume-demo-card"><h3>%s</h3><p>%s</p><p class="edulume-demo-card__count">%s</p>',
                esc_html($demo->name),
                esc_html($demo->description),
                esc_html(sprintf(
                    /* translators: %d: how many items the pack contains. */
                    _n('%d item', '%d items', $total, 'edulume'),
                    $total
                ))
            );

            if ($total === 0) {
                printf(
                    '<p><em>%s</em></p>',
                    esc_html__('This pack has no content yet.', 'edulume')
                );
            } else {
                /*
                 * A plain form that works on its own, marked so the enhancement layer can take
                 * it over. Without script it posts and the server drives the import to the end;
                 * with script the same button runs it ten items at a time and draws a bar.
                 */
                printf(
                    '<form method="post" action="%1$s" data-edulume-import="%2$s">%3$s'
                    . '<input type="hidden" name="action" value="%4$s" />'
                    . '<input type="hidden" name="demo" value="%2$s" />'
                    . '<button type="submit" class="button button-primary">%5$s</button>'
                    . '<div class="edulume-progress" role="progressbar" aria-valuemin="0" '
                    . 'aria-valuemax="100" aria-valuenow="0">'
                    . '<span class="edulume-progress__bar" data-edulume-import-bar></span></div>'
                    . '<p class="edulume-progress__status" data-edulume-import-status '
                    . 'role="status" aria-live="polite"></p></form>',
                    esc_url(admin_url('admin-post.php')),
                    esc_attr($demo->slug),
                    wp_nonce_field(self::ACTION, '_wpnonce', true, false),
                    esc_attr(self::ACTION),
                    esc_html__('Import this pack', 'edulume')
                );
            }

            echo '</div>';
        }

        echo '</div>';

        if ($existing === 0) {
            return;
        }

        printf(
            '<hr /><p>%s</p><form method="post" action="%s">%s'
            . '<input type="hidden" name="action" value="%s" />'
            . '<button type="submit" class="button">%s</button></form>',
            esc_html(sprintf(
                /* translators: %d: how many imported items are on the site. */
                _n(
                    '%d item on this site came from a starter pack.',
                    '%d items on this site came from a starter pack.',
                    $existing,
                    'edulume'
                ),
                $existing
            )),
            esc_url(admin_url('admin-post.php')),
            wp_nonce_field(self::REMOVE_ACTION, '_wpnonce', true, false),
            esc_attr(self::REMOVE_ACTION),
            esc_html__('Remove imported content', 'edulume')
        );
    }

    private function renderNotice(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $notice = isset($_GET[self::NOTICE_PARAMETER])
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? sanitize_text_field(wp_unslash((string) $_GET[self::NOTICE_PARAMETER]))
            : '';

        if ($notice === '') {
            return;
        }

        printf('<div class="notice notice-success"><p>%s</p></div>', esc_html($notice));
    }

    /**
     * Capability and nonce, in that order, on every write.
     */
    private function assertAllowed(): void
    {
        if (!current_user_can(Capabilities::IMPORT_DEMO_CONTENT)) {
            wp_die(esc_html__('You are not allowed to import content.', 'edulume'), '', ['response' => 403]);
        }

        $action = isset($_POST['action']) ? sanitize_key(wp_unslash((string) $_POST['action'])) : '';

        check_admin_referer($action === self::REMOVE_ACTION ? self::REMOVE_ACTION : self::ACTION);
    }

    private function redirect(string $notice): void
    {
        wp_safe_redirect(add_query_arg(
            [
                'page' => 'edulume-demos',
                self::NOTICE_PARAMETER => rawurlencode($notice),
            ],
            admin_url('admin.php')
        ));

        exit;
    }
}
