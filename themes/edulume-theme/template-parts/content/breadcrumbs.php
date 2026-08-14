<?php

/**
 * Breadcrumbs.
 *
 * Deferred to an SEO plugin when one is active, so the page never carries two BreadcrumbList
 * graphs — search engines treat that as a conflict rather than as reinforcement.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_trail = apply_filters('edulume_breadcrumb_trail', []);

if (!is_array($edulume_trail) || $edulume_trail === []) {
    return;
}

?>
<nav class="edulume-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'edulume'); ?>">
    <ol class="edulume-breadcrumbs__list">
        <?php foreach ($edulume_trail as $edulume_crumb) : ?>
            <li class="edulume-breadcrumbs__item">
                <?php if (!empty($edulume_crumb['url'])) : ?>
                    <a href="<?php echo esc_url((string) $edulume_crumb['url']); ?>">
                        <?php echo esc_html((string) ($edulume_crumb['label'] ?? '')); ?>
                    </a>
                <?php else : ?>
                    <span aria-current="page"><?php echo esc_html((string) ($edulume_crumb['label'] ?? '')); ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
