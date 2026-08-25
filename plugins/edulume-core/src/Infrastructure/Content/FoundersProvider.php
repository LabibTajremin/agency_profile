<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Support\Guard;

/**
 * The founders, from the team post type.
 *
 * The same `edulume_team-member` posts that feed the counsellors section, filtered by a role
 * meta. A second people post type would mean a counsellor who becomes a partner has to be
 * retyped rather than reclassified, and the site would carry two half-populated staff lists.
 *
 * Returns an empty list when nobody is marked as a founder, which is what lets the template fall
 * through to the bundled two — the page is complete on a new install and becomes theirs the
 * moment they mark one person.
 */
final class FoundersProvider
{
    public const FILTER = 'edulume_founders';
    public const ROLE_META = '_edulume_role_type';
    public const ROLE_FOUNDER = 'founder';

    public function register(): void
    {
        add_action('init', [$this, 'registerMeta'], 16);
        add_filter(self::FILTER, [$this, 'founders'], 10, 1);
    }

    public function registerMeta(): void
    {
        $postType = ContentModel::postTypeKey('team-member');

        register_post_meta($postType, self::ROLE_META, [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'default' => 'counsellor',
            'sanitize_callback' => 'sanitize_key',
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ]);

        foreach (['_edulume_designation', '_edulume_tagline', '_edulume_expertise', '_edulume_languages'] as $key) {
            register_post_meta($postType, $key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
            ]);
        }

        // The repeaters and the social links are objects, so they stay out of REST: a write
        // endpoint that accepts a free-form array without a schema accepts anything.
        foreach (['_edulume_education', '_edulume_experience', '_edulume_socials'] as $key) {
            register_post_meta($postType, $key, [
                'type' => 'object',
                'single' => true,
                'show_in_rest' => false,
                'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
            ]);
        }
    }

    /**
     * @param list<array<string, mixed>> $fallback
     *
     * @return list<array<string, mixed>>
     */
    public function founders(array $fallback): array
    {
        $posts = get_posts([
            'post_type' => ContentModel::postTypeKey('team-member'),
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'no_found_rows' => true,
            'meta_key' => self::ROLE_META,
            'meta_value' => self::ROLE_FOUNDER,
        ]);

        if (!is_array($posts) || $posts === []) {
            return $fallback;
        }

        $founders = [];

        foreach ($posts as $post) {
            if ($post instanceof \WP_Post) {
                $founders[] = $this->founder($post);
            }
        }

        return $founders;
    }

    /**
     * @return array<string, mixed>
     */
    private function founder(\WP_Post $post): array
    {
        return [
            'name' => get_the_title($post),
            'designation' => (string) get_post_meta($post->ID, '_edulume_designation', true),
            'tagline' => (string) get_post_meta($post->ID, '_edulume_tagline', true),
            'expertise' => (string) get_post_meta($post->ID, '_edulume_expertise', true),
            'languages' => (string) get_post_meta($post->ID, '_edulume_languages', true),
            'bio' => wp_strip_all_tags($post->post_content),
            'photoUrl' => (string) get_the_post_thumbnail_url($post, 'large'),
            'education' => $this->rows($post->ID, '_edulume_education'),
            'experience' => $this->rows($post->ID, '_edulume_experience'),
            'socials' => $this->socials($post->ID),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(int $postId, string $key): array
    {
        $stored = Guard::toArray(get_post_meta($postId, $key, true));
        $rows = [];

        foreach ($stored as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function socials(int $postId): array
    {
        $stored = Guard::toArray(get_post_meta($postId, '_edulume_socials', true));
        $socials = [];

        foreach ($stored as $network => $url) {
            if (is_string($url) && $url !== '') {
                $socials[(string) $network] = $url;
            }
        }

        return $socials;
    }
}
