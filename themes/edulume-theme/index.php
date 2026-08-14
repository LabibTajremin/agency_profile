<?php

/**
 * The fallback template.
 *
 * Every more specific template in this theme delegates its list rendering here, so a post type
 * that gains an archive without gaining a template still looks like the rest of the site.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();

?>
<section class="edulume-section">
    <div class="edulume-container">
        <?php if (is_archive() || is_home()) : ?>
            <header class="edulume-archive__header">
                <h1 class="edulume-archive__title"><?php echo esc_html(edulume_archive_title()); ?></h1>
                <?php the_archive_description('<div class="edulume-archive__description">', '</div>'); ?>
            </header>
        <?php endif; ?>

        <?php if (have_posts()) : ?>
            <div class="edulume-grid">
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
                'screen_reader_text' => __('Posts navigation', 'edulume'),
            ]);
            ?>
        <?php else : ?>
            <p><?php esc_html_e('Nothing published here yet.', 'edulume'); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php

get_footer();
