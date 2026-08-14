<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Chrome;

/**
 * The footer arrangements.
 *
 * Column count is a property of the variant rather than a separate setting, because the two are
 * not independent: a "compact" footer with five columns is neither compact nor a footer.
 */
enum FooterVariant: string
{
    case Columns = 'columns';
    case Compact = 'compact';
    case Centred = 'centred';
    case ContactFirst = 'contact-first';

    public function label(): string
    {
        return match ($this) {
            self::Columns => 'Columns',
            self::Compact => 'Compact',
            self::Centred => 'Centred',
            self::ContactFirst => 'Contact first',
        };
    }

    public function templatePart(): string
    {
        return 'template-parts/footer/' . $this->value;
    }

    /** How many widget areas the variant exposes. */
    public function columnCount(): int
    {
        return match ($this) {
            self::Columns => 4,
            self::ContactFirst => 3,
            self::Compact => 2,
            self::Centred => 1,
        };
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }
}
