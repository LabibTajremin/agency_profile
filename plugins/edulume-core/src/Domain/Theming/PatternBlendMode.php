<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The CSS blend mode a pattern layer is composited with.
 */
enum PatternBlendMode: string
{
    case Normal = 'normal';
    case Multiply = 'multiply';
    case Screen = 'screen';
    case Overlay = 'overlay';
    case SoftLight = 'soft-light';

    public function toCssValue(): string
    {
        return $this->value;
    }
}
