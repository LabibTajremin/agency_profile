<?php

/**
 * Edulume Child theme bootstrap.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('edulume-child', get_stylesheet_uri(), ['edulume-base'], wp_get_theme()->get('Version'));
}, 20);
