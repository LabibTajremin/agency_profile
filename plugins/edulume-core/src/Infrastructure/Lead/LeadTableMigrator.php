<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Domain\Lead\LeadTableSchema;

/**
 * Creates and upgrades the lead tables.
 *
 * Runs through `dbDelta`, which is idempotent by design: it compares the described schema
 * against what is there and issues only the difference. Running it twice must be a no-op,
 * because it runs on activation and on every version bump, and a site owner reactivating a
 * plugin must not lose a lead.
 */
final class LeadTableMigrator
{
    public const VERSION_OPTION = 'edulume_lead_tables_version';

    public function migrate(): void
    {
        if ($this->installedVersion() === LeadTableSchema::CURRENT_VERSION) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        global $wpdb;

        foreach (LeadTableSchema::allTableSql($wpdb->prefix, $wpdb->get_charset_collate()) as $sql) {
            dbDelta($sql);
        }

        update_option(self::VERSION_OPTION, LeadTableSchema::CURRENT_VERSION, true);
    }

    public function installedVersion(): int
    {
        return (int) get_option(self::VERSION_OPTION, 0);
    }

    public function tablesExist(): bool
    {
        global $wpdb;

        foreach (LeadTableSchema::tableNames($wpdb->prefix) as $table) {
            $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

            if ($found !== $table) {
                return false;
            }
        }

        return true;
    }
}
