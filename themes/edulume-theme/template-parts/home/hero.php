<?php

/**
 * Home section: the first screen. Reserves its own height so the image decoding costs no layout shift.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Introduction', 'edulume');
$edulume_content = apply_filters('edulume_home_section_content', '', 'hero');

if ($edulume_content === '') {
    return;
}

?>
<section class="edulume-section edulume-home-hero" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <?php echo wp_kses_post($edulume_content); ?>
    </div>
</section>
