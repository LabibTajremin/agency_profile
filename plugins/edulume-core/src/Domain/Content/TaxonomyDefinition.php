<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * A taxonomy as a value object.
 */
final class TaxonomyDefinition
{
    public const KEY_PREFIX = 'edulume_';

    private function __construct(
        public readonly string $key,
        public readonly string $singularLabel,
        public readonly string $pluralLabel,
        public readonly string $rewriteBase,
        public readonly bool $isHierarchical,
    ) {
    }

    public static function of(
        string $key,
        string $singularLabel,
        string $pluralLabel,
        string $rewriteBase,
        bool $isHierarchical = true
    ): self {
        return new self(self::KEY_PREFIX . $key, $singularLabel, $pluralLabel, $rewriteBase, $isHierarchical);
    }

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return [
            'name' => $this->pluralLabel,
            'singular_name' => $this->singularLabel,
            'search_items' => sprintf('Search %s', $this->pluralLabel),
            'all_items' => sprintf('All %s', $this->pluralLabel),
            'edit_item' => sprintf('Edit %s', $this->singularLabel),
            'update_item' => sprintf('Update %s', $this->singularLabel),
            'add_new_item' => sprintf('Add %s', $this->singularLabel),
            'new_item_name' => sprintf('New %s Name', $this->singularLabel),
            'menu_name' => $this->pluralLabel,
        ];
    }
}
