<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * A modular type scale emitted as fluid `clamp()` values in rem.
 *
 * The mobile end uses a damped ratio. A 1.5 ratio that reads as confident on a desktop
 * headline is a wall of text on a 360px phone, so the small end of every step is computed
 * from a gentler ratio and the two are interpolated across the viewport.
 *
 * Sizes are rem, never px, so a visitor who raises their browser's base font size gets a
 * larger site rather than an unchanged one.
 */
final class TypeScale
{
    public const SMALLEST_STEP = -2;
    public const LARGEST_STEP = 6;

    public const MINIMUM_RATIO = 1.05;
    public const MAXIMUM_RATIO = 1.8;
    public const DEFAULT_RATIO = 1.25;

    public const MINIMUM_BASE_REM = 0.875;
    public const MAXIMUM_BASE_REM = 1.375;
    public const DEFAULT_BASE_REM = 1.0;

    private const MOBILE_RATIO_DAMPING = 0.6;
    private const SMALLEST_VIEWPORT_REM = 20.0;
    private const LARGEST_VIEWPORT_REM = 80.0;
    private const VIEWPORT_UNITS_PER_REM = 100.0;
    private const OUTPUT_PRECISION = 4;

    private function __construct(
        public readonly float $ratio,
        public readonly float $baseSizeRem,
    ) {
    }

    public static function of(float $ratio, float $baseSizeRem = self::DEFAULT_BASE_REM): self
    {
        return new self(
            max(self::MINIMUM_RATIO, min(self::MAXIMUM_RATIO, $ratio)),
            max(self::MINIMUM_BASE_REM, min(self::MAXIMUM_BASE_REM, $baseSizeRem)),
        );
    }

    /**
     * The ratio used at the narrow end of the viewport: always closer to 1 than the desktop
     * ratio, so headings stay in proportion on a phone.
     */
    public function mobileRatio(): float
    {
        return 1.0 + (($this->ratio - 1.0) * self::MOBILE_RATIO_DAMPING);
    }

    public function minimumRemAt(int $step): float
    {
        return $this->baseSizeRem * ($this->mobileRatio() ** $step);
    }

    public function maximumRemAt(int $step): float
    {
        return $this->baseSizeRem * ($this->ratio ** $step);
    }

    /**
     * A CSS `clamp()` that interpolates between the narrow-viewport size and the wide-viewport
     * size. Below the base step the wide size is the smaller of the two, so the bounds are
     * ordered rather than assumed — an inverted `clamp()` silently pins to one end.
     */
    public function clampAt(int $step): string
    {
        $narrowViewportRem = $this->minimumRemAt($step);
        $wideViewportRem = $this->maximumRemAt($step);

        if ($narrowViewportRem === $wideViewportRem) {
            return self::formatRem($narrowViewportRem);
        }

        $slope = ($wideViewportRem - $narrowViewportRem) / (self::LARGEST_VIEWPORT_REM - self::SMALLEST_VIEWPORT_REM);
        $intercept = $narrowViewportRem - ($slope * self::SMALLEST_VIEWPORT_REM);
        $viewportTerm = $slope * self::VIEWPORT_UNITS_PER_REM;

        return sprintf(
            'clamp(%s, %s %s %s, %s)',
            self::formatRem(min($narrowViewportRem, $wideViewportRem)),
            self::formatRem($intercept),
            $viewportTerm < 0.0 ? '-' : '+',
            self::formatViewportWidth(abs($viewportTerm)),
            self::formatRem(max($narrowViewportRem, $wideViewportRem)),
        );
    }

    /**
     * @return array<int, string>
     */
    public function toClampMap(): array
    {
        $sizes = [];

        for ($step = self::SMALLEST_STEP; $step <= self::LARGEST_STEP; $step++) {
            $sizes[$step] = $this->clampAt($step);
        }

        return $sizes;
    }

    private static function formatRem(float $value): string
    {
        return self::trimZeroes($value) . 'rem';
    }

    private static function formatViewportWidth(float $value): string
    {
        return self::trimZeroes($value) . 'vw';
    }

    private static function trimZeroes(float $value): string
    {
        $formatted = number_format($value, self::OUTPUT_PRECISION, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
