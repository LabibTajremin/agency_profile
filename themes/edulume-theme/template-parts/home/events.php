<?php

/**
 * Home section: upcoming events.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('events.title', __('Upcoming events', 'edulume'));

if (edulume_section_posts('edulume_event', 3) !== []) {
    edulume_the_grid_section('events', $edulume_label, 'edulume_event', 3);

    return;
}

$edulume_items = edulume_opt_list('events.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('events', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);
?>
<ul class="edulume-events" role="list">
    <?php foreach ($edulume_items as $edulume_item) : ?>
        <?php
        $edulume_title = edulume_row($edulume_item, 'title');
        $edulume_cta = is_array($edulume_item['cta'] ?? null) ? $edulume_item['cta'] : [];
        $edulume_cta_label = edulume_row($edulume_cta, 'label');
        $edulume_cta_url = edulume_row($edulume_cta, 'url');
        ?>
        <li class="edulume-event-card">
            <span class="edulume-event-card__media">
                <?php edulume_the_demo_image(edulume_row($edulume_item, 'image'), $edulume_title, 600, 400); ?>
            </span>
            <p class="edulume-event-card__date">
                <span class="edulume-event-card__day"><?php echo esc_html(edulume_row($edulume_item, 'day')); ?></span>
                <span class="edulume-event-card__month"><?php echo esc_html(edulume_row($edulume_item, 'month')); ?></span>
            </p>
            <h3 class="edulume-event-card__title"><?php echo esc_html($edulume_title); ?></h3>
            <p class="edulume-event-card__venue"><?php echo esc_html(edulume_row($edulume_item, 'venue')); ?></p>

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
