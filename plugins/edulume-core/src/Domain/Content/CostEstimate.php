<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

/**
 * What one year abroad is likely to cost, broken down so a family can see where it goes.
 */
final class CostEstimate
{
    private function __construct(
        public readonly int $tuition,
        public readonly int $living,
        public readonly int $visa,
        public readonly int $flights,
        public readonly int $insurance,
        public readonly string $currency,
    ) {
    }

    public static function of(
        int $tuition,
        int $living,
        int $visa,
        int $flights,
        int $insurance,
        string $currency
    ): self {
        return new self(
            max(0, $tuition),
            max(0, $living),
            max(0, $visa),
            max(0, $flights),
            max(0, $insurance),
            strtoupper(trim($currency)) ?: ExchangeRateTable::BASE_CURRENCY,
        );
    }

    public function total(): int
    {
        return $this->tuition + $this->living + $this->visa + $this->flights + $this->insurance;
    }

    /**
     * @return array<string, int>
     */
    public function breakdown(): array
    {
        return [
            'tuition' => $this->tuition,
            'living' => $this->living,
            'visa' => $this->visa,
            'flights' => $this->flights,
            'insurance' => $this->insurance,
        ];
    }

    /**
     * Each line as a whole-number percentage of the total, summing to 100 by giving the
     * remainder to the largest line — otherwise a pie chart shows 99% and someone files a bug.
     *
     * @return array<string, int>
     */
    public function percentages(): array
    {
        $total = $this->total();

        if ($total === 0) {
            return array_map(static fn (): int => 0, $this->breakdown());
        }

        $percentages = [];

        foreach ($this->breakdown() as $line => $amount) {
            $percentages[$line] = (int) floor(($amount / $total) * 100);
        }

        $largest = array_search(max($this->breakdown()), $this->breakdown(), true);

        if (is_string($largest)) {
            $percentages[$largest] += 100 - array_sum($percentages);
        }

        return $percentages;
    }
}
