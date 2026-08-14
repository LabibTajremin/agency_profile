<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

use Edulume\Core\Domain\Support\Guard;

/**
 * Admin-editable static exchange rates.
 *
 * Deliberately not a live API. A paid dependency that expires, rate-limits or goes down takes
 * the cost calculator with it, and for an indicative estimate a rate the consultancy updates
 * each term is both accurate enough and something they can be held to.
 */
final class ExchangeRateTable
{
    public const BASE_CURRENCY = 'USD';

    /**
     * @param array<string, float> $ratesPerBaseUnit
     */
    private function __construct(private readonly array $ratesPerBaseUnit)
    {
    }

    /**
     * @param array<string, float> $ratesPerBaseUnit how many units of each currency one base unit buys
     */
    public static function of(array $ratesPerBaseUnit): self
    {
        $rates = [self::BASE_CURRENCY => 1.0];

        foreach ($ratesPerBaseUnit as $currency => $rate) {
            $code = strtoupper(trim($currency));

            if ($code !== '' && is_finite($rate) && $rate > 0.0) {
                $rates[$code] = $rate;
            }
        }

        return new self($rates);
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $rates = [];

        foreach ($stored as $currency => $rate) {
            if (is_string($currency)) {
                $rates[$currency] = Guard::toFloat($rate);
            }
        }

        return self::of($rates);
    }

    /**
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return $this->ratesPerBaseUnit;
    }

    public function knows(string $currency): bool
    {
        return array_key_exists(strtoupper(trim($currency)), $this->ratesPerBaseUnit);
    }

    /**
     * An unknown currency converts at parity rather than at zero. A calculator that silently
     * reports a year abroad as costing nothing is worse than one that is slightly wrong.
     */
    public function convert(int $amountInBase, string $currency): int
    {
        $code = strtoupper(trim($currency));
        $rate = $this->ratesPerBaseUnit[$code] ?? 1.0;

        return (int) round($amountInBase * $rate);
    }

    /**
     * @return list<string>
     */
    public function currencies(): array
    {
        return array_keys($this->ratesPerBaseUnit);
    }
}
