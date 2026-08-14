<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Support\Guard;

/**
 * A configuration as it stood at one moment, and why it was captured.
 */
final class SettingsSnapshot
{
    /**
     * @param array<string, mixed> $settings
     */
    private function __construct(
        public readonly string $reason,
        public readonly string $takenAt,
        private readonly array $settings,
    ) {
    }

    public static function of(ThemeSettings $settings, string $reason, string $takenAt): self
    {
        return new self($reason, $takenAt, $settings->toArray());
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        /** @var array<string, mixed> $settings */
        $settings = Guard::toArray($stored['settings'] ?? null);

        return new self(
            Guard::toString($stored['reason'] ?? null),
            Guard::toString($stored['takenAt'] ?? null),
            $settings,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['reason' => $this->reason, 'takenAt' => $this->takenAt, 'settings' => $this->settings];
    }

    public function restore(): ThemeSettings
    {
        return ThemeSettings::fromArray($this->settings);
    }
}
