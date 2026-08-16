<?php

/**
 * Home section: what students said.
 *
 * Rendered as real `<blockquote>`/`<cite>` rather than styled divs. A testimonial is a
 * quotation, and marking it as one is what lets a screen reader announce it as a quotation and
 * a search engine treat the attribution as an attribution.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('What students say', 'edulume');
$edulume_authored = apply_filters('edulume_home_section_content', '', 'testimonials');

if (is_string($edulume_authored) && $edulume_authored !== '') {
    echo '<section class="edulume-section edulume-home-section edulume-home-testimonials" aria-label="'
        . esc_attr($edulume_label) . '"><div class="edulume-container">'
        . wp_kses_post($edulume_authored) . '</div></section>';

    return;
}

$edulume_testimonials = edulume_section_posts('edulume_testimonial', 6);

if ($edulume_testimonials === []) {
    get_template_part('template-parts/home/testimonials-demo');

    return;
}

?>
<section
    class="edulume-section edulume-home-section edulume-home-testimonials"
    aria-label="<?php echo esc_attr($edulume_label); ?>"
>
    <div class="edulume-container">
        <?php edulume_the_section_header($edulume_label, 'edulume_testimonial'); ?>

        <div class="edulume-grid edulume-grid--quotes">
            <?php foreach ($edulume_testimonials as $edulume_testimonial) : ?>
                <figure class="edulume-quote">
                    <blockquote class="edulume-quote__body">
                        <?php echo esc_html(wp_strip_all_tags($edulume_testimonial->post_content)); ?>
                    </blockquote>
                    <figcaption class="edulume-quote__attribution">
                        <cite class="edulume-quote__name">
                            <?php echo esc_html(get_the_title($edulume_testimonial)); ?>
                        </cite>
                        <?php if (trim((string) $edulume_testimonial->post_excerpt) !== '') : ?>
                            <span class="edulume-quote__detail">
                                <?php echo esc_html($edulume_testimonial->post_excerpt); ?>
                            </span>
                        <?php endif; ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
