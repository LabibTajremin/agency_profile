<?php

/**
 * The modular home page.
 *
 * Sections are reorderable, individually re-themeable, and individually switchable: each one
 * renders inside a scope the token compiler emits a block for, so re-theming a section is a
 * stored override rather than a copy of this template.
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

/*
 * Switched-off sections are removed here, after the order filter and before rendering.
 *
 * After, so a child theme that reorders or adds sections still gets its list respected — the
 * visibility filter passes through any slug it does not recognise. Before rendering, because
 * hiding a section with CSS would still run its queries, still emit its markup, and still put
 * its headings in the accessibility tree for a screen reader to read out.
 *
 * With the plugin inactive this is an unfiltered array and every section renders, which is the
 * same graceful degradation the rest of the theme relies on.
 */
$edulume_sections = apply_filters('edulume_enabled_home_sections', $edulume_sections);

foreach ($edulume_sections as $edulume_section) :
    ?>
    <div class="edulume-section-scope" data-edulume-section="<?php echo esc_attr((string) $edulume_section); ?>">
        <?php get_template_part('template-parts/home/' . $edulume_section); ?>
    </div>
    <?php
endforeach;

get_footer();
