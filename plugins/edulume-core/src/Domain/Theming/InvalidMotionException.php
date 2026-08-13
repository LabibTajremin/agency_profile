<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use InvalidArgumentException;

final class InvalidMotionException extends InvalidArgumentException
{
    public static function forControlPointOutsideRange(string $axis, float $value): self
    {
        return new self(sprintf(
            'A cubic-bezier %s control point must lie between 0 and 1; got %.4F.',
            $axis,
            $value
        ));
    }

    public static function forNonFiniteControlPoint(string $axis): self
    {
        return new self(sprintf('A cubic-bezier %s control point must be a finite number.', $axis));
    }
}
