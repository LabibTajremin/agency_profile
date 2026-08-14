<?php

/**
 * Home section: the eligibility quiz entry point — the highest-converting element on the
 * page.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Check your eligibility', 'edulume');
$edulume_content = apply_filters('edulume_home_section_content', '', 'eligibility');

if ($edulume_content === '') {
    return;
}

?>
<section class="edulume-section edulume-home-eligibility" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <?php echo wp_kses_post($edulume_content); ?>
    </div>
</section>
