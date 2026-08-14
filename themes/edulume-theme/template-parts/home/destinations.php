<?php

/**
 * Home section: study destinations.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Destinations', 'edulume');
$edulume_content = apply_filters('edulume_home_section_content', '', 'destinations');

if ($edulume_content === '') {
    return;
}

?>
<section class="edulume-section edulume-home-destinations" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <?php echo wp_kses_post($edulume_content); ?>
    </div>
</section>
