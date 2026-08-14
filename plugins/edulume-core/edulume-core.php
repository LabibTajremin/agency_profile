<?php

/**
 * Plugin Name:       Edulume Core
 * Plugin URI:        https://edulume.com/
 * Description:       Owns every setting, content type, lead and REST route Edulume needs. Survives a theme switch.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Edulume
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       edulume
 * Domain Path:       /languages
 *
 * @package Edulume\Core
 */

declare(strict_types=1);

namespace Edulume\Core;

use Edulume\Core\Infrastructure\Wp\Plugin;

defined('ABSPATH') || exit;

const VERSION = '0.1.0';

/*
 * The plugin registers its own PSR-4 autoloader rather than relying on Composer's.
 *
 * Composer's generated autoloader resolves `Edulume\Core\` relative to wherever `vendor/` sits.
 * That is fine in this repository and wrong everywhere else: wp-env mounts the root `vendor/`
 * inside the plugin directory, so the generated mapping looked for
 * `edulume-core/plugins/edulume-core/src` and the plugin fatalled on activation with a
 * class-not-found — taking wp-admin down with it.
 *
 * Mapping the one namespace against `__DIR__` cannot drift, whatever the deployment does with
 * `vendor/`. The Composer autoloader is still included when present, for third-party packages.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Edulume\\Core\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/src/' . $relative . '.php';

    if (is_readable($file)) {
        require_once $file;
    }
});

foreach ([__DIR__ . '/vendor/autoload.php', dirname(__DIR__, 2) . '/vendor/autoload.php'] as $edulumeAutoloader) {
    if (is_readable($edulumeAutoloader)) {
        require_once $edulumeAutoloader;

        break;
    }
}

Plugin::boot(VERSION, __FILE__);
