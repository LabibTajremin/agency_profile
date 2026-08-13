<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Theming;

use Edulume\Core\Domain\Theming\SettingsDiff;
use Edulume\Core\Domain\Theming\ThemeSettings;

/**
 * What an import would do. Carries the diff so the admin can show every changed key before the
 * site owner commits to it.
 */
final class SettingsImportPreview
{
    private function __construct(
        public readonly bool $isReadable,
        private readonly ?ThemeSettings $incoming,
        private readonly ?SettingsDiff $diff,
    ) {
    }

    public static function of(ThemeSettings $incoming, SettingsDiff $diff): self
    {
        return new self(true, $incoming, $diff);
    }

    public static function unreadable(): self
    {
        return new self(false, null, null);
    }

    public function diff(): SettingsDiff
    {
        return $this->diff ?? SettingsDiff::between([], []);
    }

    public function apply(ThemeSettings $current): ThemeSettings
    {
        return $this->incoming ?? $current;
    }

    public function changesNothing(): bool
    {
        return $this->diff()->isEmpty();
    }
}
