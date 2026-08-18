<?php

/**
 * Home section: the first screen.
 *
 * Reserves its own height so image decoding costs no layout shift, and falls back to the site
 * title and tagline rather than to nothing. A home page whose first screen is blank because
 * nobody hooked a filter is not a minimal hero; it is a broken one.
 *
 * The background is the one image on the page that is neither lazy nor deferred: it is the
 * largest contentful paint, and telling the browser so is worth more than every other
 * optimisation in the theme put together.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Introduction', 'edulume');
$edulume_authored = apply_filters('edulume_home_section_content', '', 'hero');

$edulume_headline = edulume_opt('hero.headline', get_bloginfo('name'));
$edulume_lead = edulume_opt('hero.subheadline', get_bloginfo('description'));
$edulume_badges = edulume_opt_list('hero.badges');
$edulume_chips = edulume_opt_list('hero.chips');

$edulume_primary_label = edulume_opt('hero.primaryCta.label');
$edulume_primary_url = edulume_opt('hero.primaryCta.url');
$edulume_secondary_label = edulume_opt('hero.secondaryCta.label');
$edulume_secondary_url = edulume_opt('hero.secondaryCta.url');

?>
<section class="edulume-section edulume-home-hero" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-hero__backdrop" aria-hidden="true">
        <?php edulume_the_demo_image(edulume_opt('hero.image'), '', 1920, 1080, 'high'); ?>
    </div>

    <div class="edulume-container">
        <?php if (is_string($edulume_authored) && $edulume_authored !== '') : ?>
            <?php echo wp_kses_post($edulume_authored); ?>
        <?php else : ?>
            <div class="edulume-hero__inner">
                <h1 class="edulume-hero__title"><?php echo esc_html($edulume_headline); ?></h1>

                <?php if ($edulume_lead !== '') : ?>
                    <p class="edulume-hero__lead"><?php echo esc_html($edulume_lead); ?></p>
                <?php endif; ?>

                <form class="edulume-hero__search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <label class="screen-reader-text" for="edulume-hero-search">
                        <?php esc_html_e('Search the site', 'edulume'); ?>
                    </label>
                    <input
                        class="edulume-hero__search-input"
                        id="edulume-hero-search"
                        type="search"
                        name="s"
                        placeholder="<?php echo esc_attr(edulume_opt('hero.searchPlaceholder', __('Search', 'edulume'))); ?>"
                    />
                    <button class="edulume-button" type="submit"><?php esc_html_e('Search', 'edulume'); ?></button>
                </form>

                <div class="edulume-hero__actions">
                    <?php
                    $edulume_courses = get_post_type_archive_link('edulume_course');
                    $edulume_contact = get_page_by_path('contact');

                    // The owner's own call to action wins; the archive links are what a site
                    // with no pages yet can still offer.
                    $edulume_primary_url = $edulume_primary_url !== ''
                        ? $edulume_primary_url
                        : (is_string($edulume_courses) ? $edulume_courses : '');

                    $edulume_secondary_url = $edulume_secondary_url !== ''
                        ? $edulume_secondary_url
                        : ($edulume_contact instanceof WP_Post ? (string) get_permalink($edulume_contact) : '');
                    ?>

                    <?php if ($edulume_primary_url !== '') : ?>
                        <a class="edulume-button" href="<?php echo esc_url($edulume_primary_url); ?>">
                            <?php
                            echo esc_html($edulume_primary_label !== ''
                                ? $edulume_primary_label
                                : __('Browse courses', 'edulume'));
                            ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($edulume_secondary_url !== '') : ?>
                        <a
                            class="edulume-button edulume-button--quiet"
                            href="<?php echo esc_url($edulume_secondary_url); ?>"
                        >
                            <?php
                            echo esc_html($edulume_secondary_label !== ''
                                ? $edulume_secondary_label
                                : __('Talk to a counsellor', 'edulume'));
                            ?>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($edulume_badges !== []) : ?>
                    <ul class="edulume-hero__badges" role="list">
                        <?php foreach ($edulume_badges as $edulume_badge) : ?>
                            <li><?php echo esc_html(edulume_row($edulume_badge, 'label')); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($edulume_chips !== []) : ?>
                    <ul class="edulume-hero__chips" role="list">
                        <?php foreach ($edulume_chips as $edulume_chip) : ?>
                            <li class="edulume-hero__chip">
                                <span class="edulume-hero__chip-value">
                                    <?php echo esc_html(edulume_row($edulume_chip, 'value')); ?>
                                </span>
                                <span class="edulume-hero__chip-label">
                                    <?php echo esc_html(edulume_row($edulume_chip, 'label')); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
