<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Application\Port\FinderRepository;

/**
 * Reads published content through `WP_Query` for the finders and the export.
 *
 * Always bounded. `posts_per_page` comes from a clamped `FinderQuery`, and `no_found_rows` is
 * off only because the finder needs a total for its pager — a count nobody displays is a second
 * query for nothing.
 */
final class WpFinderRepository implements FinderRepository
{
    private const MAXIMUM_PER_PAGE = 100;

    public function find(string $postTypeKey, array $filters): array
    {
        $query = new \WP_Query($this->argumentsFor($postTypeKey, $filters));
        $rows = [];

        foreach ($query->posts as $post) {
            if ($post instanceof \WP_Post) {
                $rows[] = $this->rowFor($post);
            }
        }

        return $rows;
    }

    public function count(string $postTypeKey, array $filters): int
    {
        // Counted with `fields => ids` and one row per page, so the total comes back without
        // hydrating post objects nobody is going to render.
        $query = new \WP_Query([
            ...$this->argumentsFor($postTypeKey, $filters),
            'posts_per_page' => 1,
            'offset' => 0,
            'fields' => 'ids',
            'no_found_rows' => false,
        ]);

        return (int) $query->found_posts;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function argumentsFor(string $postTypeKey, array $filters): array
    {
        $perPage = is_numeric($filters['perPage'] ?? null) ? (int) $filters['perPage'] : 24;
        $metaKey = is_string($filters['metaKey'] ?? null) ? $filters['metaKey'] : '';

        $arguments = [
            'post_type' => $postTypeKey,
            'post_status' => 'publish',
            // Clamped again here rather than trusted. This adapter is one call away from a
            // public endpoint, and the clamp costs nothing.
            'posts_per_page' => min(self::MAXIMUM_PER_PAGE, max(1, $perPage)),
            'offset' => is_numeric($filters['offset'] ?? null) ? max(0, (int) $filters['offset']) : 0,
            'ignore_sticky_posts' => true,
            'order' => ($filters['descending'] ?? true) ? 'DESC' : 'ASC',
        ];

        if (is_string($filters['search'] ?? null) && $filters['search'] !== '') {
            $arguments['s'] = $filters['search'];
        }

        if ($metaKey !== '') {
            $arguments['meta_key'] = $metaKey;
            $arguments['orderby'] = 'meta_value_num';
        }

        $taxQuery = $this->taxQueryFor($filters);

        if ($taxQuery !== []) {
            $arguments['tax_query'] = $taxQuery;
        }

        return $arguments;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<array<string, mixed>>
     */
    private function taxQueryFor(array $filters): array
    {
        $selections = is_array($filters['selections'] ?? null) ? $filters['selections'] : [];
        $clauses = [];

        foreach ($selections as $facet => $values) {
            if (!is_string($facet) || !is_array($values) || $values === []) {
                continue;
            }

            $clauses[] = [
                'taxonomy' => 'edulume_' . $facet,
                'field' => 'slug',
                'terms' => array_values(array_filter($values, 'is_string')),
            ];
        }

        return $clauses;
    }

    /**
     * @return array<string, mixed>
     */
    private function rowFor(\WP_Post $post): array
    {
        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'slug' => $post->post_name,
            'url' => (string) get_permalink($post),
            'excerpt' => get_the_excerpt($post),
            'thumbnail' => (string) get_the_post_thumbnail_url($post, 'medium_large'),
        ];
    }
}
