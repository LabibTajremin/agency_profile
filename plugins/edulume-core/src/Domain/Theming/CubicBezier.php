<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * A validated CSS `cubic-bezier()` timing function.
 *
 * The x control points must lie between 0 and 1 — CSS requires it, and a browser drops the
 * whole declaration when they do not, which silently removes the transition rather than
 * making it odd. The y points may overshoot, which is what produces a spring.
 */
final class CubicBezier
{
    private const OUTPUT_PRECISION = 4;

    private function __construct(
        public readonly float $firstControlPointX,
        public readonly float $firstControlPointY,
        public readonly float $secondControlPointX,
        public readonly float $secondControlPointY,
    ) {
    }

    /**
     * @throws InvalidMotionException when a control point is not finite or an x point is
     *                                outside the 0–1 range CSS requires
     */
    public static function of(
        float $firstControlPointX,
        float $firstControlPointY,
        float $secondControlPointX,
        float $secondControlPointY
    ): self {
        self::guardFinite('x1', $firstControlPointX);
        self::guardFinite('y1', $firstControlPointY);
        self::guardFinite('x2', $secondControlPointX);
        self::guardFinite('y2', $secondControlPointY);

        self::guardHorizontalRange('x1', $firstControlPointX);
        self::guardHorizontalRange('x2', $secondControlPointX);

        return new self($firstControlPointX, $firstControlPointY, $secondControlPointX, $secondControlPointY);
    }

    public static function isValid(float $x1, float $y1, float $x2, float $y2): bool
    {
        return is_finite($x1) && is_finite($y1) && is_finite($x2) && is_finite($y2)
            && $x1 >= 0.0 && $x1 <= 1.0
            && $x2 >= 0.0 && $x2 <= 1.0;
    }

    public function toCssValue(): string
    {
        return sprintf(
            'cubic-bezier(%s, %s, %s, %s)',
            self::format($this->firstControlPointX),
            self::format($this->firstControlPointY),
            self::format($this->secondControlPointX),
            self::format($this->secondControlPointY),
        );
    }

    private static function guardFinite(string $axis, float $value): void
    {
        if (!is_finite($value)) {
            throw InvalidMotionException::forNonFiniteControlPoint($axis);
        }
    }

    private static function guardHorizontalRange(string $axis, float $value): void
    {
        if ($value < 0.0 || $value > 1.0) {
            throw InvalidMotionException::forControlPointOutsideRange($axis, $value);
        }
    }

    private static function format(float $value): string
    {
        $formatted = number_format($value, self::OUTPUT_PRECISION, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
