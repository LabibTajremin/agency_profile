<?php

/**
 * The consent banner.
 *
 * The banner is not the point — the gating is. A banner that records a choice and then loads
 * the analytics anyway is worse than none: it collects the same data and adds a claim that it
 * did not. So every tracking script goes through `edulume_consent_allows()`, and nothing is
 * printed for a category the visitor has not agreed to.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Edulume\Core\Domain\Tracking\ConsentCategory;
use Edulume\Core\Domain\Tracking\ConsentState;

const EDULUME_CONSENT_COOKIE = 'edulume-consent';

/**
 * What the visitor has agreed to, read from their cookie.
 */
function edulume_consent_state(): ConsentState
{
    static $state = null;

    if ($state instanceof ConsentState) {
        return $state;
    }

    $raw = isset($_COOKIE[EDULUME_CONSENT_COOKIE])
        ? sanitize_text_field(wp_unslash((string) $_COOKIE[EDULUME_CONSENT_COOKIE]))
        : '';

    $state = ConsentState::fromCookie($raw);

    return $state;
}

function edulume_consent_allows(string $category): bool
{
    $enum = ConsentCategory::tryFrom($category);

    return $enum !== null && edulume_consent_state()->allows($enum);
}

/**
 * Renders the banner, unless the visitor has already answered.
 */
function edulume_the_consent_banner(): void
{
    if (!edulume_core_is_active() || edulume_consent_state()->hasBeenAnswered()) {
        return;
    }

    printf(
        '<section class="edulume-consent" data-edulume-consent role="region" aria-label="%s">',
        esc_attr__('Cookie choices', 'edulume')
    );

    printf('<p class="edulume-consent__text">%s</p>', esc_html__(
        'We use necessary cookies to run this site, and optional ones for analytics and '
        . 'marketing. You choose which.',
        'edulume'
    ));

    echo '<div class="edulume-consent__choices">';

    foreach (ConsentCategory::cases() as $category) {
        if ($category === ConsentCategory::Necessary) {
            continue;
        }

        printf(
            '<label class="edulume-consent__choice"><input type="checkbox" value="%s" checked /> %s</label>',
            esc_attr($category->value),
            esc_html($category->label())
        );
    }

    echo '</div><div class="edulume-consent__actions">';

    printf(
        '<button type="button" data-edulume-consent-accept>%s</button>',
        esc_html__('Accept selected', 'edulume')
    );
    printf(
        '<button type="button" data-edulume-consent-reject>%s</button>',
        esc_html__('Necessary only', 'edulume')
    );

    echo '</div></section>';
}

add_action('wp_footer', 'edulume_the_consent_banner', 5);

/**
 * Prints the tracking scripts the visitor has actually agreed to.
 */
add_action('wp_footer', static function (): void {
    if (!edulume_core_is_active()) {
        return;
    }

    foreach (apply_filters('edulume_tracking_scripts', []) as $script) {
        if (!is_array($script) || !is_string($script['category'] ?? null)) {
            continue;
        }

        if (edulume_consent_allows($script['category']) && is_string($script['src'] ?? null)) {
            wp_enqueue_script(
                'edulume-tracking-' . sanitize_key($script['category']),
                $script['src'],
                [],
                null,
                true
            );
        }
    }
}, 20);
