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

/*
 * The same two candidates the plugin itself tries, and for the same reason: the repository
 * layout puts `vendor/` at the root, while wp-env mounts it inside the plugin directory. A
 * single hard-coded relative path is right in exactly one of those.
 */
$autoloaders = [
    dirname(__DIR__, 2) . '/vendor/autoload.php',
    dirname(__DIR__, 4) . '/vendor/autoload.php',
];

$loaded = false;

foreach ($autoloaders as $autoloader) {
    if (is_readable($autoloader)) {
        require_once $autoloader;
        $loaded = true;

        break;
    }
}

if (!$loaded) {
    fwrite(STDERR, "Could not find Composer's autoloader. Run `composer install`.\n");

    exit(1);
}

/*
 * WordPress's core test bootstrap requires Yoast's PHPUnit Polyfills and refuses to start
 * without them. Pointing the constant at the installed package is the documented way to say
 * where they are; loading the autoloader alone is not enough, because core looks for the path.
 */
if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    $polyfills = dirname($autoloader, 2) . '/yoast/phpunit-polyfills';

    if (is_dir($polyfills)) {
        define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills);
    }
}

require_once $testsDirectory . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
    require dirname(__DIR__, 2) . '/edulume-core.php';
});

require $testsDirectory . '/includes/bootstrap.php';
