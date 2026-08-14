<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Blocks;

/**
 * How the blocks are grouped in the inserter.
 *
 * Five categories rather than one flat list of thirty-six: an inserter panel that needs
 * scrolling to reach the block you use most is an inserter people stop opening.
 */
enum BlockGroup: string
{
    case Layout = 'layout';
    case Content = 'content';
    case Data = 'data';
    case Media = 'media';
    case Conversion = 'conversion';

    public function label(): string
    {
        return match ($this) {
            self::Layout => 'Edulume Layout',
            self::Content => 'Edulume Content',
            self::Data => 'Edulume Data',
            self::Media => 'Edulume Media',
            self::Conversion => 'Edulume Conversion',
        };
    }

    /** The block category slug registered with WordPress. */
    public function categorySlug(): string
    {
        return 'edulume-' . $this->value;
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }
}
