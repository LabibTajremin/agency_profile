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
 * Dimensions are always written out and the loading strategy is always stated: an image without
 * them is a layout shift, and the hero is the one image on the page that must not be deferred.
 */
function edulume_the_demo_image(string $file, string $alt, int $width, int $height, bool $isAboveFold = false): void
{
    $url = edulume_demo_img($file);

    if ($url === '') {
        return;
    }

    if ($isAboveFold) {
        printf(
            '<img src="%1$s" alt="%2$s" width="%3$d" height="%4$d" decoding="async" fetchpriority="high" />',
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
