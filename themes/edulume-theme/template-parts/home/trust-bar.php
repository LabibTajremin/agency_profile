<?php

/**
 * Home section: partner and accreditation logos.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Accreditations', 'edulume');
$edulume_content = apply_filters('edulume_home_section_content', '', 'trust-bar');

if ($edulume_content === '') {
    return;
}

?>
<section class="edulume-section edulume-home-trust-bar" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <?php echo wp_kses_post($edulume_content); ?>
    </div>
</section>
