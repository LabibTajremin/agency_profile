<?php

/**
 * The site header.
 *
 * The variant decides the arrangement; everything above it — skip link, announcement, utility
 * bar — is the same in all six, because those are content, not layout.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$edulume_chrome = edulume_chrome();

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class($edulume_chrome->isHeaderSticky ? 'edulume-has-sticky-header' : ''); ?>>
<?php wp_body_open(); ?>

<a class="edulume-skip-link screen-reader-text" href="#edulume-main">
    <?php esc_html_e('Skip to content', 'edulume'); ?>
</a>

<?php if ($edulume_chrome->hasPreloader) : ?>
    <div class="edulume-preloader" data-edulume-preloader aria-hidden="true"></div>
<?php endif; ?>

<div class="edulume-page" id="top">
    <?php
    edulume_the_announcement_bar();
    edulume_the_utility_bar();
    ?>

    <header class="edulume-header edulume-header--<?php echo esc_attr($edulume_chrome->headerVariant->value); ?>"
            <?php echo $edulume_chrome->isHeaderSticky ? 'data-sticky="true"' : ''; ?>>
        <?php edulume_render_variant($edulume_chrome->headerVariant->templatePart(), 'template-parts/header/classic'); ?>
    </header>

    <?php edulume_the_drawer(); ?>

    <main class="edulume-main" id="edulume-main">
