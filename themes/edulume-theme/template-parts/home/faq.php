<?php

/**
 * Home section: frequently asked questions.
 *
 * Built from `<details>`/`<summary>`, which gives keyboard operation, screen-reader
 * announcement of expanded state, and find-in-page that opens the matching answer — all of it
 * without a line of JavaScript. A scripted accordion has to reimplement each of those, and
 * usually reimplements the first two and forgets the third.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Frequently asked questions', 'edulume');
$edulume_authored = apply_filters('edulume_home_section_content', '', 'faq');

if (is_string($edulume_authored) && $edulume_authored !== '') {
    echo '<section class="edulume-section edulume-home-section edulume-home-faq" aria-label="'
        . esc_attr($edulume_label) . '"><div class="edulume-container">'
        . wp_kses_post($edulume_authored) . '</div></section>';

    return;
}

$edulume_faqs = edulume_section_posts('edulume_faq', 8);

if ($edulume_faqs === []) {
    get_template_part('template-parts/home/faq-demo');

    return;
}

?>
<section
    class="edulume-section edulume-home-section edulume-home-faq"
    aria-label="<?php echo esc_attr($edulume_label); ?>"
>
    <div class="edulume-container">
        <?php edulume_the_section_header($edulume_label, 'edulume_faq'); ?>

        <div class="edulume-faq-list">
            <?php foreach ($edulume_faqs as $edulume_index => $edulume_faq) : ?>
                <details class="edulume-faq-item"<?php echo $edulume_index === 0 ? ' open' : ''; ?>>
                    <summary class="edulume-faq-item__question">
                        <?php echo esc_html(get_the_title($edulume_faq)); ?>
                    </summary>
                    <div class="edulume-faq-item__answer">
                        <?php
                        $edulume_answer = trim((string) $edulume_faq->post_content) !== ''
                            ? $edulume_faq->post_content
                            : (string) $edulume_faq->post_excerpt;

                        echo wp_kses_post(wpautop($edulume_answer));
                        ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
