<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Finder;

/**
 * Where a facet's values live.
 *
 * The distinction is not cosmetic: a taxonomy facet becomes a `tax_query` against an indexed
 * term relationship, while a meta facet becomes a `meta_query` against a key that has to be
 * registered and indexed first. Making the source explicit is what stops the second kind being
 * added by accident.
 */
enum FacetSource: string
{
    case Taxonomy = 'taxonomy';
    case Meta = 'meta';
    case PostField = 'post-field';
}
