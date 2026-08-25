<?php

/**
 * Home section: partner institutions.
 *
 * Real institutions the site owner has entered come first; the bundled tiles are what a new
 * install shows until they have. The crests are generated monograms, never a real university
 * mark — shipping those in a distributed theme is somebody else's trademark on your disk.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('universities.title', __('Partner universities', 'edulume'));
$edulume_posts = edulume_section_posts('edulume_institution', 12);

if ($edulume_posts !== []) {
    edulume_the_grid_section('universities', $edulume_label, 'edulume_institution', 12);

    return;
}

$edulume_items = edulume_opt_list('universities.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('universities', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);

$edulume_intro = edulume_opt('universities.intro');

if ($edulume_intro !== '') {
    printf('<p class="edulume-home-section__intro">%s</p>', esc_html($edulume_intro));
}
?>
<ul class="edulume-crest-grid" role="list">
    <?php foreach ($edulume_items as $edulume_item) : ?>
        <li class="edulume-crest-card">
            <span class="edulume-crest-card__tile">
                <?php
                edulume_the_demo_image(
                    edulume_row($edulume_item, 'crest'),
                    edulume_row($edulume_item, 'name'),
                    96,
                    96
                );
                ?>
            </span>
            <p class="edulume-crest-card__name"><?php echo esc_html(edulume_row($edulume_item, 'name')); ?></p>
            <p class="edulume-crest-card__meta">
                <?php echo esc_html(edulume_row($edulume_item, 'country')); ?>
                <span class="edulume-crest-card__rank"><?php echo esc_html(edulume_row($edulume_item, 'ranking')); ?></span>
            </p>
        </li>
    <?php endforeach; ?>
</ul>
<?php

edulume_home_section_close();
