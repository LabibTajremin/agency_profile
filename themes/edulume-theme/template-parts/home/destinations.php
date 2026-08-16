<?php

/**
 * Home section: study destinations.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

edulume_the_grid_section(
    'destinations',
    __('Study destinations', 'edulume'),
    'edulume_destination',
    8
);
