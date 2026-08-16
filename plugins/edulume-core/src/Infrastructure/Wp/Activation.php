<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Wp;

use Edulume\Core\Infrastructure\Lead\LeadTableMigrator;
use Edulume\Core\Infrastructure\Settings\OptionSettingsRepository;

/**
 * Runs once, when the plugin is activated.
 *
 * Activation is deliberately thin: it grants capabilities and records the version. It does
 * not write default settings, because absent settings already load as defaults, and writing
 * them would overwrite a configuration that survived a deactivate/reactivate cycle.
 */
final class Activation
{
    public const VERSION_OPTION = 'edulume_version';
    public const ACTIVATED_AT_OPTION = 'edulume_activated_at';

    public static function run(string $version): void
    {
        Capabilities::grantToAdministrator();

        (new LeadTableMigrator())->migrate();

        update_option(self::VERSION_OPTION, $version, true);

        if (get_option(self::ACTIVATED_AT_OPTION, '') === '') {
            update_option(self::ACTIVATED_AT_OPTION, gmdate('c'), false);
        }

        flush_rewrite_rules();
    }

    /**
     * Whether the database was last prepared by a different version of this code.
     *
     * An absent option counts as pending: that is a site whose plugin files were put in place
     * without an activation ever running, which happens whenever a deployment copies a plugin
     * directory into an install that already had it active.
     */
    public static function isUpgradePending(string $version): bool
    {
        return (string) get_option(self::VERSION_OPTION, '') !== $version;
    }

    public static function hasStoredSettings(): bool
    {
        return get_option(OptionSettingsRepository::SETTINGS_OPTION, null) !== null;
    }
}
