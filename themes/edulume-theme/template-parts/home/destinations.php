<?php

/**
 * Home section: study destinations.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('destinations.title', __('Study destinations', 'edulume'));

if (edulume_section_posts('edulume_destination', 8) !== []) {
    edulume_the_grid_section('destinations', $edulume_label, 'edulume_destination', 8);

    return;
}

$edulume_items = edulume_opt_list('destinations.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('destinations', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);

$edulume_intro = edulume_opt('destinations.intro');

if ($edulume_intro !== '') {
    printf('<p class="edulume-home-section__intro">%s</p>', esc_html($edulume_intro));
}
?>
<ul class="edulume-destination-grid" role="list">
    <?php foreach ($edulume_items as $edulume_item) : ?>
        <?php
        $edulume_name = edulume_row($edulume_item, 'name');
        $edulume_count = (int) edulume_row($edulume_item, 'universities', '0');
        ?>
        <li class="edulume-destination-card">
            <span class="edulume-destination-card__media">
                <?php edulume_the_demo_image(edulume_row($edulume_item, 'image'), $edulume_name, 640, 480); ?>
            </span>
            <h3 class="edulume-destination-card__name"><?php echo esc_html($edulume_name); ?></h3>
            <p class="edulume-destination-card__hook"><?php echo esc_html(edulume_row($edulume_item, 'hook')); ?></p>
            <p class="edulume-destination-card__meta">
                <span>
                    <?php
                    printf(
                        /* translators: %s: how many partner universities the country has. */
                        esc_html(_n('%s university', '%s universities', $edulume_count, 'edulume')),
                        esc_html(number_format_i18n($edulume_count))
                    );
                    ?>
                </span>
                <span><?php echo esc_html(edulume_row($edulume_item, 'tuition')); ?></span>
            </p>
        </li>
    <?php endforeach; ?>
</ul>
<?php

edulume_home_section_close();
