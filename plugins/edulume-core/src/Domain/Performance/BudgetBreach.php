<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Performance;

/**
 * One budget metric that came in over.
 *
 * `$measured` is null when the run produced no figure at all, which is reported as a breach
 * rather than skipped — a missing measurement and a passing one look identical otherwise.
 */
final class BudgetBreach
{
    public function __construct(
        public readonly string $metric,
        public readonly int|float|null $measured,
        public readonly int|float $threshold,
    ) {
    }

    public function wasMeasured(): bool
    {
        return $this->measured !== null;
    }

    public function describe(): string
    {
        if (!$this->wasMeasured()) {
            return sprintf('%s was not measured (budget %s)', $this->metric, $this->format($this->threshold));
        }

        return sprintf(
            '%s is %s against a budget of %s',
            $this->metric,
            $this->format($this->measured ?? 0),
            $this->format($this->threshold),
        );
    }

    private function format(int|float $value): string
    {
        return is_int($value) ? (string) $value : rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
