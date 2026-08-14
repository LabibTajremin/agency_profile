<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use InvalidArgumentException;

final class InvalidPaletteException extends InvalidArgumentException
{
    public static function forStepCount(int $given, int $expected): self
    {
        return new self(sprintf('A palette needs exactly %d steps; %d were given.', $expected, $given));
    }

    public static function forStepIndex(int $index, int $stepCount): self
    {
        return new self(sprintf('Step %d does not exist; the palette has %d steps.', $index, $stepCount));
    }
}
