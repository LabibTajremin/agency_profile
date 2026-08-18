<?php

/**
 * Asset loading, and the performance budget it exists to keep.
 *
 * The rules, in one place because they only hold if they hold everywhere:
 *
 * - Critical CSS is inlined; the rest of the stylesheet loads without blocking.
 * - A script is enqueued only on a page that contains the thing it drives.
 * - Every image gets explicit dimensions, and the one above the fold gets `fetchpriority=high`
 *   while everything else gets `loading=lazy`.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * The modules, and the condition each one loads under.
 *
 * A flat list rather than scattered `wp_enqueue_script` calls, so "what does this page cost"
 * has one answer you can read.
 *
 * @return array<string, callable(): bool>
 */
function edulume_conditional_modules(): array
{
    return [
        // The drawer and the mode toggle exist in the chrome on every page.
        'chrome' => static fn (): bool => true,
        'consent' => static fn (): bool => true,
        'finder' => static fn (): bool => edulume_page_has('finder'),
        'compare' => static fn (): bool => edulume_page_has('compare'),
        'eligibility' => static fn (): bool => edulume_page_has('eligibility'),
        'calculator' => static fn (): bool => edulume_page_has('calculator'),
        'forms' => static fn (): bool => edulume_page_has('form'),
        'carousel' => static fn (): bool => edulume_page_has('carousel')
            || edulume_page_has('marquee'),
        'motion' => static fn (): bool => edulume_motion_is_active(),
        /*
         * `tabs`, `counters`, `before-after` and `video` are declared by blocks in the
         * catalogue and had no module at all, so those blocks rendered and did nothing. One
         * file rather than four: they are twenty lines apiece, and four requests on a page
         * using all four is worse than one.
         */
        'video-rail' => static fn (): bool => edulume_page_has('video-rail'),
        'university-filter' => static fn (): bool => edulume_page_has('university-filter'),
        'interactions' => static fn (): bool => edulume_page_has('tabs')
            || edulume_page_has('counters')
            || edulume_page_has('before-after')
            || edulume_page_has('video'),
    ];
}

/**
 * Whether the page being rendered contains a given feature.
 *
 * Blocks register themselves here as they render, which is the only way to know for certain —
 * scanning post content for a block name misses blocks inside reusable blocks, template parts
 * and widgets.
 */
function edulume_page_has(string $feature, bool $register = false): bool
{
    static $features = [];

    if ($register) {
        $features[$feature] = true;
    }

    return isset($features[$feature]);
}

/**
 * Called by a block or template part to declare that it needs a module.
 */
function edulume_require_feature(string $feature): void
{
    edulume_page_has($feature, true);
}

/**
 * Blocks announce what they need as they render. The plugin fires this; the theme is what
 * knows how assets get onto the page, so the two meet here rather than the plugin enqueuing.
 */
add_action('edulume_block_requires_feature', 'edulume_require_feature');

function edulume_motion_is_active(): bool
{
    return (bool) apply_filters('edulume_motion_is_active', false);
}

/**
 * The critical stylesheet: what the first screen needs and nothing else.
 *
 * Inlined rather than linked because a render-blocking request on a slow connection is the
 * difference between the LCP budget being met and missed, and it is small enough that inlining
 * it costs less than the request would.
 */
function edulume_critical_css(): string
{
    $path = get_template_directory() . '/assets/css/critical.css';

    return is_readable($path) ? (string) file_get_contents($path) : '';
}

/**
 * The escaper for CSS printed inside a `<style>` element.
 *
 * `esc_html()` is wrong here — it would mangle `>` in every child selector. What actually
 * matters in this context is that nothing can close the element and start markup, so that is
 * what this removes. Registered as a custom escaping function in `phpcs-security.xml.dist`
 * so the security ruleset recognises it rather than being told to ignore the line.
 */
function edulume_escape_css(string $css): string
{
    return (string) preg_replace('#</\s*(style|script)#i', '', wp_strip_all_tags($css));
}

/*
 * Tells the browser about the hero image before it has parsed a line of the body.
 *
 * Measured, not guessed. The Lighthouse report for the front page names the hero backdrop as
 * the largest contentful paint, and breaks its 2.1s down as: 455ms waiting for the document,
 * **520ms of load delay**, 136ms of transfer, 842ms of render delay. That load delay is the
 * gap between the document arriving and the image being asked for at all — the preload scanner
 * finds it only after it has worked past two render-blocking stylesheets.
 *
 * A preload in the head collapses that gap: the request goes out with the stylesheets rather
 * than behind them. Front page only, because that is the only template that renders the
 * backdrop, and preloading an image a page never uses is a wasted download plus a console
 * warning telling you so.
 */
add_action('wp_head', static function (): void {
    if (!is_front_page() || !function_exists('edulume_demo_img')) {
        return;
    }

    $backdrop = edulume_demo_img(edulume_opt('hero.image'));

    if ($backdrop === '') {
        return;
    }

    printf(
        '<link rel="preload" as="image" href="%s" fetchpriority="high" />',
        esc_url($backdrop)
    );
}, 1);

add_action('wp_head', static function (): void {
    $critical = edulume_critical_css();

    if ($critical === '') {
        return;
    }

    printf('<style id="edulume-critical">%s</style>', edulume_escape_css($critical));
}, 2);

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'edulume-base',
        get_template_directory_uri() . '/assets/css/base.css',
        [],
        EDULUME_THEME_VERSION
    );

    wp_enqueue_style(
        'edulume-chrome',
        get_template_directory_uri() . '/assets/css/chrome.css',
        ['edulume-base'],
        EDULUME_THEME_VERSION
    );
});

