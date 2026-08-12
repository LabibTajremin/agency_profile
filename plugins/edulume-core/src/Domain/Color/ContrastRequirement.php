<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Color;

/**
 * WCAG 2.x contrast thresholds.
 *
 * Large text is 18.66px bold or 24px regular and upwards. Non-text covers UI component
 * boundaries, focus rings and meaningful graphical objects (WCAG 1.4.11).
 */
enum ContrastRequirement: string
{
    case NormalTextAa = 'normal-text-aa';
    case LargeTextAa = 'large-text-aa';
    case NonText = 'non-text';
    case NormalTextAaa = 'normal-text-aaa';
    case LargeTextAaa = 'large-text-aaa';

    private const THRESHOLD_NORMAL_TEXT_AA = 4.5;
    private const THRESHOLD_LARGE_TEXT_AA = 3.0;
    private const THRESHOLD_NON_TEXT = 3.0;
    private const THRESHOLD_NORMAL_TEXT_AAA = 7.0;
    private const THRESHOLD_LARGE_TEXT_AAA = 4.5;

    public function threshold(): float
    {
        return match ($this) {
            self::NormalTextAa => self::THRESHOLD_NORMAL_TEXT_AA,
            self::LargeTextAa => self::THRESHOLD_LARGE_TEXT_AA,
            self::NonText => self::THRESHOLD_NON_TEXT,
            self::NormalTextAaa => self::THRESHOLD_NORMAL_TEXT_AAA,
            self::LargeTextAaa => self::THRESHOLD_LARGE_TEXT_AAA,
        };
    }

    public function isSatisfiedBy(float $ratio): bool
    {
        return $ratio >= $this->threshold();
    }
}
