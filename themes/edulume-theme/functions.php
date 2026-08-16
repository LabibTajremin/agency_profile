<?php

/**
 * Edulume theme bootstrap.
 *
 * Presentation only. This theme reads tokens and calls documented plugin functions; it never
 * writes to the database and never reads a plugin option key directly. Deactivating Edulume
 * Core degrades the site to a plain but working one rather than breaking it.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const EDULUME_THEME_VERSION = '0.1.0';
const EDULUME_CORE_PLUGIN_CLASS = 'Edulume\\Core\\Infrastructure\\Wp\\Plugin';

/**
 * Whether Edulume Core is present. Everything the theme adds beyond a plain site is gated on
 * this, so a deactivated plugin is a degraded site rather than a fatal error.
 */
function edulume_core_is_active(): bool
{
    return class_exists(EDULUME_CORE_PLUGIN_CLASS);
}

require_once __DIR__ . '/inc/chrome.php';
require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/consent.php';
require_once __DIR__ . '/inc/sections.php';

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets']);

    register_nav_menus([
        'primary' => __('Primary menu', 'edulume'),
        'footer' => __('Footer menu', 'edulume'),
        'utility' => __('Utility bar', 'edulume'),
    ]);

    load_theme_textdomain('edulume', get_template_directory() . '/languages');
});

/**
 * The footer widget areas.
 *
 * Four are registered regardless of the active footer variant, because a variant switch must
 * never delete a widget: the narrower variants simply render fewer of them, and switching back
 * brings the rest into view again with their content intact.
 */
add_action('widgets_init', static function (): void {
    for ($column = 1; $column <= 4; $column++) {
        register_sidebar([
            'id' => 'edulume-footer-' . $column,
            'name' => sprintf(
                /* translators: %d: the footer column number. */
                __('Footer column %d', 'edulume'),
                $column
            ),
            'before_widget' => '<section id="%1$s" class="edulume-widget %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="edulume-widget__title">',
            'after_title' => '</h2>',
        ]);
    }
});

/**
 * The archive heading, without the "Archives:" prefix WordPress adds by default.
 *
 * The prefix reads as a database term rather than a page title, and on a course archive it is
 * actively confusing.
 */
function edulume_archive_title(): string
{
    if (is_home() && !is_front_page()) {
        return (string) get_the_title((int) get_option('page_for_posts'));
    }

    if (is_search()) {
        return (string) get_search_query();
    }

    return wp_strip_all_tags(get_the_archive_title(), true);
}

add_filter('get_the_archive_title_prefix', '__return_empty_string');

/**
 * The no-flash mode resolver.
 *
 * Inline and blocking in <head> on purpose: anything deferred, async or in an external file
 * paints the wrong theme first, and a white flash on a dark site is the single most-reported
 * complaint about theme toggles.
 */
add_action('wp_head', static function (): void {
    $script = <<<'JS'
    (function () {
      try {
        var stored = localStorage.getItem('edulume-theme');
        var system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', stored || system);
      } catch (error) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
    JS;

    // wp_print_inline_script_tag escapes and applies the site's CSP nonce if one is set;
    // printing the tag by hand does neither.
    wp_print_inline_script_tag($script);
}, 1);

/**
 * Tells the site owner why the site looks plain, once, where they will see it.
 */
add_action('admin_notices', static function (): void {
    if (edulume_core_is_active() || !current_user_can('activate_plugins')) {
        return;
    }

    printf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        esc_html__(
            'The Edulume theme is running without the Edulume Core plugin. Your content is safe, but '
            . 'settings, leads and the configurator are unavailable until the plugin is activated.',
            'edulume'
        )
    );
});
