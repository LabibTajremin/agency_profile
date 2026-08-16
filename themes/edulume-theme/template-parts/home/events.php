<?php

/**
 * Home section: upcoming events.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

edulume_the_grid_section(
    'events',
    __('Upcoming events', 'edulume'),
    'edulume_event',
    3
);
