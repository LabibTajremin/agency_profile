<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Performance;

/**
 * The performance budget, as numbers rather than as an intention.
 *
 * A budget nobody measures is a wish. These are the figures the Lighthouse CI job asserts on
 * every pull request, on a throttled connection — which is the only kind of measurement that
 * predicts what a visitor on a phone in Dhaka actually experiences.
 */
final class PerformanceBudget
{
    /** Largest Contentful Paint, in milliseconds. */
    public const LCP_MS = 2000;

    /** Cumulative Layout Shift, unitless. */
    public const CLS = 0.05;

    /** Interaction to Next Paint, in milliseconds. */
    public const INP_MS = 200;

    /** Transferred bytes per resource class. */
    public const JS_BYTES = 120 * 1024;
    public const CSS_BYTES = 60 * 1024;
    public const FONT_BYTES = 100 * 1024;

    public const REQUESTS = 45;

    /** The mobile Lighthouse performance score, out of 100. */
    public const SCORE = 90;

    /**
     * @return array<string, int|float>
     */
    public static function thresholds(): array
    {
        return [
            'score' => self::SCORE,
            'lcp-ms' => self::LCP_MS,
            'cls' => self::CLS,
            'inp-ms' => self::INP_MS,
            'js-bytes' => self::JS_BYTES,
            'css-bytes' => self::CSS_BYTES,
            'font-bytes' => self::FONT_BYTES,
            'requests' => self::REQUESTS,
        ];
    }

    /**
     * Whether a metric is one where more is better.
     *
     * Only the score is. Everything else is a cost, and treating them uniformly is how a budget
     * check ends up passing a page that got twice as slow.
     */
    public static function isHigherBetter(string $metric): bool
    {
        return $metric === 'score';
    }

    /**
     * @param array<string, int|float> $measurements
     */
    public static function evaluate(array $measurements): BudgetReport
    {
        $breaches = [];

        foreach (self::thresholds() as $metric => $threshold) {
            if (!array_key_exists($metric, $measurements)) {
                // A metric the run did not produce is a failed measurement, not a pass. Silent
                // omission is exactly how a budget stops catching anything.
                $breaches[] = new BudgetBreach($metric, null, $threshold);

                continue;
            }

            $measured = $measurements[$metric];
            $isBreach = self::isHigherBetter($metric)
                ? $measured < $threshold
                : $measured > $threshold;

            if ($isBreach) {
                $breaches[] = new BudgetBreach($metric, $measured, $threshold);
            }
        }

        return new BudgetReport($breaches);
    }
}
