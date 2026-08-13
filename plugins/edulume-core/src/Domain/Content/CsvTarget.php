<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * Where a CSV column lands: a post field, a meta key, a taxonomy, or nowhere.
 */
enum CsvTarget: string
{
    case Title = 'title';
    case Content = 'content';
    case Excerpt = 'excerpt';
    case Slug = 'slug';
    case Status = 'status';
    case Meta = 'meta';
    case Taxonomy = 'taxonomy';
    case Relationship = 'relationship';
    case Ignore = 'ignore';

    public function needsKey(): bool
    {
        return $this === self::Meta || $this === self::Taxonomy || $this === self::Relationship;
    }
}
