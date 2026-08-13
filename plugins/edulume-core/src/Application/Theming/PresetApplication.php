<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Theming;

use Edulume\Core\Domain\Theming\StylePreset;
use Edulume\Core\Domain\Theming\ThemeSettings;

/**
 * The result of applying a preset: the new settings, and the snapshot taken immediately
 * before, so the change can be undone within the session.
 *
 * The snapshot is the serialised previous state rather than a reference to a mutable object,
 * which is what makes an undo restore byte for byte however many previews happened in
 * between.
 */
final class PresetApplication
{
    /**
     * @param array<string, mixed> $snapshot
     */
    private function __construct(
        public readonly StylePreset $preset,
        public readonly ThemeSettings $settings,
        private readonly array $snapshot,
    ) {
    }

    public static function of(StylePreset $preset, ThemeSettings $previous, ThemeSettings $applied): self
    {
        return new self($preset, $applied, $previous->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return $this->snapshot;
    }

    public function undo(): ThemeSettings
    {
        return ThemeSettings::fromArray($this->snapshot);
    }
}
