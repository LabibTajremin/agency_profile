<?php

/**
 * The modular home page.
 *
 * Sections are reorderable and individually re-themeable: each one renders inside a scope the
 * token compiler emits a block for, so re-theming a section is a stored override rather than a
 * copy of this template.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();

$edulume_sections = apply_filters('edulume_home_sections', [
    'hero',
    'trust-bar',
    'services',
    'destinations',
    'courses',
    'eligibility',
    'success-stories',
    'events',
    'testimonials',
    'faq',
    'cta',
]);

foreach ($edulume_sections as $edulume_section) :
    ?>
    <div class="edulume-section-scope" data-edulume-section="<?php echo esc_attr((string) $edulume_section); ?>">
        <?php get_template_part('template-parts/home/' . $edulume_section); ?>
    </div>
    <?php
endforeach;

get_footer();
