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
 * The visitor's light/dark control, as a pair of radios.
 *
 * A radio group rather than a button because the two modes are a choice between two named
 * options, and that is what a radio group means. A lone toggle button has to encode the current
 * state and the action it will perform in the same control, which is why they so often show the
 * icon of the mode you are *not* in and leave everyone guessing.
 *
 * `role="radiogroup"` with real inputs, so arrow keys move between them, the browser handles
 * focus, and a screen reader announces "Light, radio button, 1 of 2, selected". The visible
 * labels are the icons; the accessible names come from the text beside them.
 */
function edulume_the_mode_toggle(): void
{
    $modes = [
        'light' => [
            'label' => __('Light', 'edulume'),
            /* A sun: a filled centre and eight rays. */
            'icon' => '<circle cx="12" cy="12" r="4.2"/>'
                . '<g stroke="currentColor" stroke-width="1.8" stroke-linecap="round">'
                . '<path d="M12 2.4v2.6M12 19v2.6M4.6 12H2M22 12h-2.6"/>'
                . '<path d="M5.8 5.8l1.9 1.9M16.3 16.3l1.9 1.9M18.2 5.8l-1.9 1.9M7.7 16.3l-1.9 1.9"/>'
                . '</g>',
        ],
        'dark' => [
            'label' => __('Dark', 'edulume'),
            /* A crescent, cut from one circle by another rather than drawn by hand. */
            'icon' => '<path d="M20.2 14.6A8.6 8.6 0 0 1 9.4 3.8a8.6 8.6 0 1 0 10.8 10.8z"/>',
        ],
    ];

    echo '<div class="edulume-mode-switch" role="radiogroup" aria-label="'
        . esc_attr__('Colour mode', 'edulume') . '" data-edulume-mode-switch>';

    foreach ($modes as $value => $mode) {
        printf(
            '<label class="edulume-mode-switch__option" data-mode="%1$s">'
            . '<input type="radio" name="edulume-mode" value="%1$s" class="screen-reader-text" '
            . 'data-edulume-mode-input>'
            . '<span class="edulume-mode-switch__icon" aria-hidden="true">'
            . '<svg viewBox="0 0 24 24" fill="currentColor" focusable="false">%2$s</svg>'
            . '</span>'
            . '<span class="edulume-mode-switch__label">%3$s</span>'
            . '</label>',
            esc_attr($value),
            $mode['icon'],
            esc_html($mode['label'])
        );
    }

    echo '</div>';
}

/**
 * Whether a home-page section should render.
 *
 * The documented way for a template to ask. Defaults to true so that a deactivated plugin — or
 * a slug the plugin has never heard of, such as one a child theme added — renders rather than
 * silently disappears.
 */
function edulume_section_is_enabled(string $slug): bool
{
    if (!edulume_core_is_active()) {
        return true;
    }

    return (bool) apply_filters('edulume_section_is_enabled', true, $slug);
}
