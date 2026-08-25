<?php

/**
 * Home section: the promo video rail.
 *
 * Nothing renders until the owner has added at least one video with a URL that validates, which
 * is deliberate — a rail of empty frames on a brand-new site looks broken in a way an absent
 * section does not. Somebody signed in and able to edit gets told where to add them; a visitor
 * gets nothing at all.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_rail = apply_filters('edulume_video_rail', [], 'home');
$edulume_label = __('Videos', 'edulume');

if (!is_array($edulume_rail) || ($edulume_rail['enabled'] ?? false) !== true) {
    if (!current_user_can('edit_theme_options')) {
        return;
    }

    echo '<section class="edulume-section edulume-home-section edulume-home-video-rail" aria-label="'
        . esc_attr($edulume_label) . '"><div class="edulume-container"><p class="edulume-editor-hint">'
        . esc_html__(
            'Your video row is empty. Add your Facebook, YouTube or uploaded videos under '
            . 'Edulume → Videos. Only you can see this message.',
            'edulume'
        )
        . '</p></div></section>';

    return;
}

?>
<section class="edulume-section edulume-home-section edulume-home-video-rail" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <?php get_template_part('template-parts/video/rail', null, ['rail' => $edulume_rail]); ?>
    </div>
</section>
