<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Chrome;

use Edulume\Core\Domain\Theming\ThemeMode;

/**
 * The logo slots, including the dark-mode alternates.
 *
 * A single logo is the reason so many dark modes ship with an invisible wordmark. Every slot
 * falls back rather than failing, so a site with one uploaded logo still works everywhere.
 */
final class LogoSet
{
    public function __construct(
        public readonly string $primary = '',
        public readonly string $primaryDark = '',
        public readonly string $mobile = '',
        public readonly string $mobileDark = '',
        public readonly string $footer = '',
        public readonly string $footerDark = '',
        public readonly string $alternativeText = '',
    ) {
    }

    /**
     * Resolves the logo for a slot and mode, falling back along a chain that always ends at the
     * primary light logo: dark variant, then the slot's light variant, then the primary.
     */
    public function resolve(LogoSlot $slot, ThemeMode $mode): string
    {
        $candidates = $mode === ThemeMode::Dark
            ? [$this->darkFor($slot), $this->lightFor($slot), $this->primaryDark, $this->primary]
            : [$this->lightFor($slot), $this->primary];

        foreach ($candidates as $candidate) {
            if (trim($candidate) !== '') {
                return $candidate;
            }
        }

        return '';
    }

    public function hasAny(): bool
    {
        return trim($this->primary) !== '';
    }

    private function lightFor(LogoSlot $slot): string
    {
        return match ($slot) {
            LogoSlot::Header => $this->primary,
            LogoSlot::Mobile => $this->mobile,
            LogoSlot::Footer => $this->footer,
        };
    }

    private function darkFor(LogoSlot $slot): string
    {
        return match ($slot) {
            LogoSlot::Header => $this->primaryDark,
            LogoSlot::Mobile => $this->mobileDark,
            LogoSlot::Footer => $this->footerDark,
        };
    }
}
