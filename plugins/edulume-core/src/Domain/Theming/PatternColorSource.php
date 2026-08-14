<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * Where a pattern takes its tint from.
 */
enum PatternColorSource: string
{
    case Accent = 'accent';
    case Neutral = 'neutral';
    case Custom = 'custom';
}
