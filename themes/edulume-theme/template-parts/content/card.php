<?php

/**
 * The default card.
 *
 * Every archive uses a card, and every card carries explicit image dimensions, because a card
 * grid without them is the single largest source of layout shift on a page like this.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/*
 * The reveal hook is only emitted when motion is actually on. Emitting it unconditionally
 * would put a hidden-until-observed element on pages where nothing is ever going to observe it.
 */
$edulume_motion = edulume_motion_is_active();

if ($edulume_motion) {
    edulume_require_feature('motion');
}
?>
<article
    <?php post_class('edulume-card'); ?>
    <?php echo $edulume_motion ? 'data-edulume-motion="fade-in-up"' : ''; ?>
>
    <?php if (has_post_thumbnail()) : ?>
        <a class="edulume-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
            <?php
            the_post_thumbnail('medium_large', [
                'loading' => 'lazy',
                'decoding' => 'async',
                'class' => 'edulume-card__image',
            ]);
            ?>
        </a>
    <?php endif; ?>

    <div class="edulume-card__body">
        <h2 class="edulume-card__title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>
        <p class="edulume-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>

        <?php edulume_the_card_actions(get_the_ID(), get_post_type()); ?>
    </div>
</article>
