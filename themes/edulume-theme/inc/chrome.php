<?php

/**
 * The theme's read-only view of the chrome settings.
 *
 * Every function here answers with a sensible value when Edulume Core is absent, so a
 * deactivated plugin degrades the header and footer rather than fataling the site.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Edulume\Core\Domain\Chrome\ChromeSettings;
use Edulume\Core\Domain\Chrome\FloatingAction;
use Edulume\Core\Domain\Chrome\LogoSlot;
use Edulume\Core\Domain\Theming\ThemeMode;

/**
 * The chrome settings, or the shipped defaults when the plugin is not there.
 */
function edulume_chrome(): ChromeSettings
{
    static $chrome = null;

    if ($chrome instanceof ChromeSettings) {
        return $chrome;
    }

    $stored = edulume_core_is_active() ? apply_filters('edulume_chrome_settings', []) : [];
    $chrome = ChromeSettings::fromArray(is_array($stored) ? $stored : []);

    return $chrome;
}

/**
 * Renders a template part for the configured header or footer variant.
 *
 * Falls back rather than rendering nothing: a stored variant can outlive the part that drew it
 * — a child theme removing one, an older setting after an update — and a site with no header
 * is worse than a site with the wrong header.
 */
function edulume_render_variant(string $part, string $fallback): void
{
    $chosen = file_exists(get_template_directory() . '/' . $part . '.php') ? $part : $fallback;

    get_template_part($chosen);
}

/**
 * The visitor's current mode, resolved server-side for markup that cannot wait for script.
 *
 * Only used to pick a logo file. The visible theme is set by the inline head script, which runs
 * before first paint; this is the best guess for the initial HTML and is corrected by CSS.
 */
function edulume_current_mode(): ThemeMode
{
    $cookie = isset($_COOKIE['edulume-theme']) ? sanitize_key(wp_unslash($_COOKIE['edulume-theme'])) : '';

    return $cookie === 'dark' ? ThemeMode::Dark : ThemeMode::Light;
}

/**
 * Prints the logo for a slot, or the site title when no logo is configured.
 */
function edulume_the_logo(string $slot): void
{
    $chrome = edulume_chrome();
    $enum = LogoSlot::tryFrom($slot) ?? LogoSlot::Header;
    $source = $chrome->logos->resolve($enum, edulume_current_mode());
    $alternative = $chrome->logos->alternativeText !== ''
        ? $chrome->logos->alternativeText
        : get_bloginfo('name');

    printf('<a class="edulume-logo" href="%s" rel="home">', esc_url(home_url('/')));

    if ($source === '') {
        printf('<span class="edulume-logo__text">%s</span>', esc_html(get_bloginfo('name')));
    } else {
        printf(
            '<img class="edulume-logo__image" src="%s" alt="%s" width="180" height="48" decoding="async" />',
            esc_url($source),
            esc_attr($alternative)
        );
    }

    echo '</a>';
}

/**
 * The floating action cluster.
 *
 * Rendered as real links so it works before — and without — JavaScript. Back-to-top is the one
 * exception and is marked as such rather than pretending to be a destination.
 */
function edulume_the_floating_actions(): void
{
    $chrome = edulume_chrome();
    $actions = $chrome->visibleFloatingActions();

    if ($actions === []) {
        return;
    }

    printf(
        '<div class="edulume-fab" role="complementary" aria-label="%s">',
        esc_attr__('Quick contact', 'edulume')
    );

    foreach ($actions as $action) {
        printf(
            '<a class="edulume-fab__action edulume-fab__action--%s" href="%s"%s data-icon="%s">'
            . '<span class="screen-reader-text">%s</span></a>',
            esc_attr($action->value),
            esc_url($action->href($chrome->contact)),
            $action === FloatingAction::BackToTop ? '' : ' rel="noopener"',
            esc_attr($action->icon()),
            esc_html($action->label())
        );
    }

    echo '</div>';
}

/**
 * The scheduled announcement bar. Nothing renders outside its window.
 */
