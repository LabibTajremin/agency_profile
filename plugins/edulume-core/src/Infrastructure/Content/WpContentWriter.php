<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Application\Port\ContentWriter;
use Edulume\Core\Domain\Content\CsvMappedRow;
use Edulume\Core\Domain\Content\CsvTarget;

/**
 * Writes imported rows as WordPress posts.
 *
 * Upsert rather than insert: the CSV importer's whole round-trip guarantee — export, edit,
 * re-import — depends on a row whose slug already exists updating that post instead of creating
 * a second one. An importer that only inserts turns one edit into a duplicate catalogue.
 */
final class WpContentWriter implements ContentWriter
{
    public function upsert(string $postTypeKey, CsvMappedRow $row): int
    {
        $slug = $row->fields[CsvTarget::Slug->value] ?? '';
        $existingId = $slug === '' ? 0 : $this->findBySlug($postTypeKey, $slug);

        $postData = [
            'post_type' => $postTypeKey,
            'post_title' => $row->title(),
            'post_name' => $slug,
            'post_content' => $row->fields[CsvTarget::Content->value] ?? '',
            'post_excerpt' => $row->fields[CsvTarget::Excerpt->value] ?? '',
            'post_status' => $row->fields[CsvTarget::Status->value] ?? 'publish',
        ];

        if ($existingId > 0) {
            $postData['ID'] = $existingId;
        }

        $id = wp_insert_post($postData, true);

        if (!is_int($id) || $id === 0) {
            return 0;
        }

        foreach ($row->meta as $key => $value) {
            update_post_meta($id, $key, $value);
        }

        foreach ($row->terms as $taxonomy => $terms) {
            // Appended rather than replaced only on an insert: on an update the CSV is the
            // source of truth for the columns it carries, and leaving stale terms behind is
            // how a re-import stops actually correcting anything.
            wp_set_object_terms($id, $terms, $taxonomy, false);
        }

        return $id;
    }

    private function findBySlug(string $postTypeKey, string $slug): int
    {
        $posts = get_posts([
            'post_type' => $postTypeKey,
            'name' => $slug,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        return is_array($posts) && isset($posts[0]) && is_int($posts[0]) ? $posts[0] : 0;
    }
}
