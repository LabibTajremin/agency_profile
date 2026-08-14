<?php

/**
 * The Institution archive.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();

get_template_part('template-parts/content/archive', null, [
    'post_type' => 'edulume_institution',
    'finder' => true,
]);

get_footer();
