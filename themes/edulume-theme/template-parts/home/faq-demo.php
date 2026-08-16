<?php

/**
 * The FAQ section before anybody has entered an FAQ.
 *
 * Same `<details>`/`<summary>` markup as the post-backed version, for the same reason: keyboard
 * operation, expanded-state announcement and find-in-page, none of which need a script.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = edulume_opt('faq.title', __('Frequently asked questions', 'edulume'));
$edulume_items = edulume_opt_list('faq.items');

if ($edulume_items === []) {
    return;
}

?>
<section
    class="edulume-section edulume-home-section edulume-home-faq"
    aria-label="<?php echo esc_attr($edulume_label); ?>"
>
    <div class="edulume-container">
        <?php edulume_the_section_header($edulume_label); ?>

        <div class="edulume-faq-list">
            <?php foreach ($edulume_items as $edulume_index => $edulume_item) : ?>
                <details class="edulume-faq-item"<?php echo $edulume_index === 0 ? ' open' : ''; ?>>
                    <summary class="edulume-faq-item__question">
                        <?php echo esc_html(edulume_row($edulume_item, 'question')); ?>
                    </summary>
                    <div class="edulume-faq-item__answer">
                        <p><?php echo esc_html(edulume_row($edulume_item, 'answer')); ?></p>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
