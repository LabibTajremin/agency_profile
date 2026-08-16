<?php

/**
 * Home section: the eligibility check.
 *
 * The highest-converting element on the page, and it rendered nothing at all unless somebody
 * hooked a filter — so on every real install it was absent. It now shows the destinations a
 * visitor can be assessed against, which is content the site already has.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Check your eligibility', 'edulume');
$edulume_authored = apply_filters('edulume_home_section_content', '', 'eligibility');

if (is_string($edulume_authored) && $edulume_authored !== '') {
    echo '<section class="edulume-section edulume-home-section edulume-home-eligibility" aria-label="'
        . esc_attr($edulume_label) . '"><div class="edulume-container">'
        . wp_kses_post($edulume_authored) . '</div></section>';

    return;
}

$edulume_destinations = edulume_section_posts('edulume_destination', 6);

if ($edulume_destinations === []) {
    return;
}

?>
<section
    class="edulume-section edulume-home-section edulume-home-eligibility"
    aria-label="<?php echo esc_attr($edulume_label); ?>"
>
    <div class="edulume-container">
        <div class="edulume-eligibility">
            <h2 class="edulume-eligibility__title"><?php echo esc_html($edulume_label); ?></h2>
            <p class="edulume-eligibility__lead">
                <?php esc_html_e('Pick where you want to study. We will tell you what you need and what it costs.', 'edulume'); ?>
            </p>

            <ul class="edulume-eligibility__choices">
                <?php foreach ($edulume_destinations as $edulume_destination) : ?>
                    <li>
                        <a class="edulume-chip" href="<?php echo esc_url((string) get_permalink($edulume_destination)); ?>">
                            <?php echo esc_html(get_the_title($edulume_destination)); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
