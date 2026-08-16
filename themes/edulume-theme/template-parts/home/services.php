<?php

/**
 * Home section: how we help.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

edulume_the_grid_section(
    'services',
    __('How we help', 'edulume'),
    'edulume_service',
    6
);
