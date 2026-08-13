<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The six typographic roles the product resolves a family for.
 *
 * A pairing sets heading and body; the other roles inherit from one of those two unless the
 * user overrides them explicitly.
 */
enum FontRole: string
{
    case Display = 'display';
    case Heading = 'heading';
    case Body = 'body';
    case Interface = 'interface';
    case Quote = 'quote';
    case Code = 'code';

    /**
     * Which of the pairing's two families this role follows when nothing overrides it.
     */
    public function inheritsFrom(): self
    {
        return match ($this) {
            self::Display, self::Heading => self::Heading,
            self::Body, self::Interface, self::Quote, self::Code => self::Body,
        };
    }
}
