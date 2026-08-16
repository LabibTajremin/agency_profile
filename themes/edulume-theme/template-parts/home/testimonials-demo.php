<?php

/**
 * The testimonials section before anybody has entered a testimonial.
 *
 * A separate part rather than a second branch in `testimonials.php`: that file's job is to
 * render posts, and interleaving two data shapes through one loop is how the quotation markup
 * ends up wrong for one of them.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('testimonials.title', __('What students say', 'edulume'));
$edulume_items = edulume_opt_list('testimonials.items');

if ($edulume_items === []) {
    return;
}

?>
<section
    class="edulume-section edulume-home-section edulume-home-testimonials"
    aria-label="<?php echo esc_attr($edulume_label); ?>"
>
    <div class="edulume-container">
        <?php edulume_the_section_header($edulume_label); ?>

        <div class="edulume-grid edulume-grid--quotes">
            <?php foreach ($edulume_items as $edulume_item) : ?>
                <?php $edulume_name = edulume_row($edulume_item, 'name'); ?>
                <figure class="edulume-quote">
                    <blockquote class="edulume-quote__body">
                        <?php echo esc_html(edulume_row($edulume_item, 'quote')); ?>
                    </blockquote>
                    <figcaption class="edulume-quote__attribution">
                        <span class="edulume-quote__portrait">
                            <?php edulume_the_demo_image(edulume_row($edulume_item, 'avatar'), $edulume_name, 64, 64); ?>
                        </span>
                        <cite class="edulume-quote__name"><?php echo esc_html($edulume_name); ?></cite>
                        <span class="edulume-quote__detail">
                            <?php
                            printf(
                                /* translators: 1: a university name, 2: a country. */
                                esc_html__('%1$s, %2$s', 'edulume'),
                                esc_html(edulume_row($edulume_item, 'university')),
                                esc_html(edulume_row($edulume_item, 'country'))
                            );
                            ?>
                        </span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
