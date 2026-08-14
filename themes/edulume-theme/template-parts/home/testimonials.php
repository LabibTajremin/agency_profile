<?php

/**
 * Home section: what students said.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Testimonials', 'edulume');
$edulume_content = apply_filters('edulume_home_section_content', '', 'testimonials');

if ($edulume_content === '') {
    return;
}

?>
<section class="edulume-section edulume-home-testimonials" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <?php echo wp_kses_post($edulume_content); ?>
    </div>
</section>
