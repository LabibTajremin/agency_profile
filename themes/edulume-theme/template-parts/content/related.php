<?php

/**
 * Related items.
 *
 * The relationships are stored as IDs by the plugin, so this asks for them rather than running
 * its own query — an archive-wide "related" query is the classic place an unbounded one hides.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_related = apply_filters('edulume_related_items', [], get_post_type(), get_the_ID());

if (!is_array($edulume_related) || $edulume_related === []) {
    return;
}

?>
<section class="edulume-section edulume-related" aria-labelledby="edulume-related-title">
    <div class="edulume-container">
        <h2 class="edulume-related__title" id="edulume-related-title">
            <?php esc_html_e('Related', 'edulume'); ?>
        </h2>

        <div class="edulume-grid">
            <?php foreach ($edulume_related as $edulume_id) : ?>
                <article class="edulume-card">
                    <div class="edulume-card__body">
                        <h3 class="edulume-card__title">
                            <a href="<?php echo esc_url((string) get_permalink((int) $edulume_id)); ?>">
                                <?php echo esc_html(get_the_title((int) $edulume_id)); ?>
                            </a>
                        </h3>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
