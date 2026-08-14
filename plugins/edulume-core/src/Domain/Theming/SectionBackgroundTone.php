<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * Which surface a section sits on, expressed as a role rather than a colour so it stays
 * correct in both modes.
 */
enum SectionBackgroundTone: string
{
    case Surface = 'surface';
    case Subtle = 'subtle';
    case Inverted = 'inverted';
    case Accent = 'accent';
}
