<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * Undo and redo within one configuration session.
 *
 * Pushing a new state clears the redo branch, which is what everyone expects and what people
 * are surprised by when it is missing: undo three times, change something, and the future you
 * abandoned must not still be reachable.
 */
final class UndoStack
{
    public const MAXIMUM_DEPTH = 50;

    /**
     * @param list<array<string, mixed>> $past
     * @param array<string, mixed> $present
     * @param list<array<string, mixed>> $future
     */
    private function __construct(
        private readonly array $past,
        private readonly array $present,
        private readonly array $future,
    ) {
    }

    public static function startingAt(ThemeSettings $settings): self
    {
        return new self([], $settings->toArray(), []);
    }

    public function push(ThemeSettings $settings): self
    {
        $state = $settings->toArray();

        if ($state === $this->present) {
            return $this;
        }

        return new self(
            array_slice([...$this->past, $this->present], -self::MAXIMUM_DEPTH),
            $state,
            [],
        );
    }

    public function canUndo(): bool
    {
        return $this->past !== [];
    }

    public function canRedo(): bool
    {
        return $this->future !== [];
    }

    public function undo(): self
    {
        $past = $this->past;
        $previous = array_pop($past);

        if ($previous === null) {
            return $this;
        }

        return new self($past, $previous, [$this->present, ...$this->future]);
    }

    public function redo(): self
    {
        $future = $this->future;
        $next = array_shift($future);

        if ($next === null) {
            return $this;
        }

        return new self([...$this->past, $this->present], $next, $future);
    }

    public function current(): ThemeSettings
    {
        return ThemeSettings::fromArray($this->present);
    }

    public function depth(): int
    {
        return count($this->past);
    }
}
