<?php

/**
 * A single Institution.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    get_template_part('template-parts/content/single');
endwhile;

get_footer();
