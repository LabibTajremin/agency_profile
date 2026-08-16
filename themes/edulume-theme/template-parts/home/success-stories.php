<?php

/**
 * Home section: success stories.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

edulume_the_grid_section(
    'success-stories',
    __('Success stories', 'edulume'),
    'edulume_story',
    3
);
