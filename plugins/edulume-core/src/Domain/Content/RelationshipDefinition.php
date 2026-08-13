<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * A real post-to-post relationship, stored as post IDs in meta rather than as free text.
 *
 * Free text is why so many consultancy sites list "University of Toronto", "Toronto Uni" and
 * "U of T" as three different institutions. An ID resolves in both directions and survives a
 * rename.
 */
final class RelationshipDefinition
{
    private function __construct(
        public readonly string $key,
        public readonly string $fromPostTypeKey,
        public readonly string $toPostTypeKey,
        public readonly string $fromLabel,
        public readonly string $toLabel,
        public readonly bool $allowsMany,
    ) {
    }

    public static function of(
        string $key,
        string $fromPostTypeKey,
        string $toPostTypeKey,
        string $fromLabel,
        string $toLabel,
        bool $allowsMany = false
    ): self {
        return new self($key, $fromPostTypeKey, $toPostTypeKey, $fromLabel, $toLabel, $allowsMany);
    }

    public function metaKey(): string
    {
        return '_edulume_rel_' . $this->key;
    }

    public function connects(string $postTypeKey): bool
    {
        return $this->fromPostTypeKey === $postTypeKey || $this->toPostTypeKey === $postTypeKey;
    }

    public function otherEnd(string $postTypeKey): string
    {
        return $this->fromPostTypeKey === $postTypeKey ? $this->toPostTypeKey : $this->fromPostTypeKey;
    }
}
