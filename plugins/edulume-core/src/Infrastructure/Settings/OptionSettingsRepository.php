<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Settings;

use Edulume\Core\Application\Port\SettingsRepository;
use Edulume\Core\Domain\Support\Guard;
use Edulume\Core\Domain\Theming\SectionId;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\SettingsMigrator;
use Edulume\Core\Domain\Theming\ThemeSettings;

/**
 * Stores settings in two WordPress options: the theme settings blob and the per-section
 * overrides.
 *
 * Migration happens on read, not on upgrade. A site can be restored from a database dump
 * older than the plugin, or have its plugin folder replaced by FTP without any upgrade hook
 * ever firing, and the settings still have to load.
 */
final class OptionSettingsRepository implements SettingsRepository
{
    public const SETTINGS_OPTION = 'edulume_theme_settings';
    public const SECTION_OVERRIDES_OPTION = 'edulume_section_overrides';
    private const ORDER_KEY = '_order';

    public function __construct(private readonly SettingsMigrator $migrator)
    {
    }

    public function load(): ThemeSettings
    {
        $stored = Guard::toArray(get_option(self::SETTINGS_OPTION, []));

        if ($this->migrator->needsMigration($stored)) {
            $stored = $this->migrator->migrate($stored);

            update_option(self::SETTINGS_OPTION, $stored, false);
        }

        return ThemeSettings::fromArray($stored);
    }

    public function save(ThemeSettings $settings): void
    {
        update_option(self::SETTINGS_OPTION, $settings->toArray(), false);
    }

    public function loadSectionOverrides(): array
    {
        $stored = Guard::toArray(get_option(self::SECTION_OVERRIDES_OPTION, []));
        $overrides = [];

        foreach (SectionId::cases() as $section) {
            $sectionValues = Guard::toArray($stored[$section->value] ?? null);

            if ($sectionValues === []) {
                continue;
            }

            $override = SectionOverride::fromArray($sectionValues);

            if (!$override->isInheritingEverything()) {
                $overrides[$section->value] = $override;
            }
        }

        return $overrides;
    }

    public function saveSectionOverride(string $sectionId, SectionOverride $override): void
    {
        if ($override->isInheritingEverything()) {
            $this->deleteSectionOverride($sectionId);

            return;
        }

        $stored = Guard::toArray(get_option(self::SECTION_OVERRIDES_OPTION, []));
        $stored[$sectionId] = $override->toArray();

        update_option(self::SECTION_OVERRIDES_OPTION, $stored, false);
    }

    public function deleteSectionOverride(string $sectionId): void
    {
        $stored = Guard::toArray(get_option(self::SECTION_OVERRIDES_OPTION, []));

        if (!array_key_exists($sectionId, $stored)) {
            return;
        }

        unset($stored[$sectionId]);

        update_option(self::SECTION_OVERRIDES_OPTION, $stored, false);
    }

    /**
     * The order lives in the overrides row under a reserved key.
     *
     * `loadSectionOverrides()` walks `SectionId::cases()` rather than the row's own keys, so a
     * key that is not a section slug is invisible to it. That is what makes this safe, and it
     * beats a third option row for one small list about the same subject.
     *
     * @return list<string>
     */
    public function loadSectionOrder(): array
    {
        $stored = Guard::toArray(get_option(self::SECTION_OVERRIDES_OPTION, []));
        $order = Guard::toArray($stored[self::ORDER_KEY] ?? null);

        return array_values(array_filter($order, 'is_string'));
    }

    /**
     * @param list<string> $order
     */
    public function saveSectionOrder(array $order): void
    {
        $stored = Guard::toArray(get_option(self::SECTION_OVERRIDES_OPTION, []));
        $stored[self::ORDER_KEY] = array_values($order);

        update_option(self::SECTION_OVERRIDES_OPTION, $stored, false);
    }
}
