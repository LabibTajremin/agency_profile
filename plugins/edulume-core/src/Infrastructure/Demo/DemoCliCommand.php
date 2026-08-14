<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Demo;

use Edulume\Core\Domain\Demo\DemoImportMode;
use Edulume\Core\Domain\Demo\DemoLibrary;
use Edulume\Core\Domain\Demo\ExistingContentChoice;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * `wp edulume demo …`
 *
 * A WP-CLI front end for the demo importer. It exists for two reasons that are both about
 * honesty rather than convenience: CI needs a real site with real content to measure against —
 * a performance budget asserted on an empty install measures nothing — and a support engineer
 * needs a way to reproduce a customer's site without clicking through a wizard.
 */
final class DemoCliCommand
{
    public function __construct(private readonly Container $container)
    {
    }

    public static function register(Container $container): void
    {
        if (!defined('WP_CLI') || !constant('WP_CLI')) {
            return;
        }

        \WP_CLI::add_command('edulume demo', new self($container));
    }

    /**
     * Imports a starter demo.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Which demo. One of gulf-premium, test-prep, guide-site, boutique.
     *
     * [--mode=<mode>]
     * : full, content-only or settings-only. Defaults to full.
     *
     * [--existing=<choice>]
     * : merge or fresh. Defaults to merge, which never deletes anything.
     *
     * @param list<string> $args
     * @param array<string, string> $options
     */
    public function import(array $args, array $options): void
    {
        $demo = DemoLibrary::find($args[0] ?? '');

        if ($demo === null) {
            \WP_CLI::error(sprintf(
                'Unknown demo "%s". Available: %s',
                $args[0] ?? '',
                implode(', ', DemoLibrary::slugs())
            ));

            return;
        }

        $mode = DemoImportMode::tryFrom($options['mode'] ?? 'full') ?? DemoImportMode::Full;
        $choice = ExistingContentChoice::tryFrom($options['existing'] ?? 'merge') ?? ExistingContentChoice::Merge;
        $importer = $this->container->importDemo();
        $cursor = null;
        $passes = 0;

        // Looped rather than run once. The importer stops when its budget runs out, and on CLI
        // that budget is generous but not infinite — driving it to completion here is what makes
        // "imported cleanly" a thing this command can actually report.
        do {
            $cursor = $importer->run($demo, $mode, $choice, $this->container->executionBudget(), $cursor);
            $passes++;
        } while (!$cursor->isComplete && $passes < 100);

        if (!$cursor->isComplete) {
            \WP_CLI::error(sprintf('Import did not finish after %d passes.', $passes));

            return;
        }

        \WP_CLI::success(sprintf(
            'Imported %s: %d item(s) in %d pass(es).',
            $demo->name,
            $cursor->createdCount(),
            $passes
        ));
    }

    /**
     * Lists the available demos.
     */
    public function list(): void
    {
        foreach (DemoLibrary::all() as $demo) {
            \WP_CLI::line(sprintf('%-16s %s (%d items)', $demo->slug, $demo->name, $demo->totalItems()));
        }
    }
}
