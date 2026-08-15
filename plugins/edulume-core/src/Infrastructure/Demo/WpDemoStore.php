<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Demo;

use Edulume\Core\Application\Port\DemoStore;
use Edulume\Core\Domain\Demo\DemoDefinition;

/**
 * Writes demo content into WordPress.
 *
 * Every created item carries `_edulume_demo` with the demo's slug. That marker is the entire
 * basis of a clean rollback: without it, "remove the demo" has to guess, and a wrong guess
 * deletes something the site owner wrote.
 */
final class WpDemoStore implements DemoStore
{
    public const MARKER_META_KEY = '_edulume_demo';

    public function createItem(string $demoSlug, string $postType, array $item): int
    {
        $id = wp_insert_post([
            'post_type' => $postType,
            'post_title' => is_string($item['title'] ?? null) ? $item['title'] : '',
            'post_content' => is_string($item['content'] ?? null) ? $item['content'] : '',
            'post_excerpt' => is_string($item['excerpt'] ?? null) ? $item['excerpt'] : '',
            'post_status' => 'publish',
        ], true);

        if (!is_int($id) || $id === 0) {
            return 0;
        }

        update_post_meta($id, self::MARKER_META_KEY, $demoSlug);

        foreach (is_array($item['meta'] ?? null) ? $item['meta'] : [] as $key => $value) {
            if (is_string($key)) {
                update_post_meta($id, $key, $value);
            }
        }

        foreach (is_array($item['terms'] ?? null) ? $item['terms'] : [] as $taxonomy => $terms) {
            if (is_string($taxonomy) && is_array($terms)) {
                wp_set_object_terms($id, array_values(array_filter($terms, 'is_string')), $taxonomy, false);
            }
        }

        return $id;
    }

    /**
     * The demo payloads live at the plugin root, so this walks up three levels, not two.
     *
     * `__DIR__` is `<plugin>/src/Infrastructure/Demo`; two levels reaches `<plugin>/src`, which
     * has no `demos/` in it. That off-by-one made every import silently create nothing: the
     * unreadable file returns an empty list, the importer reports "complete", and the site owner
     * is told the demo imported when not one row was written. A wrong path that throws is a bug
     * someone fixes in a minute; a wrong path that returns `[]` is a bug that ships.
     */
    public function itemsFor(DemoDefinition $demo, string $postType): array
    {
        $file = sprintf('%s/demos/%s/%s.json', dirname(__DIR__, 3), $demo->slug, $postType);

        if (!is_readable($file)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_array'));
    }

    public function applySettings(array $settings): void
    {
        do_action('edulume_demo_settings', $settings);
    }

    public function assignMenus(string $demoSlug, array $menuLocations): void
    {
        $locations = get_nav_menu_locations();
        $locations = is_array($locations) ? $locations : [];

        foreach ($menuLocations as $location) {
            $menu = wp_get_nav_menu_object($demoSlug . '-' . $location);

            if ($menu !== false && isset($menu->term_id)) {
                $locations[$location] = (int) $menu->term_id;
            }
        }

        set_theme_mod('nav_menu_locations', $locations);
    }

    public function assignHomepage(string $demoSlug, string $homepageTitle): void
    {
        $page = get_page_by_path(sanitize_title($homepageTitle), OBJECT, 'page');

        if ($page === null) {
            return;
        }

        update_option('show_on_front', 'page');
        update_option('page_on_front', $page->ID);
    }

    public function deleteItems(array $itemIds): void
    {
        foreach ($itemIds as $id) {
            // Forced, so a rollback does not leave a thousand demo items filling the trash —
            // which is where a site owner would then find them and wonder what happened.
            wp_delete_post($id, true);
        }
    }

    public function previouslyImportedIds(): array
    {
        $ids = get_posts([
            'post_type' => 'any',
            'post_status' => 'any',
            'meta_key' => self::MARKER_META_KEY,
            'posts_per_page' => 500,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        return array_values(array_filter(is_array($ids) ? $ids : [], 'is_int'));
    }

    public function hasAuthoredContent(): bool
    {
        $authored = get_posts([
            'post_type' => 'any',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => self::MARKER_META_KEY,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        return is_array($authored) && $authored !== [];
    }
}
