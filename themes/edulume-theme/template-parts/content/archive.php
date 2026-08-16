<?php

/**
 * The shared archive body.
 *
 * Every content type's archive delegates here. The differences between them — the facets that
 * make sense, the meta a card shows — are data passed in, not a fork of this file, so a change
 * to pagination or grid behaviour lands once for all sixteen types.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_type = $args['post_type'] ?? get_post_type();
$edulume_has_finder = (bool) ($args['finder'] ?? false);

if ($edulume_has_finder) {
    edulume_require_feature('finder');
}

?>
<section class="edulume-section edulume-archive edulume-archive--<?php echo esc_attr((string) $edulume_type); ?>">
    <div class="edulume-container">
        <header class="edulume-archive__header">
            <h1 class="edulume-archive__title"><?php echo esc_html(edulume_archive_title()); ?></h1>
            <?php the_archive_description('<div class="edulume-archive__description">', '</div>'); ?>
        </header>

        <?php if ($edulume_has_finder) : ?>
            <?php get_template_part('template-parts/content/finder', null, ['post_type' => $edulume_type]); ?>
        <?php endif; ?>

        <?php
        /*
         * The compare tray. Hidden until two things are selected, because a control that says
         * "compare 1 item" is a control that has nothing to do.
         */
        edulume_require_feature('compare');
        $edulume_compare_url = (string) home_url('/compare/');
        ?>
        <div class="edulume-compare-bar">
            <p class="edulume-compare-bar__count">
                <span data-edulume-compare-count>0</span>
                <?php esc_html_e('selected to compare', 'edulume'); ?>
            </p>
            <a
                class="edulume-button edulume-compare-bar__link"
                href="<?php echo esc_url($edulume_compare_url); ?>"
                data-edulume-compare-link="<?php echo esc_url($edulume_compare_url); ?>"
                hidden
            >
                <?php esc_html_e('Compare selected', 'edulume'); ?>
            </a>
        </div>

        <?php if (have_posts()) : ?>
            <div class="edulume-grid" id="edulume-results">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('template-parts/content/card', get_post_type());
                endwhile;
                ?>
            </div>

            <?php
            the_posts_pagination([
                'mid_size' => 2,
                'screen_reader_text' => __('Results navigation', 'edulume'),
            ]);
            ?>
        <?php else : ?>
            <p class="edulume-archive__empty">
                <?php esc_html_e('Nothing here yet. Adjust your filters or check back soon.', 'edulume'); ?>
            </p>
        <?php endif; ?>
    </div>
</section>
