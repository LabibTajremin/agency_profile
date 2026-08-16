<?php

/**
 * Home section: the first screen.
 *
 * Reserves its own height so image decoding costs no layout shift, and falls back to the site
 * title and tagline rather than to nothing. A home page whose first screen is blank because
 * nobody hooked a filter is not a minimal hero; it is a broken one.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Introduction', 'edulume');
$edulume_authored = apply_filters('edulume_home_section_content', '', 'hero');

?>
<section class="edulume-section edulume-home-hero" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <?php if (is_string($edulume_authored) && $edulume_authored !== '') : ?>
            <?php echo wp_kses_post($edulume_authored); ?>
        <?php else : ?>
            <div class="edulume-hero__inner">
                <h1 class="edulume-hero__title"><?php echo esc_html(get_bloginfo('name')); ?></h1>

                <?php if (get_bloginfo('description') !== '') : ?>
                    <p class="edulume-hero__lead"><?php echo esc_html(get_bloginfo('description')); ?></p>
                <?php endif; ?>

                <?php
                $edulume_courses = get_post_type_archive_link('edulume_course');
                $edulume_contact = get_page_by_path('contact');
                ?>

                <div class="edulume-hero__actions">
                    <?php if (is_string($edulume_courses) && $edulume_courses !== '') : ?>
                        <a class="edulume-button" href="<?php echo esc_url($edulume_courses); ?>">
                            <?php esc_html_e('Browse courses', 'edulume'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($edulume_contact instanceof WP_Post) : ?>
                        <?php $edulume_contact_url = (string) get_permalink($edulume_contact); ?>
                        <a
                            class="edulume-button edulume-button--quiet"
                            href="<?php echo esc_url($edulume_contact_url); ?>"
                        >
                            <?php esc_html_e('Talk to a counsellor', 'edulume'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
