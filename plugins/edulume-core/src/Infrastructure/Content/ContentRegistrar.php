<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Infrastructure\Admin\AdminMenu;
use Edulume\Core\Domain\Content\PostTypeDefinition;
use Edulume\Core\Domain\Content\RelationshipDefinition;
use Edulume\Core\Domain\Content\TaxonomyDefinition;

/**
 * Registers the content model with WordPress.
 *
 * Rewrite bases are filterable so a site owner can localise `/courses/` to `/kurse/` without
 * editing code, and every relationship is registered as typed post meta so it appears in REST
 * and in the block editor rather than only in a meta box.
 */
final class ContentRegistrar
{
    public const REWRITE_BASE_FILTER = 'edulume_rewrite_base';

    public function register(): void
    {
        add_action('init', [$this, 'registerTaxonomies'], 5);
        add_action('init', [$this, 'registerPostTypes'], 10);
        add_action('init', [$this, 'registerRelationships'], 15);
        add_action('init', [$this, 'registerAccentOverrides'], 20);
    }

    public function registerTaxonomies(): void
    {
        foreach (ContentModel::taxonomies() as $taxonomy) {
            register_taxonomy($taxonomy->key, $this->postTypesFor($taxonomy), [
                'labels' => $taxonomy->labels(),
                'hierarchical' => $taxonomy->isHierarchical,
                'public' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => ['slug' => $this->rewriteBase($taxonomy->key, $taxonomy->rewriteBase)],
            ]);
        }
    }

    public function registerPostTypes(): void
    {
        foreach (ContentModel::postTypes() as $postType) {
            register_post_type($postType->key, [
                'labels' => $postType->labels(),
                'public' => true,
                'publicly_queryable' => $postType->isPubliclyQueryable,
                'show_in_rest' => true,
                'has_archive' => true,
                /*
                 * Nested under the one Edulume menu instead of planting sixteen top-level
                 * entries in the sidebar.
                 *
                 * Sixteen post types each claiming their own top-level item buries WordPress's
                 * own menu below the fold and makes the product look like sixteen plugins. It
                 * is also the single most common thing a non-technical site owner gets lost in:
                 * everything to do with the site should be in one place, and now it is.
                 */
                'show_in_menu' => AdminMenu::SLUG,
                'menu_icon' => $postType->menuIcon,
                'supports' => $postType->supports(),
                'taxonomies' => $postType->taxonomyKeys,
                'capability_type' => 'post',
                'rewrite' => [
                    'slug' => $this->rewriteBase($postType->key, $postType->rewriteBase),
                    'with_front' => false,
                ],
            ]);
        }
    }

    public function registerRelationships(): void
    {
        foreach (ContentModel::relationships() as $relationship) {
            register_post_meta($relationship->fromPostTypeKey, $relationship->metaKey(), [
                'type' => 'integer',
                'single' => !$relationship->allowsMany,
                'show_in_rest' => true,
                'sanitize_callback' => 'absint',
                'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
            ]);
        }
    }

    public function registerAccentOverrides(): void
    {
        foreach (ContentModel::postTypes() as $postType) {
            if (!$postType->supportsAccentOverride) {
                continue;
            }

            register_post_meta($postType->key, PostTypeAccentOverride::META_KEY, [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => [PostTypeAccentOverride::class, 'sanitize'],
                'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function postTypesFor(TaxonomyDefinition $taxonomy): array
    {
        $postTypes = [];

        foreach (ContentModel::postTypes() as $postType) {
            if ($postType->hasTaxonomy($taxonomy->key)) {
                $postTypes[] = $postType->key;
            }
        }

        return $postTypes;
    }

    private function rewriteBase(string $key, string $default): string
    {
        $base = apply_filters(self::REWRITE_BASE_FILTER, $default, $key);

        return is_string($base) && trim($base) !== '' ? trim($base, '/') : $default;
    }
}
