<?php

/**
 * The 404 template.
 *
 * A dead end is a lead about to leave, so this offers the three things that recover one: search,
 * the finders, and a way to talk to someone.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

get_header();

?>
<section class="edulume-section edulume-404">
    <div class="edulume-container">
        <h1 class="edulume-404__title"><?php esc_html_e('We could not find that page', 'edulume'); ?></h1>
        <p class="edulume-404__lead">
            <?php esc_html_e('It may have moved. Try a search, or pick up from one of these.', 'edulume'); ?>
        </p>

        <?php get_search_form(); ?>

        <nav class="edulume-404__links" aria-label="<?php esc_attr_e('Useful links', 'edulume'); ?>">
            <ul>
                <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'edulume'); ?></a></li>
                <li>
                    <a href="<?php echo esc_url(get_post_type_archive_link('edulume_course') ?: home_url('/')); ?>">
                        <?php esc_html_e('Find a course', 'edulume'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(get_post_type_archive_link('edulume_destination') ?: home_url('/')); ?>">
                        <?php esc_html_e('Browse destinations', 'edulume'); ?>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</section>
<?php

get_footer();