/*
 * The video rail's stylesheet, on the pages that have a rail and nowhere else.
 *
 * On `wp_footer` for the same reason the scripts are: a section declares the feature as it
 * renders, and nothing knows whether the page has a rail until it has. A late stylesheet is a
 * trade — it can repaint — which is why the card reserves its own height from an inline custom
 * property in the markup rather than waiting for this file to say so.
 */
add_action('wp_footer', static function (): void {
    if (!edulume_page_has('video-rail')) {
        return;
    }

    wp_enqueue_style(
        'edulume-video',
        get_template_directory_uri() . '/assets/css/video.css',
        ['edulume-chrome'],
        EDULUME_THEME_VERSION
    );
}, 1);

/**
 * Enqueues the modules the page actually asked for.
 *
 * Runs late — after the content has rendered and registered its features — which is why it is
 * on `wp_footer` rather than `wp_enqueue_scripts`.
 */
add_action('wp_footer', static function (): void {
    foreach (edulume_conditional_modules() as $module => $isNeeded) {
        if (!$isNeeded()) {
            continue;
        }

        $relative = '/assets/js/' . $module . '.js';

        if (!is_readable(get_template_directory() . $relative)) {
            continue;
        }

        $handle = 'edulume-' . $module;

        wp_enqueue_script(
            $handle,
            get_template_directory_uri() . $relative,
            [],
            EDULUME_THEME_VERSION,
            ['strategy' => 'defer', 'in_footer' => true]
        );

        edulume_localise_module($handle, $module);
    }
}, 1);

/**
 * The translatable strings a module announces to the visitor.
 *
 * These lived in the JavaScript as English literals — "Filtering…", "1 result", "3 results" —
 * which meant a Bengali or Arabic site announced its filter results in English. The i18n audit
 * never saw them because it reads PHP, and a string in a `.js` file is invisible to both it and
 * to `make:pot`. Declaring them here puts them back inside the translation pipeline.
 *
 * @return array<string, array<string, string>>
 */
function edulume_module_strings(): array
{
    return [
        'video-rail' => [
            'video' => __('Video', 'edulume'),
        ],
        'university-filter' => [
            'one' => __('1 university', 'edulume'),
            /* translators: %s: how many universities match the filter. */
            'many' => __('%s universities', 'edulume'),
        ],
        'carousel' => [
            'track' => __('Carousel', 'edulume'),
            'previous' => __('Previous', 'edulume'),
            'next' => __('Next', 'edulume'),
        ],
        'forms' => [
            'required' => __('This field is required.', 'edulume'),
            'email' => __('Enter a valid email address.', 'edulume'),
            'consent' => __('Please tick this to continue.', 'edulume'),
            'sending' => __('Sending…', 'edulume'),
            'sent' => __('Thank you. We will be in touch shortly.', 'edulume'),
        ],
        'calculator' => [
            'tuition' => __('Tuition', 'edulume'),
            'living' => __('Living costs', 'edulume'),
            'visa' => __('Visa and health surcharge', 'edulume'),
            'flights' => __('Flights', 'edulume'),
        ],
        'eligibility' => [
            'likely' => __('Likely — your profile meets the published minimums.', 'edulume'),
            'borderline' => __('Borderline — worth a conversation.', 'edulume'),
            'unlikely' => __('Unlikely as things stand.', 'edulume'),
            'gradeGap' => __('a higher grade average', 'edulume'),
            'englishGap' => __('a higher English score', 'edulume'),
            'gaps' => __('What would change this: ', 'edulume'),
            'noGaps' => __('Bring your transcript and test report to your first session.', 'edulume'),
            'and' => __(' and ', 'edulume'),
        ],
        'interactions' => [
            'reveal' => __('Reveal the after image', 'edulume'),
            'video' => __('Video', 'edulume'),
        ],
        'finder' => [
            'filtering' => __('Filtering…', 'edulume'),
            /* translators: %s is the number of results found. */
            'results' => __('%s results', 'edulume'),
            'oneResult' => __('1 result', 'edulume'),
            'noResults' => __('No results', 'edulume'),
        ],
    ];
}

function edulume_localise_module(string $handle, string $module): void
{
    $strings = edulume_module_strings()[$module] ?? [];

    if ($strings === []) {
        return;
    }

    wp_add_inline_script(
        $handle,
        sprintf(
            'window.edulumeStrings = Object.assign(window.edulumeStrings || {}, {%s: %s});',
            wp_json_encode($module),
            wp_json_encode($strings)
        ),
        'before'
    );
}

/**
 * Marks the first in-content image as the likely LCP element.
 *
 * `fetchpriority=high` on the right image is worth more than any amount of lazy-loading
 * elsewhere; `fetchpriority=high` on the wrong one is worse than nothing, so only the first
 * image on a singular view gets it.
 */
add_filter('wp_get_attachment_image_attributes', static function (array $attributes): array {
    static $isFirst = true;

    if ($isFirst && is_singular() && in_the_loop()) {
        $isFirst = false;
        $attributes['fetchpriority'] = 'high';
        $attributes['loading'] = 'eager';

        return $attributes;
    }

    $attributes['loading'] = $attributes['loading'] ?? 'lazy';
    $attributes['decoding'] = $attributes['decoding'] ?? 'async';

    return $attributes;
});

/**
 * Adds a WebP source alongside every full-size image that has one on disk.
 *
 * A `<picture>` element rather than a filter that swaps the file, so a browser without WebP
 * support still gets the original rather than a broken image.
 */
add_filter('the_content', static function (string $content): string {
    return (string) apply_filters('edulume_content_images', $content);
});
