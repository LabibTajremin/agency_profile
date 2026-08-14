<?php

/**
 * Search results.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();

?>
<section class="edulume-section edulume-search">
    <div class="edulume-container">
        <h1 class="edulume-search__title">
            <?php
            printf(
                /* translators: %s: the search term. */
                esc_html__('Results for %s', 'edulume'),
                '<span>' . esc_html(get_search_query()) . '</span>'
            );
            ?>
        </h1>

        <?php get_search_form(); ?>

        <?php if (have_posts()) : ?>
            <ol class="edulume-search__results">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('template-parts/content/search-result');
                endwhile;
                ?>
            </ol>

            <?php
            the_posts_pagination([
                'mid_size' => 2,
                'screen_reader_text' => __('Results navigation', 'edulume'),
            ]);
            ?>
        <?php else : ?>
            <p class="edulume-search__empty">
                <?php esc_html_e('Nothing matched. Try fewer words, or a country or course name.', 'edulume'); ?>
            </p>
        <?php endif; ?>
    </div>
</section>
<?php

get_footer();
