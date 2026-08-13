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

require_once __DIR__ . '/../../vendor/autoload.php';

Plugin::boot(VERSION, __FILE__);
