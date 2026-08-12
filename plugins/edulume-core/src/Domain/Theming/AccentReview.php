<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\ContrastPair;
use Edulume\Core\Domain\Color\Srgb;

/**
 * The verdict on a custom accent hex used as text on each mode's page surface, with the
 * nearest compliant colour for whichever mode the hex as typed would fail.
 *
 * The verdict is per mode by necessity: a light page and a dark page sit at opposite ends of
 * the luminance range, and no single colour clears 4.5:1 against both. A suggestion is
 * present exactly when that mode fails, so the admin can offer a one-click fix instead of
 * either silently accepting an illegible accent or refusing the input outright.
 */
final class AccentReview
{
    private function __construct(
        public readonly Srgb $seed,
        public readonly ContrastPair $onLightSurface,
        public readonly ContrastPair $onDarkSurface,
        public readonly ?Srgb $lightModeSuggestion,
        public readonly ?Srgb $darkModeSuggestion,
    ) {
    }

    public static function of(
        Srgb $seed,
        ContrastPair $onLightSurface,
        ContrastPair $onDarkSurface,
        ?Srgb $lightModeSuggestion,
        ?Srgb $darkModeSuggestion
    ): self {
        return new self($seed, $onLightSurface, $onDarkSurface, $lightModeSuggestion, $darkModeSuggestion);
    }

    public function isCompliantIn(ThemeMode $mode): bool
    {
        return $this->suggestionFor($mode) === null;
    }

    public function needsAttention(): bool
    {
        return !$this->isCompliantIn(ThemeMode::Light) || !$this->isCompliantIn(ThemeMode::Dark);
    }

    public function suggestionFor(ThemeMode $mode): ?Srgb
    {
        return $mode === ThemeMode::Light ? $this->lightModeSuggestion : $this->darkModeSuggestion;
    }

    public function pairFor(ThemeMode $mode): ContrastPair
    {
        return $mode === ThemeMode::Light ? $this->onLightSurface : $this->onDarkSurface;
    }
}
