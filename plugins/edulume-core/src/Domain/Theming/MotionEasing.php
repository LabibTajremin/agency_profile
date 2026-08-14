<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The six named curves offered before anyone opens a bezier editor.
 */
enum MotionEasing: string
{
    case Linear = 'linear';
    case Standard = 'standard';
    case Decelerate = 'decelerate';
    case Accelerate = 'accelerate';
    case Emphasized = 'emphasized';
    case Spring = 'spring';

    /**
     * @return array{float, float, float, float}
     */
    public function controlPoints(): array
    {
        return match ($this) {
            self::Linear => [0.0, 0.0, 1.0, 1.0],
            self::Standard => [0.4, 0.0, 0.2, 1.0],
            self::Decelerate => [0.0, 0.0, 0.2, 1.0],
            self::Accelerate => [0.4, 0.0, 1.0, 1.0],
            self::Emphasized => [0.2, 0.0, 0.0, 1.0],
            self::Spring => [0.34, 1.56, 0.64, 1.0],
        };
    }

    public function toCubicBezier(): CubicBezier
    {
        [$x1, $y1, $x2, $y2] = $this->controlPoints();

        return CubicBezier::of($x1, $y1, $x2, $y2);
    }
}
