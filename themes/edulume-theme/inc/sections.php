<?php

/**
 * What the home page's sections actually draw.
 *
 * Every section but the trust bar used to end at the same four lines:
 *
 *     $content = apply_filters('edulume_home_section_content', '', 'courses');
 *     if ($content === '') { return; }
 *
 * — which means that on a site where nobody has hooked that filter, and nobody has, the whole
 * home page renders as a header and a footer with nothing between them. Import a hundred and
 * seventy-four courses, destinations and testimonials and the front page still shows none of
 * them, because no section ever asked the database a question.
 *
 * These helpers ask. Editor-authored content still wins outright where it exists: somebody who
 * has written their own version of a section means it, and appending a generated grid under it
 * would be surprising.
 *
 * @package Edulume\Theme
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * The published items of one type, bounded.
 *
 * `posts_per_page` is always a real number and `no_found_rows` is always true: a home page
 * running an unbounded query with pagination counting is how a site with six hundred courses
 * takes four seconds to render a row of six.
 *
 * @return list<\WP_Post>
 */
function edulume_section_posts(string $postType, int $limit = 6): array
{
    if (!post_type_exists($postType)) {
        return [];
    }

    $posts = get_posts([
        'post_type' => $postType,
        'post_status' => 'publish',
        'posts_per_page' => max(1, min($limit, 24)),
        'orderby' => 'menu_order date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    ]);

    return is_array($posts) ? array_values(array_filter($posts, static fn ($p): bool => $p instanceof WP_Post)) : [];
}

/**
 * A meta value that holds a list, however it happens to be stored.
 *
 * WordPress meta round-trips arrays, but a value imported from a CSV or typed into a plain text
 * field arrives as a comma-separated string. Accepting both here means the template does not
 * have to know which import route a particular site used.
 *
 * @return list<string>
 */
function edulume_meta_list(int $postId, string $key): array
{
    $value = get_post_meta($postId, $key, true);

    if (is_string($value)) {
        $value = $value === '' ? [] : explode(',', $value);
    }

    if (!is_array($value)) {
        return [];
    }

    return array_values(array_filter(array_map(
        static fn ($item): string => is_scalar($item) ? trim((string) $item) : '',
        $value
    )));
}

/**
 * Every intake month offered by any university in a list, deduplicated.
 *
 * Built from the posts already fetched rather than by asking the database for distinct meta
 * values: the rows are in memory, and a second query to derive a filter's options from data the
 * page is holding is a query nobody notices until the page has sixty of them.
 *
 * @param list<\WP_Post> $universities
 *
 * @return list<string>
 */
function edulume_destination_intakes(array $universities): array
{
    $intakes = [];

    foreach ($universities as $university) {
        foreach (edulume_meta_list($university->ID, '_edulume_intakes') as $intake) {
            $intakes[$intake] = true;
        }
    }

    $names = array_keys($intakes);
    sort($names);

    return array_map('strval', $names);
}

/**
 * The heading and optional "see everything" link a section shares.
 */
function edulume_the_section_header(string $title, string $postType = ''): void
{
    echo '<header class="edulume-home-section__header">';
    printf('<h2 class="edulume-home-section__title">%s</h2>', esc_html($title));

    $archive = $postType === '' ? '' : (string) get_post_type_archive_link($postType);

    if ($archive !== '') {
        printf(
            '<a class="edulume-home-section__more" href="%s">%s</a>',
            esc_url($archive),
            esc_html__('See all', 'edulume')
        );
    }

    echo '</header>';
}

/**
 * Opens a home section, printing editor-authored content when there is any.
 *
 * Returns true when the caller should render its own content instead.
 */
function edulume_home_section_open(string $slug, string $label): bool
{
    $authored = apply_filters('edulume_home_section_content', '', $slug);

    printf(
        '<section class="edulume-section edulume-home-section edulume-home-%s" aria-label="%s">'
        . '<div class="edulume-container">',
        esc_attr($slug),
        esc_attr($label)
    );

    if (is_string($authored) && $authored !== '') {
        echo wp_kses_post($authored);

        return false;
    }

    return true;
}

function edulume_home_section_close(): void
{
    echo '</div></section>';
}

/**
 * A whole grid section in one call, printing nothing at all when it would be empty.
 */
function edulume_the_grid_section(string $slug, string $label, string $postType, int $limit = 6): void
{
    $authored = apply_filters('edulume_home_section_content', '', $slug);

    if (is_string($authored) && $authored !== '') {
        edulume_home_section_open($slug, $label);
        edulume_home_section_close();

        return;
    }

    /*
     * Queried once, then rendered inline.
     *
     * The obvious shapes both have a cost. Checking whether anything exists and then rendering
     * runs every home-page query twice; buffering the render and echoing the buffer produces a
     * built HTML string that has to be echoed unescaped, which is indistinguishable — to a
     * reader and to a linter — from echoing something untrusted. Holding the posts and printing
     * around them has neither problem.
     */
    $posts = edulume_section_posts($postType, $limit);

    if ($posts === []) {
        return;
    }

    printf(
        '<section class="edulume-section edulume-home-section edulume-home-%s" aria-label="%s">'
        . '<div class="edulume-container">',
        esc_attr($slug),
        esc_attr($label)
    );

    edulume_the_section_header($label, $postType);

    echo '<div class="edulume-grid">';

    foreach ($posts as $post) {
        // `setup_postdata` so the shared card template can use the ordinary template tags
        // rather than growing a second code path for "a post that is not the current one".
        $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        setup_postdata($post);
        get_template_part('template-parts/content/card', get_post_type($post));
    }

    wp_reset_postdata();

    echo '</div></div></section>';
}
