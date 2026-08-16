<?php

/**
 * Home section: the closing call to action.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Talk to a counsellor', 'edulume');
$edulume_authored = apply_filters('edulume_home_section_content', '', 'cta');
$edulume_contact = get_page_by_path('contact');

?>
<section class="edulume-section edulume-home-cta" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <?php if (is_string($edulume_authored) && $edulume_authored !== '') : ?>
            <?php echo wp_kses_post($edulume_authored); ?>
        <?php else : ?>
            <div class="edulume-cta">
                <h2 class="edulume-cta__title"><?php echo esc_html($edulume_label); ?></h2>
                <p class="edulume-cta__lead">
                    <?php esc_html_e('Tell us where you want to study and we will tell you, honestly, what is realistic.', 'edulume'); ?>
                </p>

                <?php
                /*
                 * The form itself, not a link to a page with a form on it. Every navigation
                 * between somebody deciding to ask and being able to type is a place they stop.
                 */
                get_template_part('template-parts/content/enquiry-form', null, [
                    'form_id' => 'enquiry',
                    'title' => __('Send us your question', 'edulume'),
                ]);
                ?>

                <?php if ($edulume_contact instanceof WP_Post) : ?>
                    <p class="edulume-cta__alternative">
                        <a href="<?php echo esc_url((string) get_permalink($edulume_contact)); ?>">
                            <?php esc_html_e('Or book a consultation', 'edulume'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
