<?php

/**
 * Home section: recent writing.
 *
 * Real posts win over the bundled three, which is the case that matters: this is the one
 * section on the page backed by a post type every WordPress install already has.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('blog.title', __('From the blog', 'edulume'));
$edulume_posts = edulume_section_posts('post', 3);

if ($edulume_posts !== []) {
    edulume_the_grid_section('blog', $edulume_label, 'post', 3);

    return;
}

$edulume_items = edulume_opt_list('blog.items');

if ($edulume_items === []) {
    return;
}

if (!edulume_home_section_open('blog', $edulume_label)) {
    edulume_home_section_close();

    return;
}

edulume_the_section_header($edulume_label);
?>
<ul class="edulume-posts" role="list">
    <?php foreach ($edulume_items as $edulume_item) : ?>
        <?php $edulume_title = edulume_row($edulume_item, 'title'); ?>
        <li class="edulume-post-card">
            <span class="edulume-post-card__media">
                <?php edulume_the_demo_image(edulume_row($edulume_item, 'image'), $edulume_title, 640, 360); ?>
            </span>
            <p class="edulume-post-card__category"><?php echo esc_html(edulume_row($edulume_item, 'category')); ?></p>
            <h3 class="edulume-post-card__title"><?php echo esc_html($edulume_title); ?></h3>
            <p class="edulume-post-card__excerpt"><?php echo esc_html(edulume_row($edulume_item, 'excerpt')); ?></p>
            <p class="edulume-post-card__meta">
                <span><?php echo esc_html(edulume_row($edulume_item, 'date')); ?></span>
                <span><?php echo esc_html(edulume_row($edulume_item, 'readTime')); ?></span>
            </p>
        </li>
    <?php endforeach; ?>
</ul>
<?php

edulume_home_section_close();
