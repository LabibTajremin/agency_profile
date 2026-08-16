<?php

/**
 * Home section: featured courses.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

edulume_the_grid_section(
    'courses',
    __('Featured courses', 'edulume'),
    'edulume_course',
    6
);
