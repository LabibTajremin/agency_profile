<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Demo;

use Edulume\Core\Application\Port\DemoStore;
use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Demo\DemoDefinition;
use Edulume\Core\Domain\Security\SvgSanitiser;

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

        $this->applyMeta($id, $item);
        $this->applyTerms($id, $item);
        $this->applyRelationships($id, $postType, $item);

        if (is_string($item['image'] ?? null) && $item['image'] !== '') {
            $this->attachFeaturedImage($id, $demoSlug, $item['image'], (string) ($item['title'] ?? ''));
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function applyMeta(int $id, array $item): void
    {
        foreach (is_array($item['meta'] ?? null) ? $item['meta'] : [] as $key => $value) {
            if (is_string($key)) {
                update_post_meta($id, $key, $value);
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private function applyTerms(int $id, array $item): void
    {
        foreach (is_array($item['terms'] ?? null) ? $item['terms'] : [] as $taxonomy => $terms) {
            if (is_string($taxonomy) && is_array($terms)) {
                wp_set_object_terms($id, array_values(array_filter($terms, 'is_string')), $taxonomy, false);
            }
        }
    }

    /**
     * Wires the post-to-post relationships the item declares.
     *
     * Declared by title and stored as an id, because that is what the content model registers and
     * what the country page queries. A destination whose institutions are attached by a taxonomy
     * term nobody reads renders the empty state on a site with two dozen universities in it.
     *
     * The demo's post types are imported in alphabetical order, so a relationship resolves only
     * when its target sorts earlier. That holds for every relationship the packs declare; one
     * that does not resolve is skipped rather than stored as a dangling id.
     *
     * @param array<string, mixed> $item
     */
    private function applyRelationships(int $id, string $postType, array $item): void
    {
        $declared = is_array($item['relationships'] ?? null) ? $item['relationships'] : [];

        foreach (ContentModel::relationships() as $relationship) {
            if ($relationship->fromPostTypeKey !== $postType) {
                continue;
            }

            $value = $declared[$relationship->key] ?? null;
            $titles = is_array($value) ? $value : [$value];

            foreach ($titles as $title) {
                if (!is_string($title) || $title === '') {
                    continue;
                }

                $relatedId = $this->idOfTitled($relationship->toPostTypeKey, $title);

                if ($relatedId === 0) {
                    continue;
                }

                if ($relationship->allowsMany) {
                    add_post_meta($id, $relationship->metaKey(), (string) $relatedId);

                    continue;
                }

                update_post_meta($id, $relationship->metaKey(), (string) $relatedId);

                break;
            }
        }
    }

    private function idOfTitled(string $postType, string $title): int
    {
        $ids = get_posts([
            'post_type' => $postType,
            'post_status' => 'publish',
            'title' => $title,
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'fields' => 'ids',
        ]);

        return is_array($ids) && isset($ids[0]) && is_int($ids[0]) ? $ids[0] : 0;
    }

    /**
     * Copies a bundled image into the media library and sets it as the item's featured image.
     *
     * The attachment carries the same `_edulume_demo` marker as the post, so removing a demo
     * takes its imagery with it rather than leaving orphans in the library for somebody to
     * puzzle over later.
     *
     * SVG is sanitised on the way in rather than trusted. These files ship with the plugin, so
     * the risk is theoretical — but an SVG that reaches `wp_insert_attachment` unsanitised is a
     * stored-XSS primitive, and having the sanitiser and not using it is worse than not having
     * one, because it reads as though the case was handled.
     */
    private function attachFeaturedImage(int $postId, string $demoSlug, string $fileName, string $title): void
    {
        $source = sprintf('%s/demos/%s/media/%s', dirname(__DIR__, 3), $demoSlug, basename($fileName));

        if (!is_readable($source)) {
            return;
        }

        $contents = (string) file_get_contents($source);
        $isSvg = strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'svg';

        if ($isSvg) {
            $contents = (new SvgSanitiser())->sanitise($contents);

            if ($contents === '') {
                return;
            }
        }

        $uploaded = wp_upload_bits(basename($fileName), null, $contents);

        if (!is_array($uploaded) || ($uploaded['error'] ?? false) || !is_string($uploaded['file'] ?? null)) {
            return;
        }

        $attachmentId = wp_insert_attachment([
            'post_mime_type' => $isSvg ? 'image/svg+xml' : ((string) ($uploaded['type'] ?? 'image/png')),
            'post_title' => $title !== '' ? $title : basename($fileName),
            'post_content' => '',
            'post_status' => 'inherit',
        ], $uploaded['file'], $postId, true);

        if (!is_int($attachmentId) || $attachmentId === 0) {
            return;
        }

        update_post_meta($attachmentId, self::MARKER_META_KEY, $demoSlug);
        update_post_meta($attachmentId, '_wp_attachment_image_alt', $title);

        /*
         * Intermediate sizes are generated for raster images only. `wp_generate_attachment_metadata`
         * on an SVG returns an empty array on most installs and shells out to an image library on
         * the rest, which on shared hosting is where an import stops dead.
         */
        if (!$isSvg && function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';

            wp_update_attachment_metadata(
                $attachmentId,
                wp_generate_attachment_metadata($attachmentId, $uploaded['file'])
            );
        }

        set_post_thumbnail($postId, $attachmentId);
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
            /*
             * Attachments go through `wp_delete_attachment`, which removes the file from disk
             * as well as the row. `wp_delete_post` on an attachment deletes the record and
             * leaves the image in `wp-content/uploads` forever — invisible in the library, and
             * still counting against the hosting quota.
             */
            if (get_post_type($id) === 'attachment') {
                wp_delete_attachment($id, true);

                continue;
            }

            // Forced, so a rollback does not leave a thousand demo items filling the trash —
            // which is where a site owner would then find them and wonder what happened.
            wp_delete_post($id, true);
        }
    }

    public function previouslyImportedIds(): array
    {
        $ids = get_posts([
            /*
             * `any` plus `attachment`, explicitly.
             *
             * `'post_type' => 'any'` reads as "everything" and is not: WP_Query excludes post
             * types flagged `exclude_from_search`, and `attachment` is one of them. Left as
             * `any`, removing a demo deleted its posts and left every imported image behind in
             * the media library, unattached and unexplained.
             */
            'post_type' => ['any', 'attachment'],
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
