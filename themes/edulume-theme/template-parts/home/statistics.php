<?php

/**
 * Home section: the numbers.
 *
 * Each figure is written into the markup at its final value and the counter script animates
 * from zero up to it. The other way round — an empty element the script fills — shows a row of
 * blanks to anyone with JavaScript off, and a row of zeroes to everyone else for the first
 * frame after paint.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('statistics.title', __('By the numbers', 'edulume'));
$edulume_items = edulume_opt_list('statistics.items');

if ($edulume_items === []) {
    return;
}

edulume_require_feature('counters');

if (!edulume_home_section_open('statistics', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);
?>
<ul class="edulume-stats" role="list">
    <?php foreach ($edulume_items as $edulume_item) : ?>
        <?php
        $edulume_value = (int) edulume_row($edulume_item, 'value', '0');
        $edulume_suffix = edulume_row($edulume_item, 'suffix');
        ?>
        <li class="edulume-stat">
            <p class="edulume-stat__value">
                <span data-edulume-counter="<?php echo esc_attr((string) $edulume_value); ?>">
                    <?php echo esc_html(number_format_i18n($edulume_value)); ?>
                </span><?php echo esc_html($edulume_suffix); ?>
            </p>
            <p class="edulume-stat__label"><?php echo esc_html(edulume_row($edulume_item, 'label')); ?></p>
        </li>
    <?php endforeach; ?>
</ul>
<?php

edulume_home_section_close();
