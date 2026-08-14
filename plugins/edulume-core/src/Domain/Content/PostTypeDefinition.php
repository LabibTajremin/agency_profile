<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * A post type as a value object, with no WordPress in sight.
 *
 * Keeping the definitions here means the content model can be asserted — every type has a
 * plural label, a rewrite base, a menu icon — without booting WordPress to find out.
 */
final class PostTypeDefinition
{
    public const KEY_PREFIX = 'edulume_';

    private function __construct(
        public readonly string $key,
        public readonly string $singularLabel,
        public readonly string $pluralLabel,
        public readonly string $rewriteBase,
        public readonly string $menuIcon,
        public readonly bool $isPubliclyQueryable,
        public readonly bool $supportsExcerpt,
        public readonly bool $supportsAccentOverride,
        /** @var list<string> */
        public readonly array $taxonomyKeys,
    ) {
    }

    /**
     * @param list<string> $taxonomyKeys
     */
    public static function of(
        string $key,
        string $singularLabel,
        string $pluralLabel,
        string $rewriteBase,
        string $menuIcon,
        array $taxonomyKeys = [],
        bool $isPubliclyQueryable = true,
        bool $supportsExcerpt = true,
        bool $supportsAccentOverride = true
    ): self {
        return new self(
            self::KEY_PREFIX . $key,
            $singularLabel,
            $pluralLabel,
            $rewriteBase,
            $menuIcon,
            $isPubliclyQueryable,
            $supportsExcerpt,
            $supportsAccentOverride,
            $taxonomyKeys,
        );
    }

    public function hasTaxonomy(string $taxonomyKey): bool
    {
        return in_array($taxonomyKey, $this->taxonomyKeys, true);
    }

    /**
     * @return list<string>
     */
    public function supports(): array
    {
        $supports = ['title', 'editor', 'thumbnail', 'revisions', 'custom-fields'];

        if ($this->supportsExcerpt) {
            $supports[] = 'excerpt';
        }

        return $supports;
    }

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return [
            'name' => $this->pluralLabel,
            'singular_name' => $this->singularLabel,
            'add_new_item' => sprintf('Add %s', $this->singularLabel),
            'edit_item' => sprintf('Edit %s', $this->singularLabel),
            'new_item' => sprintf('New %s', $this->singularLabel),
            'view_item' => sprintf('View %s', $this->singularLabel),
            'view_items' => sprintf('View %s', $this->pluralLabel),
            'search_items' => sprintf('Search %s', $this->pluralLabel),
            'not_found' => sprintf('No %s found', strtolower($this->pluralLabel)),
            'not_found_in_trash' => sprintf('No %s found in Trash', strtolower($this->pluralLabel)),
            'all_items' => sprintf('All %s', $this->pluralLabel),
            'archives' => sprintf('%s Archives', $this->singularLabel),
            'menu_name' => $this->pluralLabel,
        ];
    }
}
