<?php

/**
 * Home section: funding.
 *
 * Real scholarship posts win; the bundled four are what a new install shows.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('scholarships.title', __('Scholarships', 'edulume'));
$edulume_posts = edulume_section_posts('edulume_scholarship', 6);

if ($edulume_posts !== []) {
    edulume_the_grid_section('scholarships', $edulume_label, 'edulume_scholarship', 6);

    return;
}

$edulume_items = edulume_opt_list('scholarships.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('scholarships', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);

$edulume_intro = edulume_opt('scholarships.intro');

if ($edulume_intro !== '') {
    printf('<p class="edulume-home-section__intro">%s</p>', esc_html($edulume_intro));
}
?>
<ul class="edulume-funding" role="list">
    <?php foreach ($edulume_items as $edulume_item) : ?>
        <li class="edulume-funding-card">
            <h3 class="edulume-funding-card__name"><?php echo esc_html(edulume_row($edulume_item, 'name')); ?></h3>
            <p class="edulume-funding-card__amount"><?php echo esc_html(edulume_row($edulume_item, 'amount')); ?></p>
            <p class="edulume-funding-card__eligibility">
                <?php echo esc_html(edulume_row($edulume_item, 'eligibility')); ?>
            </p>
            <p class="edulume-funding-card__meta">
                <span><?php echo esc_html(edulume_row($edulume_item, 'country')); ?></span>
                <span><?php echo esc_html(edulume_row($edulume_item, 'deadline')); ?></span>
            </p>
        </li>
    <?php endforeach; ?>
</ul>
<?php

edulume_home_section_close();
