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
 * The visitor's light/dark control: one square button, icon only.
 *
 * It was a segmented pair of radios with the words "Light" and "Dark" beside their icons, which
 * is defensible markup and about three times wider than it needs to be. In a header that also
 * has to hold a logo, a menu and a call to action, that width is the thing that pushes the row
 * onto a second line on a laptop.
 *
 * A single button has one well-known failure mode — it must encode both the current state and
 * the action, and the ones that get it wrong show the icon of the mode you are already in. This
 * shows the mode you will switch *to*, and says so out loud: the accessible name is "Switch to
 * dark mode", not "Dark". Both icons ship in the markup and CSS cross-fades between them, so
 * the change costs no request and no reflow.
 *
 * The server cannot know the visitor's stored preference, so the button is rendered assuming
 * light and the script corrects it before paint. That is why the label lives in a data
 * attribute pair rather than only in the markup.
 */
function edulume_the_mode_toggle(): void
{
    /* A sun: a filled centre and eight rays. */
    $sun = '<circle cx="12" cy="12" r="4.2"/>'
        . '<g stroke="currentColor" stroke-width="1.75" stroke-linecap="round">'
        . '<path d="M12 2.4v2.6M12 19v2.6M4.6 12H2M22 12h-2.6"/>'
        . '<path d="M5.8 5.8l1.9 1.9M16.3 16.3l1.9 1.9M18.2 5.8l-1.9 1.9M7.7 16.3l-1.9 1.9"/>'
        . '</g>';

    /* A crescent, cut from one circle by another rather than drawn by hand. */
    $moon = '<path d="M20.2 14.6A8.6 8.6 0 0 1 9.4 3.8a8.6 8.6 0 1 0 10.8 10.8z"/>';

    $toDark = __('Switch to dark mode', 'edulume');
    $toLight = __('Switch to light mode', 'edulume');

    printf(
        '<button type="button" class="edulume-mode-toggle" data-edulume-mode-toggle '
        . 'data-label-to-dark="%1$s" data-label-to-light="%2$s" aria-label="%1$s" title="%1$s">'
        . '<span class="edulume-mode-toggle__icon edulume-mode-toggle__icon--moon" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" fill="currentColor" focusable="false">%3$s</svg></span>'
        . '<span class="edulume-mode-toggle__icon edulume-mode-toggle__icon--sun" aria-hidden="true">'
        . '<svg viewBox="0 0 24 24" fill="currentColor" focusable="false">%4$s</svg></span>'
        . '</button>',
        esc_attr($toDark),
        esc_attr($toLight),
        // Escaped rather than trusted, even though the markup a dozen lines above is a constant
        // in this file. "It is hardcoded" is how every escaping gap starts, and it stops being
        // true the first time somebody makes the icon set filterable.
        wp_kses($moon, edulume_allowed_icon_markup()),
        wp_kses($sun, edulume_allowed_icon_markup())
    );
}

/**
 * The SVG elements and attributes an inline icon may use.
 *
 * An allowlist, not a denylist: anything not named here — `script`, `foreignObject`, every
 * `on*` handler — is stripped, so an icon can only ever draw.
 *
 * @return array<string, array<string, bool>>
 */
function edulume_allowed_icon_markup(): array
{
    $shape = [
        'd' => true,
        'cx' => true,
        'cy' => true,
        'r' => true,
        'x' => true,
        'y' => true,
        'width' => true,
        'height' => true,
        'rx' => true,
        'ry' => true,
        'points' => true,
        'fill' => true,
        'fill-rule' => true,
        'stroke' => true,
        'stroke-width' => true,
        'stroke-linecap' => true,
        'stroke-linejoin' => true,
        'opacity' => true,
        'transform' => true,
    ];

    return [
        'g' => $shape,
        'path' => $shape,
        'circle' => $shape,
        'rect' => $shape,
        'line' => $shape,
        'polyline' => $shape,
        'polygon' => $shape,
        'ellipse' => $shape,
    ];
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

/**
 * The post types a visitor can shortlist or compare.
 *
 * Comparison only makes sense between things with the same shape of attributes, which is why
 * this is a list rather than "anything with a post type".
 *
 * @return array<string, list<string>>
 */
function edulume_card_action_types(): array
{
    return [
        'compare' => ['edulume_course', 'edulume_institution', 'edulume_test-prep'],
        'shortlist' => [
            'edulume_course',
            'edulume_institution',
            'edulume_scholarship',
            'edulume_destination',
        ],
    ];
}

/**
 * Shortlist and compare controls for one card.
 *
 * These buttons are why `compare.js` exists, and until now nothing rendered them: the script
 * bound to `[data-edulume-compare]` and `[data-edulume-shortlist]`, and no template in the
 * theme emitted either attribute. A hundred and twenty lines of working code with no way to
 * reach it, and no error anywhere to say so — the feature simply was not on the site.
 *
 * Rendered as real `<button>` elements with `aria-pressed`, so the state is announced rather
 * than merely coloured, and so the disabled state the script sets at the comparison limit is
 * one the browser enforces.
 */
function edulume_the_card_actions(mixed $postId, string $postType): void
{
    $id = is_int($postId) ? $postId : (int) $postId;

    if ($id <= 0) {
        return;
    }

    $types = edulume_card_action_types();
    $canCompare = in_array($postType, $types['compare'], true);
    $canShortlist = in_array($postType, $types['shortlist'], true);

    if (!$canCompare && !$canShortlist) {
        return;
    }

    echo '<div class="edulume-card__actions">';

    if ($canShortlist) {
        printf(
            '<button type="button" class="edulume-card__action" data-edulume-shortlist="%s" '
            . 'aria-pressed="false">%s</button>',
            // `%d` already forces an integer, so this is not a real escaping gap — but a
            // reader has to know printf's conversion rules to see that, and the sniff cannot
            // know them at all. Being explicit costs nothing and makes both the reviewer and
            // the linter right.
            esc_attr((string) $id),
            esc_html__('Save', 'edulume')
        );
    }

    if ($canCompare) {
        printf(
            '<button type="button" class="edulume-card__action" data-edulume-compare="%s" '
            . 'aria-pressed="false">%s</button>',
            esc_attr((string) $id),
            esc_html__('Compare', 'edulume')
        );
    }

    echo '</div>';
}
