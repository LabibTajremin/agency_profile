<?php

/**
 * Centred footer: one widget area, centred, above the baseline.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_columns = 1;

?>
<div class="edulume-footer__inner edulume-footer__inner--centred">
    <?php if (edulume_chrome()->hasNewsletter) : ?>
        <section class="edulume-footer__newsletter" aria-label="<?php esc_attr_e('Newsletter', 'edulume'); ?>">
            <h2 class="edulume-footer__heading"><?php esc_html_e('Stay in touch', 'edulume'); ?></h2>
            <?php do_action('edulume_render_form', 'newsletter'); ?>
        </section>
    <?php endif; ?>

    <?php for ($edulume_column = 1; $edulume_column <= $edulume_columns; $edulume_column++) : ?>
        <?php if (is_active_sidebar('edulume-footer-' . $edulume_column)) : ?>
            <div class="edulume-footer__column">
                <?php dynamic_sidebar('edulume-footer-' . $edulume_column); ?>
            </div>
        <?php endif; ?>
    <?php endfor; ?>
</div>