function edulume_the_announcement_bar(): void
{
    $chrome = edulume_chrome();
    $now = new DateTimeImmutable('now', wp_timezone());

    if (!$chrome->showsAnnouncementAt($now)) {
        return;
    }

    $bar = $chrome->announcement;

    printf(
        '<aside class="edulume-announcement" data-dismissal-key="%s"%s>',
        esc_attr($bar->dismissalKey()),
        $bar->isDismissible ? ' data-dismissible="true"' : ''
    );

    printf('<p class="edulume-announcement__message">%s</p>', esc_html($bar->message));

    if ($bar->hasLink()) {
        printf(
            '<a class="edulume-announcement__link" href="%s">%s</a>',
            esc_url($bar->linkUrl),
            esc_html($bar->linkLabel)
        );
    }

    if ($bar->isDismissible) {
        printf(
            '<button type="button" class="edulume-announcement__dismiss" aria-label="%s">&times;</button>',
            esc_attr__('Dismiss this announcement', 'edulume')
        );
    }

    echo '</aside>';
}

/**
 * The thin utility strip above the header. Skipped entirely when it would be empty.
 */
function edulume_the_utility_bar(): void
{
    $chrome = edulume_chrome();

    if (!$chrome->showsUtilityBar()) {
        return;
    }

    $contact = $chrome->contact;

    printf(
        '<div class="edulume-utility" role="complementary" aria-label="%s">',
        esc_attr__('Contact details', 'edulume')
    );

    if ($contact->telUrl() !== '') {
        printf(
            '<a class="edulume-utility__item" href="%s">%s</a>',
            esc_url($contact->telUrl()),
            esc_html($contact->phone)
        );
    }

    if ($contact->mailtoUrl() !== '') {
        printf(
            '<a class="edulume-utility__item" href="%s">%s</a>',
            esc_url($contact->mailtoUrl()),
            esc_html($contact->email)
        );
    }

    if (trim($contact->officeHours) !== '') {
        printf('<span class="edulume-utility__item">%s</span>', esc_html($contact->officeHours));
    }

    if (has_nav_menu('utility')) {
        wp_nav_menu([
            'theme_location' => 'utility',
            'container' => false,
            'menu_class' => 'edulume-utility__menu',
            'depth' => 1,
        ]);
    }

    echo '</div>';
}

/**
 * The primary navigation, shared by every header variant.
 */
function edulume_the_primary_menu(): void
{
    if (!has_nav_menu('primary')) {
        return;
    }

    $chrome = edulume_chrome();

    printf(
        '<nav class="edulume-nav%s" aria-label="%s">',
        $chrome->hasMegaMenu ? ' edulume-nav--mega' : '',
        esc_attr__('Primary', 'edulume')
    );

    wp_nav_menu([
        'theme_location' => 'primary',
        'container' => false,
        'menu_class' => 'edulume-nav__list',
        'depth' => $chrome->hasMegaMenu ? 3 : 2,
    ]);

    echo '</nav>';
}

/**
 * The mobile drawer trigger. The drawer itself is markup the header variants share.
 */
function edulume_the_drawer_toggle(): void
{
    printf(
        '<button type="button" class="edulume-drawer-toggle" aria-expanded="false" '
        . 'aria-controls="edulume-drawer"><span class="screen-reader-text">%s</span>'
        . '<span class="edulume-drawer-toggle__bars" aria-hidden="true"></span></button>',
        esc_html__('Open menu', 'edulume')
    );
}

/**
 * The drawer, closed by default and focus-trapped once opened by script.
 */
function edulume_the_drawer(): void
{
    printf(
        '<div class="edulume-drawer" id="edulume-drawer" hidden role="dialog" aria-modal="true" aria-label="%s">',
        esc_attr__('Site menu', 'edulume')
    );

    printf(
        '<button type="button" class="edulume-drawer__close">%s</button>',
        esc_html__('Close', 'edulume')
    );

    if (has_nav_menu('primary')) {
        wp_nav_menu([
            'theme_location' => 'primary',
            'container' => false,
            'menu_class' => 'edulume-drawer__list',
            'depth' => 3,
        ]);
    }

    echo '</div>';
}

/**
 * The visitor's light/dark toggle.
 */
function edulume_the_mode_toggle(): void
{
    printf(
        '<button type="button" class="edulume-mode-toggle" data-edulume-mode-toggle aria-pressed="false">'
        . '<span class="screen-reader-text">%s</span></button>',
        esc_html__('Switch between light and dark', 'edulume')
    );
}
