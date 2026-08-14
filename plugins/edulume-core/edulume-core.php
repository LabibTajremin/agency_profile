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
 * Two autoloader locations, in the order they are likely to exist.
 *
 * A released plugin carries its own `vendor/`; a checkout of this repository shares the root
 * one. Hard-coding the repository layout is what made the plugin fatal the moment it ran
 * anywhere other than this working copy — and a fatal in a plugin file takes the whole site
 * down, wp-admin included, so this checks rather than assumes.
 */
$edulumeAutoloaders = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php',
];

$edulumeLoaded = false;

foreach ($edulumeAutoloaders as $edulumeAutoloader) {
    if (is_readable($edulumeAutoloader)) {
        require_once $edulumeAutoloader;
        $edulumeLoaded = true;

        break;
    }
}

if (!$edulumeLoaded) {
    add_action('admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__(
                'Edulume Core is missing its dependencies. Install the release ZIP rather than a '
                . 'source checkout, or run composer install in the plugin directory.',
                'edulume'
            )
        );
    });

    return;
}

Plugin::boot(VERSION, __FILE__);
