<?php

/**
 * The shared single body.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

?>
<article <?php post_class('edulume-single'); ?>>
    <header class="edulume-single__header edulume-container">
        <?php get_template_part('template-parts/content/breadcrumbs'); ?>
        <h1 class="edulume-single__title"><?php the_title(); ?></h1>
    </header>

    <?php if (has_post_thumbnail()) : ?>
        <figure class="edulume-single__media">
            <?php
            the_post_thumbnail('large', [
                'class' => 'edulume-single__image',
                'fetchpriority' => 'high',
            ]);
            ?>
        </figure>
    <?php endif; ?>

    <div class="edulume-single__body edulume-container">
        <?php get_template_part('template-parts/content/meta', get_post_type()); ?>
        <?php the_content(); ?>
    </div>

    <?php get_template_part('template-parts/content/related', get_post_type()); ?>
</article>
