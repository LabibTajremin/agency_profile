<?php

/**
 * Removes everything Edulume stored, but only when the site owner asked for it.
 *
 * Uninstall is the one destructive step, so it is also the one gated on an explicit choice:
 * a site owner who deletes a plugin to try a different version must not lose a configuration
 * they spent an afternoon on. Nothing is removed unless the "delete data on uninstall"
 * setting was switched on first.
 *
 * @package Edulume\Core
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

const EDULUME_DELETE_DATA_OPTION = 'edulume_delete_data_on_uninstall';

const EDULUME_OWNED_OPTIONS = [
    'edulume_theme_settings',
    'edulume_section_overrides',
    'edulume_stylesheet_url',
    'edulume_stylesheet_version',
    'edulume_version',
    'edulume_activated_at',
    EDULUME_DELETE_DATA_OPTION,
];

if (!get_option(EDULUME_DELETE_DATA_OPTION, false)) {
    return;
}

foreach (EDULUME_OWNED_OPTIONS as $option) {
    delete_option($option);
}

$uploads = wp_get_upload_dir();
$directory = rtrim((string) $uploads['basedir'], '/') . '/edulume';

foreach (glob($directory . '/edulume-*.css') ?: [] as $stylesheet) {
    wp_delete_file($stylesheet);
}
