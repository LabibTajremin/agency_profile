<?php

/**
 * A horizontal row of video facades.
 *
 * Used twice: the front page's promo rail, and the single intake video on a destination page.
 * One component rather than two, parameterised by `single`, because the second copy is where
 * the iframe budget and the teardown logic stop being maintained.
 *
 * The rail scrolls natively. Arrows are an addition on wide screens, never the only way through
 * — `overflow: hidden` with JavaScript arrows is a carousel nobody can use with a trackpad, a
 * touchpad gesture, or a keyboard.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_rail = $args['rail'] ?? [];

if (!is_array($edulume_rail) || ($edulume_rail['enabled'] ?? false) !== true) {
    return;
}

$edulume_items = is_array($edulume_rail['items'] ?? null) ? $edulume_rail['items'] : [];

if ($edulume_items === []) {
    return;
}

edulume_require_feature('video-rail');

$edulume_single = (bool) ($args['single'] ?? false);
$edulume_consent = (bool) ($edulume_rail['consent'] ?? false);
$edulume_autoplay = (bool) ($edulume_rail['autoplay'] ?? true);
$edulume_aspect = is_string($edulume_rail['aspect'] ?? null) ? $edulume_rail['aspect'] : '9 / 16';
$edulume_title = is_string($edulume_rail['title'] ?? null) ? $edulume_rail['title'] : '';
$edulume_subtitle = is_string($edulume_rail['subtitle'] ?? null) ? $edulume_rail['subtitle'] : '';

$edulume_label = $edulume_title !== '' ? $edulume_title : __('Videos', 'edulume');

?>
<div
    class="edulume-video-rail<?php echo $edulume_single ? ' edulume-video-rail--single' : ''; ?>"
    data-edulume-video-rail
    data-edulume-video-autoplay="<?php echo $edulume_autoplay ? 'true' : 'false'; ?>"
    data-edulume-video-single="<?php echo $edulume_single ? 'true' : 'false'; ?>"
    data-edulume-video-consent="<?php echo $edulume_consent ? 'true' : 'false'; ?>"
    style="--edulume-video-aspect: <?php echo esc_attr($edulume_aspect); ?>;"
>
    <?php if ($edulume_title !== '' && !$edulume_single) : ?>
        <header class="edulume-video-rail__header">
            <h2 class="edulume-video-rail__title"><?php echo esc_html($edulume_title); ?></h2>
            <?php if ($edulume_subtitle !== '') : ?>
                <p class="edulume-video-rail__subtitle"><?php echo esc_html($edulume_subtitle); ?></p>
            <?php endif; ?>
        </header>
    <?php endif; ?>

    <div class="edulume-video-rail__viewport">
        <?php if (!$edulume_single) : ?>
            <?php
            /*
             * The arrows are hidden from assistive technology on purpose. The track itself is a
             * focusable region with arrow-key scrolling, so announcing two more controls that do
             * the same thing is noise, not access.
             */
            ?>
            <button
                type="button"
                class="edulume-video-rail__arrow edulume-video-rail__arrow--previous"
                data-edulume-video-previous
                aria-hidden="true"
                tabindex="-1"
            ></button>
        <?php endif; ?>

        <ul
            class="edulume-video-rail__track"
            data-edulume-video-track
            role="region"
            aria-label="<?php echo esc_attr($edulume_label); ?>"
            tabindex="0"
        >
            <?php foreach ($edulume_items as $edulume_item) : ?>
                <?php
                get_template_part('template-parts/video/card', null, [
                    'card' => $edulume_item,
                    'consent' => $edulume_consent,
                ]);
                ?>
            <?php endforeach; ?>
        </ul>

        <?php if (!$edulume_single) : ?>
            <button
                type="button"
                class="edulume-video-rail__arrow edulume-video-rail__arrow--next"
                data-edulume-video-next
                aria-hidden="true"
                tabindex="-1"
            ></button>
        <?php endif; ?>
    </div>
</div>
