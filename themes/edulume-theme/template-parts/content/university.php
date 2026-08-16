<?php

/**
 * One university, in a destination's grid.
 *
 * The logo sits in a fixed 96px box with `object-fit: contain` on a padded tile. Real
 * institutional logos are every shape there is — wide wordmarks, tall crests, square badges —
 * and a grid that lets them size themselves is a grid with one row three times the height of
 * the others.
 *
 * The filterable values are written into data attributes rather than parsed back out of the
 * rendered text, so filtering never depends on how a string happens to be formatted.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_post = $args['post'] ?? null;

if (!$edulume_post instanceof WP_Post) {
    return;
}

$edulume_name = get_the_title($edulume_post);
$edulume_city = (string) get_post_meta($edulume_post->ID, '_edulume_city', true);
$edulume_ranking = (string) get_post_meta($edulume_post->ID, '_edulume_ranking', true);
$edulume_apply = (string) get_post_meta($edulume_post->ID, '_edulume_apply_url', true);
$edulume_intakes = edulume_meta_list($edulume_post->ID, '_edulume_intakes');
$edulume_courses = array_slice(edulume_meta_list($edulume_post->ID, '_edulume_courses'), 0, 3);

$edulume_tuition_min = (string) get_post_meta($edulume_post->ID, '_edulume_tuition_min', true);
$edulume_tuition_max = (string) get_post_meta($edulume_post->ID, '_edulume_tuition_max', true);

?>
<li
    class="edulume-university"
    data-edulume-university
    data-name="<?php echo esc_attr(strtolower($edulume_name . ' ' . $edulume_city)); ?>"
    data-intakes="<?php echo esc_attr(strtolower(implode('|', $edulume_intakes))); ?>"
>
    <span class="edulume-university__logo">
        <?php if (has_post_thumbnail($edulume_post)) : ?>
            <?php
            echo get_the_post_thumbnail($edulume_post, 'thumbnail', [
                'loading' => 'lazy',
                'decoding' => 'async',
                'alt' => esc_attr($edulume_name),
            ]);
            ?>
        <?php else : ?>
            <span class="edulume-university__monogram" aria-hidden="true">
                <?php echo esc_html(mb_substr($edulume_name, 0, 1)); ?>
            </span>
        <?php endif; ?>
    </span>

    <h3 class="edulume-university__name">
        <a href="<?php echo esc_url((string) get_permalink($edulume_post)); ?>">
            <?php echo esc_html($edulume_name); ?>
        </a>
    </h3>

    <?php if ($edulume_city !== '') : ?>
        <p class="edulume-university__city"><?php echo esc_html($edulume_city); ?></p>
    <?php endif; ?>

    <?php if ($edulume_ranking !== '') : ?>
        <p class="edulume-university__ranking"><?php echo esc_html($edulume_ranking); ?></p>
    <?php endif; ?>

    <?php if ($edulume_tuition_min !== '' || $edulume_tuition_max !== '') : ?>
        <p class="edulume-university__tuition">
            <?php
            echo esc_html($edulume_tuition_max === ''
                ? $edulume_tuition_min
                : $edulume_tuition_min . '–' . $edulume_tuition_max);
            ?>
        </p>
    <?php endif; ?>

    <?php if ($edulume_intakes !== []) : ?>
        <ul class="edulume-university__intakes" role="list">
            <?php foreach ($edulume_intakes as $edulume_intake) : ?>
                <li><?php echo esc_html($edulume_intake); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($edulume_courses !== []) : ?>
        <ul class="edulume-university__courses" role="list">
            <?php foreach ($edulume_courses as $edulume_course) : ?>
                <li><?php echo esc_html($edulume_course); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($edulume_apply !== '') : ?>
        <a class="edulume-button edulume-button--quiet" href="<?php echo esc_url($edulume_apply); ?>">
            <?php
            printf(
                /* translators: %s: the university's name. */
                esc_html__('Apply to %s', 'edulume'),
                esc_html($edulume_name)
            );
            ?>
        </a>
    <?php endif; ?>
</li>
