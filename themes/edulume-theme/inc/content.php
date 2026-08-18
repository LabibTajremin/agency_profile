<?php

/**
 * Reading section copy.
 *
 * The theme asks for a value by dot path and gets one. Where it comes from is the plugin's
 * business: a stored value if the owner has edited that field, the bundled demo copy if they
 * have not. With the plugin inactive the filter is unhooked and the caller's fallback is
 * returned, which is the same graceful degradation every other bridge in this theme uses.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * A single value at a dot path.
 */
function edulume_opt(string $path, string $fallback = ''): string
{
    $value = apply_filters('edulume_content_value', $fallback, $path);

    return is_scalar($value) ? (string) $value : $fallback;
}

/**
 * A repeated block — services, destinations, testimonials and their kind.
 *
 * Rows that are not arrays are dropped rather than rendered, because a half-shaped row reaches
 * the template as a string and the template reads it as an array.
 *
 * @return list<array<string, mixed>>
 */
function edulume_opt_list(string $path): array
{
    $value = apply_filters('edulume_content_value', [], $path);

    if (!is_array($value)) {
        return [];
    }

    return array_values(array_filter($value, 'is_array'));
}

/**
 * One field of a repeated row.
 */
function edulume_row(array $row, string $key, string $fallback = ''): string
{
    $value = $row[$key] ?? null;

    return is_scalar($value) ? (string) $value : $fallback;
}

/**
 * Somebody's initials, for the case where there is no photograph.
 *
 * `mb_substr`, not `substr`: a Bangla or Arabic name cut at one byte is a broken glyph, and this
 * theme ships for sites whose staff list is not in Latin script.
 */
function edulume_initials(string $name): string
{
    $initials = '';

    foreach (array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2) as $part) {
        if ($part !== '') {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
    }

    return $initials;
}

/**
 * The URL of a bundled demo artwork, or an empty string when there is none.
 */
function edulume_demo_img(string $file): string
{
    $url = apply_filters('edulume_demo_media_url', '', $file);

    return is_string($url) ? $url : '';
}

/**
 * A demo image, printed only when one resolves.
 *
 * Dimensions are always written out, and the loading strategy is always stated rather than
 * defaulted: an image without dimensions is a layout shift, and an image without a stated
 * priority gets whatever the browser guesses.
 *
 * Three strategies, not two. `high` is for the one image that is the largest contentful paint;
 * `eager` is for an above-the-fold image that must not be deferred but must not compete with
 * the stylesheet either — a decorative wash behind the headline is exactly that, and marking it
 * `high` spends the page's most contested bandwidth on something nobody reads. `lazy` is
 * everything below the fold.
 *
 * @param 'high'|'eager'|'lazy' $loading
 */
function edulume_the_demo_image(
    string $file,
    string $alt,
    int $width,
    int $height,
    string $loading = 'lazy'
): void {
    $url = edulume_demo_img($file);

    if ($url === '') {
        return;
    }

    /*
     * A branch per strategy with the attributes written out as literals, rather than one printf
     * with the attribute string interpolated. The escaping audit reads an interpolated attribute
     * as unescaped output and cannot tell that the value is one of three constants — and being
     * right for a reason the reader has to reconstruct is not being clear.
     */
    if ($loading === 'high') {
        printf(
            '<img src="%1$s" alt="%2$s" width="%3$d" height="%4$d" decoding="async" fetchpriority="high" />',
            esc_url($url),
            esc_attr($alt),
            (int) $width,
            (int) $height
        );

        return;
    }

    if ($loading === 'eager') {
        printf(
            '<img src="%1$s" alt="%2$s" width="%3$d" height="%4$d" decoding="async" loading="eager" />',
            esc_url($url),
            esc_attr($alt),
            (int) $width,
            (int) $height
        );

        return;
    }

    printf(
        '<img src="%1$s" alt="%2$s" width="%3$d" height="%4$d" decoding="async" loading="lazy" />',
        esc_url($url),
        esc_attr($alt),
        (int) $width,
        (int) $height
    );
}
