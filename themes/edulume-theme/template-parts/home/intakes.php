<?php

/**
 * Home section: intakes still open.
 *
 * The deadline is the useful number, not the intake month, so it is the line with emphasis on
 * it. Somebody reading this in October needs to know what they have already missed.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('intakes.title', __('Open intakes', 'edulume'));
$edulume_items = edulume_opt_list('intakes.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('intakes', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);

$edulume_intro = edulume_opt('intakes.intro');

if ($edulume_intro !== '') {
    printf('<p class="edulume-home-section__intro">%s</p>', esc_html($edulume_intro));
}
?>
<ul class="edulume-intakes" role="list">
    <?php foreach ($edulume_items as $edulume_item) : ?>
        <?php
        $edulume_cta = is_array($edulume_item['cta'] ?? null) ? $edulume_item['cta'] : [];
        $edulume_cta_label = edulume_row($edulume_cta, 'label');
        $edulume_cta_url = edulume_row($edulume_cta, 'url');
        ?>
        <li class="edulume-intake">
            <p class="edulume-intake__when">
                <span class="edulume-intake__month"><?php echo esc_html(edulume_row($edulume_item, 'month')); ?></span>
                <span class="edulume-intake__year"><?php echo esc_html(edulume_row($edulume_item, 'year')); ?></span>
            </p>
            <p class="edulume-intake__countries"><?php echo esc_html(edulume_row($edulume_item, 'countries')); ?></p>
            <p class="edulume-intake__deadline"><?php echo esc_html(edulume_row($edulume_item, 'deadline')); ?></p>

            <?php if ($edulume_cta_label !== '' && $edulume_cta_url !== '') : ?>
                <a class="edulume-button edulume-button--quiet" href="<?php echo esc_url($edulume_cta_url); ?>">
                    <?php echo esc_html($edulume_cta_label); ?>
                </a>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
<?php

edulume_home_section_close();
