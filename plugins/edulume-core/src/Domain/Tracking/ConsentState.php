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
    /** Distinguishes "answered, and said no to everything" from "never asked". */
    private const REJECTED_MARKER = 'none';

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

    /**
     * Reads the compact cookie form: a comma-separated list of granted categories.
     *
     * An empty or unreadable cookie reads as *undecided*, not as rejected. The difference
     * matters: undecided shows the banner again, while a wrongly-remembered rejection means the
     * visitor is never asked and the site quietly loses every measurement it was entitled to.
     */
    public static function fromCookie(string $cookie): self
    {
        $trimmed = trim($cookie);

        if ($trimmed === '') {
            return self::undecided();
        }

        if ($trimmed === self::REJECTED_MARKER) {
            return self::rejectingEverything();
        }

        $granted = [];

        foreach (explode(',', $trimmed) as $value) {
            $category = ConsentCategory::tryFrom(trim($value));

            if ($category !== null) {
                $granted[] = $category;
            }
        }

        return $granted === [] ? self::undecided() : self::granting($granted);
    }

    /**
     * The cookie value to store. Categories only — never a timestamp or an identifier, because
     * a consent cookie that identifies the visitor is itself the thing being consented to.
     */
    public function toCookie(): string
    {
        if (!$this->hasDecided) {
            return '';
        }

        $granted = [];

        foreach (ConsentCategory::cases() as $category) {
            if (!$category->isAlwaysAllowed() && $this->allows($category)) {
                $granted[] = $category->value;
            }
        }

        return $granted === [] ? self::REJECTED_MARKER : implode(',', $granted);
    }

    public function hasBeenAnswered(): bool
    {
        return $this->hasDecided;
    }

    public function allows(ConsentCategory $category): bool
    {
        if ($category->isAlwaysAllowed()) {
            return true;
        }

        return $this->decisions[$category->value] ?? false;
    }
}
