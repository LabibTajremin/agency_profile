<?php

/**
 * Stacked header: brand and actions on the first row, full-width navigation below.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

?>
<div class="edulume-header__inner edulume-header__inner--stacked">
    <div class="edulume-header__brand"><?php edulume_the_logo('header'); ?></div>
    <div class="edulume-header__actions">
        <?php edulume_the_mode_toggle(); ?>
        <?php edulume_the_drawer_toggle(); ?>
    </div>
    <div class="edulume-header__nav edulume-header__nav--full"><?php edulume_the_primary_menu(); ?></div>
</div>
