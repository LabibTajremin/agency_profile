<?php

/**
 * The fallback for any singular view without a more specific template.
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
