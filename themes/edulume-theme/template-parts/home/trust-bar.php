<?php

/**
 * Home section: partner and accreditation logos.
 *
 * Renders the Partner content type directly rather than waiting for editor-authored content,
 * so the section is populated the moment the demo is imported or a partner is added.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_label = __('Trusted by', 'edulume');
$edulume_content = apply_filters('edulume_home_section_content', '', 'trust-bar');

// Editor-authored content wins outright: somebody who has written their own version of this
// section means it, and silently appending a generated list under it would be surprising.
if ($edulume_content !== '') {
    ?>
    <section class="edulume-section edulume-home-trust-bar" aria-label="<?php echo esc_attr($edulume_label); ?>">
        <div class="edulume-container"><?php echo wp_kses_post($edulume_content); ?></div>
    </section>
    <?php
    return;
}

$edulume_partners = get_posts([
    'post_type' => 'edulume_partner',
    'post_status' => 'publish',
    // Bounded, because an unbounded query on a home page is how a site with two hundred
    // partners becomes a slow site with two hundred partners.
    'posts_per_page' => 12,
    'orderby' => 'menu_order title',
    'order' => 'ASC',
    'no_found_rows' => true,
]);

if ($edulume_partners === []) {
    return;
}

$edulume_shows_names = edulume_chrome()->showsPartnerNames;

?>
<section class="edulume-section edulume-home-trust-bar" aria-label="<?php echo esc_attr($edulume_label); ?>">
    <div class="edulume-container">
        <h2 class="edulume-home-trust-bar__title"><?php echo esc_html($edulume_label); ?></h2>

        <ul
            class="edulume-logo-wall"
            data-show-names="<?php echo $edulume_shows_names ? 'true' : 'false'; ?>"
        >
            <?php foreach ($edulume_partners as $edulume_partner) : ?>
                <li class="edulume-logo-wall__item">
                    <?php if (has_post_thumbnail($edulume_partner)) : ?>
                        <?php
                        echo get_the_post_thumbnail(
                            $edulume_partner,
                            'medium',
                            [
                                'class' => 'edulume-logo-wall__mark',
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                // Empty alt: the name beside it already names the partner, and
                                // a screen reader announcing it twice is noise. When names are
                                // switched off the name is still in the markup, just hidden.
                                'alt' => '',
                            ]
                        );
                        ?>
                    <?php endif; ?>

                    <span class="edulume-logo-wall__name">
                        <?php echo esc_html(get_the_title($edulume_partner)); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
