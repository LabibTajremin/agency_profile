<?php

/**
 * One search result.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

?>
<li class="edulume-search__result">
    <a class="edulume-search__result-link" href="<?php the_permalink(); ?>">
        <span class="edulume-search__result-kind">
            <?php echo esc_html(get_post_type_object(get_post_type())->labels->singular_name ?? ''); ?>
        </span>
        <h2 class="edulume-search__result-title"><?php the_title(); ?></h2>
    </a>
    <p class="edulume-search__result-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 30)); ?></p>
</li>
