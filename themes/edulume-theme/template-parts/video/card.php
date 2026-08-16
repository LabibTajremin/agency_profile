<?php

/**
 * One video, as a facade.
 *
 * No iframe and no `<video>` element is written here. What renders is a poster, a duration and
 * a real `<button>`; the player is injected when the card is activated, either by the script
 * seeing it reach the middle of the viewport or by somebody pressing the button.
 *
 * That is not an optimisation, it is the difference between a page that loads and one that does
 * not: ten Facebook iframes on a front page is several megabytes of somebody else's JavaScript,
 * ten third-party connections before first paint, and a Core Web Vitals score no amount of
 * caching recovers.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_card = $args['card'] ?? [];
$edulume_consent = (bool) ($args['consent'] ?? false);

if (!is_array($edulume_card) || !is_string($edulume_card['embed'] ?? null) || $edulume_card['embed'] === '') {
    return;
}

$edulume_title = is_string($edulume_card['title'] ?? null) ? $edulume_card['title'] : '';
$edulume_poster = is_string($edulume_card['poster'] ?? null) ? $edulume_card['poster'] : '';
$edulume_duration = is_string($edulume_card['duration'] ?? null) ? $edulume_card['duration'] : '';
$edulume_source = is_string($edulume_card['source'] ?? null) ? $edulume_card['source'] : 'mp4';
$edulume_third_party = (bool) ($edulume_card['thirdParty'] ?? false);

$edulume_label = $edulume_title !== ''
    ? sprintf(
        /* translators: %s: the video's title. */
        __('Play “%s”', 'edulume'),
        $edulume_title
    )
    : __('Play video', 'edulume');

?>
<li
    class="edulume-video-card"
    data-edulume-video-card
    data-edulume-video-source="<?php echo esc_attr($edulume_source); ?>"
    data-edulume-video-embed="<?php echo esc_url($edulume_card['embed']); ?>"
    data-edulume-video-title="<?php echo esc_attr($edulume_title); ?>"
    <?php echo $edulume_third_party && $edulume_consent ? 'data-edulume-video-consent="required"' : ''; ?>
>
    <div class="edulume-video-card__frame" data-edulume-video-frame>
        <?php if ($edulume_poster !== '') : ?>
            <img
                class="edulume-video-card__poster"
                src="<?php echo esc_url($edulume_poster); ?>"
                alt=""
                loading="lazy"
                decoding="async"
            />
        <?php else : ?>
            <?php
            /*
             * No poster. A generated panel rather than a grey rectangle: Facebook does not hand
             * out thumbnails without an API token this product is not going to ask for, and the
             * owner is separately told, in the admin, which items are missing one.
             */
            ?>
            <span class="edulume-video-card__placeholder" aria-hidden="true">
                <span class="edulume-video-card__placeholder-title"><?php echo esc_html($edulume_title); ?></span>
            </span>
        <?php endif; ?>

        <button type="button" class="edulume-video-card__play" data-edulume-video-play>
            <span class="edulume-video-card__glyph" aria-hidden="true"></span>
            <span class="screen-reader-text"><?php echo esc_html($edulume_label); ?></span>
        </button>

        <?php if ($edulume_duration !== '') : ?>
            <span class="edulume-video-card__duration"><?php echo esc_html($edulume_duration); ?></span>
        <?php endif; ?>

        <?php if ($edulume_third_party && $edulume_consent) : ?>
            <div class="edulume-video-card__consent" data-edulume-video-consent-panel>
                <p>
                    <?php
                    printf(
                        /* translators: %s: the name of the video host, e.g. Facebook. */
                        esc_html__('Load this video from %s? It will set third-party cookies.', 'edulume'),
                        esc_html(ucfirst($edulume_source))
                    );
                    ?>
                </p>
                <button type="button" class="edulume-button" data-edulume-video-consent-accept>
                    <?php esc_html_e('Load video', 'edulume'); ?>
                </button>
                <label class="edulume-video-card__consent-all">
                    <input type="checkbox" data-edulume-video-consent-all />
                    <?php esc_html_e('Load videos like this from now on', 'edulume'); ?>
                </label>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($edulume_title !== '') : ?>
        <p class="edulume-video-card__title"><?php echo esc_html($edulume_title); ?></p>
    <?php endif; ?>
</li>
