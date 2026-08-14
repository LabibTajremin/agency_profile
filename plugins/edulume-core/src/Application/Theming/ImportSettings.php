<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Theming;

use Edulume\Core\Domain\Support\Guard;
use Edulume\Core\Domain\Theming\SettingsDiff;
use Edulume\Core\Domain\Theming\ThemeSettings;

/**
 * Reads an exported configuration and reports what applying it would change, before applying
 * anything.
 *
 * `preview()` never writes. Importing blind is how a site owner discovers at 6pm that the
 * export they were handed also carried someone else's fonts.
 */
final class ImportSettings
{
    public function preview(ThemeSettings $current, string $json): SettingsImportPreview
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return SettingsImportPreview::unreadable();
        }

        $incoming = ThemeSettings::fromArray(Guard::toArray($decoded));

        return SettingsImportPreview::of($incoming, SettingsDiff::between($current->toArray(), $incoming->toArray()));
    }

    public function export(ThemeSettings $settings): string
    {
        $encoded = json_encode($settings->toArray(), JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }
}
