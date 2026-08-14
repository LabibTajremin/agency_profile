<?php

/**
 * The maintenance template.
 *
 * Deliberately self-contained: maintenance mode is exactly the moment when the rest of the site
 * might not load, so this leans on nothing but the tokens already inlined in <head>.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title><?php echo esc_html(get_bloginfo('name')); ?></title>
    <?php wp_head(); ?>
</head>
<body class="edulume-maintenance">
<main class="edulume-maintenance__inner">
    <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
    <p><?php esc_html_e('We are making a few changes and will be back shortly.', 'edulume'); ?></p>
    <?php
    $edulume_contact = edulume_chrome()->contact;

    if ($edulume_contact->mailtoUrl() !== '') :
        ?>
        <p>
            <a href="<?php echo esc_url($edulume_contact->mailtoUrl()); ?>">
                <?php echo esc_html($edulume_contact->email); ?>
            </a>
        </p>
    <?php endif; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
