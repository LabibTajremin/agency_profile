<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Chrome;

/**
 * The header arrangements.
 *
 * A variant changes where things sit, never what they are: the same menu, the same logo and the
 * same call to action carry across all six. That is the difference between a variant switch and
 * a rebuild, and it is why switching one never loses configuration.
 */
enum HeaderVariant: string
{
    case Classic = 'classic';
    case Centred = 'centred';
    case Split = 'split';
    case Stacked = 'stacked';
    case Minimal = 'minimal';
    case TransparentHero = 'transparent-hero';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Classic',
            self::Centred => 'Centred',
            self::Split => 'Split',
            self::Stacked => 'Stacked',
            self::Minimal => 'Minimal',
            self::TransparentHero => 'Transparent over hero',
        };
    }

    /** The template part the theme renders for this variant. */
    public function templatePart(): string
    {
        return 'template-parts/header/' . $this->value;
    }

    /**
     * Whether the variant needs the header to start transparent.
     *
     * Only meaningful where a full-bleed hero follows; the theme falls back to the solid
     * treatment everywhere else rather than leaving white text on a white page.
     */
    public function isTransparent(): bool
    {
        return $this === self::TransparentHero;
    }

    /**
     * Whether the logo sits above the navigation rather than beside it.
     *
     * Two-row headers cost roughly 48px of vertical space, which matters on a phone: the sticky
     * treatment shrinks to a single row on scroll for exactly these two.
     */
    public function isTwoRow(): bool
    {
        return $this === self::Centred || $this === self::Stacked;
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }
}
