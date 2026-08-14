<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Performance;

/**
 * The verdict on one measured page.
 */
final class BudgetReport
{
    /**
     * @param list<BudgetBreach> $breaches
     */
    public function __construct(public readonly array $breaches)
    {
    }

    public function isWithinBudget(): bool
    {
        return $this->breaches === [];
    }

    /**
     * Every breach, one per line, in the order the budget declares them — a stable order so a
     * CI log diff between two runs is readable.
     */
    public function describe(): string
    {
        if ($this->isWithinBudget()) {
            return 'Within budget.';
        }

        return implode("\n", array_map(
            static fn (BudgetBreach $breach): string => $breach->describe(),
            $this->breaches,
        ));
    }

    /**
     * @return list<string>
     */
    public function breachedMetrics(): array
    {
        return array_map(static fn (BudgetBreach $breach): string => $breach->metric, $this->breaches);
    }
}
