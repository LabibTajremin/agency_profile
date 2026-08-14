<?php

/**
 * Split header: navigation split around a centred brand.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

?>
<div class="edulume-header__inner edulume-header__inner--split">
    <div class="edulume-header__nav edulume-header__nav--start"><?php edulume_the_primary_menu(); ?></div>
    <div class="edulume-header__brand edulume-header__brand--centred"><?php edulume_the_logo('header'); ?></div>
    <div class="edulume-header__actions">
        <?php edulume_the_mode_toggle(); ?>
        <?php edulume_the_drawer_toggle(); ?>
    </div>
</div>
