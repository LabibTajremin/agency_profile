<?php

/**
 * Home section: how an application runs.
 *
 * An ordered list, because the order is the content. A row of divs styled to look numbered
 * reads as six unrelated cards to anything that is not a sighted browser.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('process.title', __('How it works', 'edulume'));
$edulume_items = edulume_opt_list('process.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('process', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);

$edulume_intro = edulume_opt('process.intro');

if ($edulume_intro !== '') {
    printf('<p class="edulume-home-section__intro">%s</p>', esc_html($edulume_intro));
}
?>
<ol class="edulume-steps">
    <?php foreach ($edulume_items as $edulume_index => $edulume_item) : ?>
        <li class="edulume-step">
            <span class="edulume-step__number" aria-hidden="true">
                <?php echo esc_html(number_format_i18n($edulume_index + 1)); ?>
            </span>
            <h3 class="edulume-step__title"><?php echo esc_html(edulume_row($edulume_item, 'title')); ?></h3>
            <p class="edulume-step__blurb"><?php echo esc_html(edulume_row($edulume_item, 'blurb')); ?></p>
        </li>
    <?php endforeach; ?>
</ol>
<?php

edulume_home_section_close();
