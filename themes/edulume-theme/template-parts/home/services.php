<?php

/**
 * Home section: how we help.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('services.title', __('How we help', 'edulume'));

if (edulume_section_posts('edulume_service', 6) !== []) {
    edulume_the_grid_section('services', $edulume_label, 'edulume_service', 6);

    return;
}

$edulume_items = edulume_opt_list('services.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('services', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);

$edulume_intro = edulume_opt('services.intro');

if ($edulume_intro !== '') {
    printf('<p class="edulume-home-section__intro">%s</p>', esc_html($edulume_intro));
}
?>
<ul class="edulume-service-grid" role="list">
    <?php foreach ($edulume_items as $edulume_item) : ?>
        <?php
        $edulume_title = edulume_row($edulume_item, 'title');
        $edulume_url = edulume_row($edulume_item, 'url');
        ?>
        <li class="edulume-service-card" data-edulume-icon="<?php echo esc_attr(edulume_row($edulume_item, 'icon')); ?>">
            <h3 class="edulume-service-card__title">
                <?php if ($edulume_url !== '') : ?>
                    <a href="<?php echo esc_url($edulume_url); ?>"><?php echo esc_html($edulume_title); ?></a>
                <?php else : ?>
                    <?php echo esc_html($edulume_title); ?>
                <?php endif; ?>
            </h3>
            <p class="edulume-service-card__blurb"><?php echo esc_html(edulume_row($edulume_item, 'blurb')); ?></p>
        </li>
    <?php endforeach; ?>
</ul>
<?php

edulume_home_section_close();
