<?php

/**
 * Home section: the closing call to action.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Get in touch', 'edulume');
$edulume_content = apply_filters('edulume_home_section_content', '', 'cta');

if ($edulume_content === '') {
    return;
}

?>
<section class="edulume-section edulume-home-cta" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <?php echo wp_kses_post($edulume_content); ?>
    </div>
</section>
