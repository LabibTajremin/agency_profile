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

                <?php if ($edulume_contact instanceof WP_Post) : ?>
                    <a class="edulume-button" href="<?php echo esc_url((string) get_permalink($edulume_contact)); ?>">
                        <?php esc_html_e('Book a free consultation', 'edulume'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
