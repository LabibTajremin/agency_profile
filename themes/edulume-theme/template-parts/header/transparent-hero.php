<?php

/**
 * Transparent header: identical markup to the classic arrangement, drawn over a full-bleed
 * hero. Falls back to the solid treatment wherever no hero follows.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

?>
<div class="edulume-header__inner edulume-header__inner--transparent-hero">
    <div class="edulume-header__brand"><?php edulume_the_logo('header'); ?></div>
    <div class="edulume-header__nav"><?php edulume_the_primary_menu(); ?></div>
    <div class="edulume-header__actions">
        <?php edulume_the_mode_toggle(); ?>
        <?php edulume_the_drawer_toggle(); ?>
    </div>
</div>
