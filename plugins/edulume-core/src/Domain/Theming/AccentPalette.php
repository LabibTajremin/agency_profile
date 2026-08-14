<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\ContrastPair;
use Edulume\Core\Domain\Color\Srgb;

/**
 * Eleven accent steps, the foreground that is legible on each of them, and the two
 * per-mode choices the rest of the product needs.
 *
 * The fill step and the accent-as-text step are deliberately separate. A colour that works
 * as a button background is frequently unreadable as link text on the same surface, and
 * collapsing the two is the usual reason accent-driven themes fail a contrast audit.
 */
final class AccentPalette
{
    public const STEP_COUNT = 11;

    /**
     * A strong mid-dark step reads as a solid control on a light page. On a dark page the
     * same colour sinks into the background, so the fill moves several steps brighter.
     */
    private const FILL_STEP_LIGHT_MODE = 7;
    private const FILL_STEP_DARK_MODE = 4;

    /**
     * @param list<Srgb> $steps
     * @param list<Srgb> $foregrounds
     * @param array<string, Srgb> $textColors keyed by theme mode value
     */
    private function __construct(
        private readonly array $steps,
        private readonly array $foregrounds,
        private readonly array $textColors,
    ) {
    }

    /**
     * @param list<Srgb> $steps
     * @param list<Srgb> $foregrounds
     * @param array<string, Srgb> $textColors keyed by theme mode value
     *
     * @throws InvalidPaletteException when the step counts do not match
     */
    public static function fromSteps(array $steps, array $foregrounds, array $textColors): self
    {
        if (count($steps) !== self::STEP_COUNT) {
            throw InvalidPaletteException::forStepCount(count($steps), self::STEP_COUNT);
        }

        if (count($foregrounds) !== self::STEP_COUNT) {
            throw InvalidPaletteException::forStepCount(count($foregrounds), self::STEP_COUNT);
        }

        return new self($steps, $foregrounds, $textColors);
    }

    /**
     * @throws InvalidPaletteException when the step does not exist
     */
    public function step(int $index): Srgb
    {
        if (!array_key_exists($index, $this->steps)) {
            throw InvalidPaletteException::forStepIndex($index, self::STEP_COUNT);
        }

        return $this->steps[$index];
    }

    /**
     * The foreground colour that is legible on the given step.
     *
     * @throws InvalidPaletteException when the step does not exist
     */
    public function foregroundOn(int $index): Srgb
    {
        if (!array_key_exists($index, $this->foregrounds)) {
            throw InvalidPaletteException::forStepIndex($index, self::STEP_COUNT);
        }

        return $this->foregrounds[$index];
    }

    public function contrastOn(int $index): ContrastPair
    {
        return ContrastPair::of($this->step($index), $this->foregroundOn($index));
    }

    /**
     * @return list<Srgb>
     */
    public function steps(): array
    {
        return $this->steps;
    }

    public function fillStep(ThemeMode $mode): int
    {
        return $mode === ThemeMode::Light ? self::FILL_STEP_LIGHT_MODE : self::FILL_STEP_DARK_MODE;
    }

    public function fill(ThemeMode $mode): Srgb
    {
        return $this->step($this->fillStep($mode));
    }

    public function onFill(ThemeMode $mode): Srgb
    {
        return $this->foregroundOn($this->fillStep($mode));
    }

    /**
     * The accent used as text on this mode's page surface — never the fill colour.
     */
    public function text(ThemeMode $mode): Srgb
    {
        return $this->textColors[$mode->value];
    }
}
