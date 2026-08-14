<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * Whether the pattern scrolls with the page or stays fixed behind it.
 *
 * Fixed attachment is a known repaint cost on mobile Safari, which is why scrolling is the
 * default rather than the more dramatic option.
 */
enum PatternAttachment: string
{
    case Scroll = 'scroll';
    case Fixed = 'fixed';

    public function toCssValue(): string
    {
        return $this->value;
    }
}
