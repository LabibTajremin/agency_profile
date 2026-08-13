<?php

/**
 * Boots WordPress for the integration suite.
 *
 * The unit suite deliberately runs with no WordPress at all; this one needs a real install,
 * a real database and real option storage, because what it proves is precisely that the
 * infrastructure layer talks to them correctly.
 *
 * @package Edulume\Core
 */

declare(strict_types=1);

$testsDirectory = getenv('WP_TESTS_DIR') ?: '/wordpress-phpunit';

if (!is_readable($testsDirectory . '/includes/functions.php')) {
    fwrite(
        STDERR,
        "The WordPress test library was not found at {$testsDirectory}.\n"
        . "Start it with `npx wp-env start` and run the integration suite through wp-env,\n"
        . "or point WP_TESTS_DIR at an existing checkout.\n"
    );

    exit(1);
}

require_once dirname(__DIR__, 3) . '/../vendor/autoload.php';
require_once $testsDirectory . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
    require dirname(__DIR__, 2) . '/edulume-core.php';
});

require $testsDirectory . '/includes/bootstrap.php';
