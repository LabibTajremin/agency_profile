<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Finder;

/**
 * The sort orders a finder offers.
 *
 * Every one maps to an indexed column or an indexed meta key. A sort that needs a filesort over
 * three thousand rows is a sort that turns a 200 ms finder into a 2 s one.
 */
enum FinderSort: string
{
    case Relevance = 'relevance';
    case TitleAsc = 'title-asc';
    case TitleDesc = 'title-desc';
    case Newest = 'newest';
    case DeadlineSoonest = 'deadline-soonest';
    case TuitionLowest = 'tuition-lowest';
    case TuitionHighest = 'tuition-highest';

    public function label(): string
    {
        return match ($this) {
            self::Relevance => 'Most relevant',
            self::TitleAsc => 'A to Z',
            self::TitleDesc => 'Z to A',
            self::Newest => 'Newest first',
            self::DeadlineSoonest => 'Deadline soonest',
            self::TuitionLowest => 'Tuition: low to high',
            self::TuitionHighest => 'Tuition: high to low',
        };
    }

    /**
     * The meta key this sort orders by, or null when it orders by a post column.
     */
    public function metaKey(): ?string
    {
        return match ($this) {
            self::DeadlineSoonest => 'edulume_deadline',
            self::TuitionLowest, self::TuitionHighest => 'edulume_tuition_amount',
            default => null,
        };
    }

    public function isDescending(): bool
    {
        return $this === self::TitleDesc || $this === self::Newest || $this === self::TuitionHighest;
    }

    /**
     * The sorts that make sense for a given finder.
     *
     * A deadline sort on the course finder would order every row by a key most courses do not
     * have, which silently drops them.
     *
     * @return list<self>
     */
    public static function forPostType(string $postType): array
    {
        $shared = [self::Relevance, self::TitleAsc, self::TitleDesc, self::Newest];

        return match ($postType) {
            'edulume_course' => [...$shared, self::TuitionLowest, self::TuitionHighest],
            'edulume_scholarship' => [...$shared, self::DeadlineSoonest],
            default => $shared,
        };
    }
}
