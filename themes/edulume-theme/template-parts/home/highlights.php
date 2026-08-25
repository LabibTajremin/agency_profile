<?php

/**
 * Home section: why this consultancy.
 *
 * Four claims, one supporting image and one number. The number is the section's whole argument,
 * so it is marked up as text rather than baked into the artwork — a figure inside an SVG cannot
 * be read out, translated, or corrected by the owner.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('highlights.title', __('Why us', 'edulume'));
$edulume_items = edulume_opt_list('highlights.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('highlights', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);

$edulume_callout_value = edulume_opt('highlights.callout.value');
$edulume_callout_label = edulume_opt('highlights.callout.label');
?>
<div class="edulume-highlights">
    <ul class="edulume-highlights__list" role="list">
        <?php foreach ($edulume_items as $edulume_item) : ?>
            <li class="edulume-pillar">
                <h3 class="edulume-pillar__title"><?php echo esc_html(edulume_row($edulume_item, 'title')); ?></h3>
                <p class="edulume-pillar__blurb"><?php echo esc_html(edulume_row($edulume_item, 'blurb')); ?></p>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="edulume-highlights__aside">
        <?php
        edulume_the_demo_image(
            edulume_opt('highlights.image'),
            edulume_opt('highlights.title'),
            720,
            540
        );
        ?>

        <?php if ($edulume_callout_value !== '') : ?>
            <p class="edulume-highlights__callout">
                <span class="edulume-highlights__callout-value"><?php echo esc_html($edulume_callout_value); ?></span>
                <span class="edulume-highlights__callout-label"><?php echo esc_html($edulume_callout_label); ?></span>
            </p>
        <?php endif; ?>
    </div>
</div>
<?php

edulume_home_section_close();
