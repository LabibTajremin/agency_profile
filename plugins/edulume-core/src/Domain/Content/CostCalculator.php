<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * Turns a destination's costs into an estimate in whatever currency the visitor reads in.
 *
 * Everything is stored in the base currency and converted once, at the end. Converting each
 * line separately and adding them up produces a total that does not match the lines, which is
 * the sort of thing a parent notices immediately.
 */
final class CostCalculator
{
    public const MINIMUM_MONTHS = 1;
    public const MAXIMUM_MONTHS = 60;

    private const MONTHS_PER_YEAR = 12;

    public function __construct(private readonly ExchangeRateTable $exchangeRates)
    {
    }

    public function estimate(
        int $annualTuitionInBase,
        int $monthlyLivingInBase,
        int $visaFeeInBase,
        int $returnFlightInBase,
        int $annualInsuranceInBase,
        int $months,
        string $currency
    ): CostEstimate {
        $duration = max(self::MINIMUM_MONTHS, min(self::MAXIMUM_MONTHS, $months));
        $years = $duration / self::MONTHS_PER_YEAR;

        return CostEstimate::of(
            $this->exchangeRates->convert((int) round(max(0, $annualTuitionInBase) * $years), $currency),
            $this->exchangeRates->convert(max(0, $monthlyLivingInBase) * $duration, $currency),
            $this->exchangeRates->convert(max(0, $visaFeeInBase), $currency),
            $this->exchangeRates->convert(max(0, $returnFlightInBase) * (int) ceil($years), $currency),
            $this->exchangeRates->convert((int) round(max(0, $annualInsuranceInBase) * $years), $currency),
            $currency,
        );
    }
}
