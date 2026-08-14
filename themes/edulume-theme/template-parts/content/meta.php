<?php

/**
 * The meta strip on a single item.
 *
 * One file rather than sixteen: what a type shows is registered data, filtered per type, so
 * adding a field to courses does not mean copying a template.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_meta = apply_filters('edulume_single_meta', [], get_post_type(), get_the_ID());

if (!is_array($edulume_meta) || $edulume_meta === []) {
    return;
}

?>
<dl class="edulume-meta">
    <?php foreach ($edulume_meta as $edulume_row) : ?>
        <?php if (empty($edulume_row['value'])) : ?>
            <?php continue; ?>
        <?php endif; ?>
        <div class="edulume-meta__row">
            <dt class="edulume-meta__label"><?php echo esc_html((string) ($edulume_row['label'] ?? '')); ?></dt>
            <dd class="edulume-meta__value"><?php echo esc_html((string) $edulume_row['value']); ?></dd>
        </div>
    <?php endforeach; ?>
</dl>
