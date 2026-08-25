<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Application\Port\ContentReader;
use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Content\CsvMappedRow;
use WP_Post;

/**
 * Reads a post type back out for export.
 *
 * A generator, and paged rather than one query for everything: exporting six hundred courses in
 * a single `get_posts` builds six hundred post objects and their whole meta cache before the
 * first line of CSV is written, and that is the shape of request shared hosting kills.
 *
 * Relationships are exported as the related item's title rather than its id, because an id is
 * meaningless in a spreadsheet and unusable on the way back in — the importer resolves titles.
 *
 * Terms come back as a list because that is what `CsvMappedRow` holds; the exporter is the thing
 * that decides they are joined with a pipe, and it should stay the only thing that decides it.
 */
final class WpContentReader implements ContentReader
{
    private const PAGE_SIZE = 100;

    public function read(string $postTypeKey, array $filters): iterable
    {
        $definition = null;

        foreach (ContentModel::postTypes() as $candidate) {
            if ($candidate->key === $postTypeKey) {
                $definition = $candidate;
            }
        }

        if ($definition === null) {
            return;
        }

        $page = 1;
        $rowNumber = 0;

        while (true) {
            $posts = get_posts([
                'post_type' => $postTypeKey,
                'post_status' => $filters['status'] ?? ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => self::PAGE_SIZE,
                'paged' => $page,
                'orderby' => 'ID',
                'order' => 'ASC',
                'no_found_rows' => true,
                'suppress_filters' => false,
            ]);

            if (!is_array($posts) || $posts === []) {
                return;
            }

            foreach ($posts as $post) {
                if ($post instanceof WP_Post) {
                    $rowNumber++;

                    yield $this->rowFor($post, $definition->taxonomyKeys, $rowNumber);
                }
            }

            $page++;
        }
    }

    /**
     * @param list<string> $taxonomyKeys
     */
    private function rowFor(WP_Post $post, array $taxonomyKeys, int $rowNumber): CsvMappedRow
    {
        $terms = [];

        foreach ($taxonomyKeys as $taxonomy) {
            $names = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'names']);
            $terms[$taxonomy] = is_array($names) ? array_values(array_filter($names, 'is_string')) : [];
        }

        return CsvMappedRow::of(
            $rowNumber,
            [
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'slug' => $post->post_name,
                'status' => $post->post_status,
            ],
            $this->metaOf($post->ID),
            $terms,
            $this->relationshipsOf($post),
            []
        );
    }

    /**
     * Only the plugin's own meta. Exporting everything hands over whatever other plugins have
     * attached to the post, which is neither useful in a spreadsheet nor ours to hand over.
     *
     * @return array<string, string>
     */
    private function metaOf(int $postId): array
    {
        $meta = [];
        $stored = get_post_meta($postId);

        if (!is_array($stored)) {
            return $meta;
        }

        foreach ($stored as $key => $values) {
            if (!is_string($key) || !str_starts_with($key, '_edulume_') || str_starts_with($key, '_edulume_rel_')) {
                continue;
            }

            $first = is_array($values) ? ($values[0] ?? '') : $values;
            $meta[$key] = is_scalar($first) ? (string) $first : '';
        }

        return $meta;
    }

    /**
     * @return array<string, string>
     */
    private function relationshipsOf(WP_Post $post): array
    {
        $relationships = [];

        foreach (ContentModel::relationships() as $relationship) {
            if ($relationship->fromPostTypeKey !== $post->post_type) {
                continue;
            }

            $related = get_post_meta($post->ID, $relationship->metaKey(), !$relationship->allowsMany);
            $titles = [];

            foreach (is_array($related) ? $related : [$related] as $id) {
                $title = is_scalar($id) ? get_the_title((int) $id) : '';

                if ($title !== '') {
                    $titles[] = $title;
                }
            }

            $relationships[$relationship->key] = implode('|', $titles);
        }

        return $relationships;
    }
}
