<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Tracking;

use Edulume\Core\Domain\Support\Guard;

/**
 * What one visitor has agreed to.
 *
 * The default is "nothing decided", which grants nothing beyond the necessary category. An
 * undecided visitor is not a consenting one.
 */
final class ConsentState
{
    /**
     * @param array<string, bool> $decisions
     */
    private function __construct(
        private readonly array $decisions,
        public readonly bool $hasDecided,
    ) {
    }

    public static function undecided(): self
    {
        return new self([], false);
    }

    /**
     * @param list<ConsentCategory> $granted
     */
    public static function granting(array $granted): self
    {
        $decisions = [];

        foreach ($granted as $category) {
            $decisions[$category->value] = true;
        }

        return new self($decisions, true);
    }

    public static function rejectingEverything(): self
    {
        return new self([], true);
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $decisions = [];

        foreach (ConsentCategory::cases() as $category) {
            if (Guard::toBool($stored[$category->value] ?? null)) {
                $decisions[$category->value] = true;
            }
        }

        return new self($decisions, Guard::toBool($stored['hasDecided'] ?? null));
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        $stored = ['hasDecided' => $this->hasDecided];

        foreach (ConsentCategory::cases() as $category) {
            $stored[$category->value] = $this->allows($category);
        }

        return $stored;
    }

    public function allows(ConsentCategory $category): bool
    {
        if ($category->isAlwaysAllowed()) {
            return true;
        }

        return $this->decisions[$category->value] ?? false;
    }
}
