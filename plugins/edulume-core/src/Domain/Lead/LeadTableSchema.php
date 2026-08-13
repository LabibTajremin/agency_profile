<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Lead;

/**
 * The lead tables, described without a database connection in sight.
 *
 * Leads live in their own tables, never in the posts table. A hundred thousand leads in
 * wp_posts drags every admin list, every `WP_Query`, and every plugin that counts posts —
 * and a lead is not content: it has a pipeline, an owner, and a retention period.
 *
 * The indexes are part of the schema rather than an afterthought, because the queries the
 * inbox runs are known in advance: filter by status, by assignee, by branch, and sort by date.
 */
final class LeadTableSchema
{
    public const LEADS_TABLE = 'edulume_leads';
    public const LEAD_META_TABLE = 'edulume_lead_meta';

    public const CURRENT_VERSION = 1;

    public static function leadsTableSql(string $tablePrefix, string $charsetCollate): string
    {
        $table = $tablePrefix . self::LEADS_TABLE;

        return <<<SQL
        CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id VARCHAR(64) NOT NULL DEFAULT '',
            name VARCHAR(200) NOT NULL DEFAULT '',
            email VARCHAR(254) NOT NULL DEFAULT '',
            phone VARCHAR(64) NOT NULL DEFAULT '',
            status VARCHAR(32) NOT NULL DEFAULT 'new',
            assigned_to_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            branch_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            consent_given TINYINT(1) NOT NULL DEFAULT 0,
            source_url TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY status_created_at (status, created_at),
            KEY assigned_to_id (assigned_to_id, status),
            KEY branch_id (branch_id, status),
            KEY email (email),
            KEY created_at (created_at)
        ) {$charsetCollate};
        SQL;
    }

    public static function leadMetaTableSql(string $tablePrefix, string $charsetCollate): string
    {
        $table = $tablePrefix . self::LEAD_META_TABLE;

        return <<<SQL
        CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            meta_key VARCHAR(191) NOT NULL DEFAULT '',
            meta_value LONGTEXT NOT NULL,
            PRIMARY KEY  (id),
            KEY lead_id_meta_key (lead_id, meta_key),
            KEY meta_key (meta_key)
        ) {$charsetCollate};
        SQL;
    }

    /**
     * @return list<string>
     */
    public static function allTableSql(string $tablePrefix, string $charsetCollate): array
    {
        return [
            self::leadsTableSql($tablePrefix, $charsetCollate),
            self::leadMetaTableSql($tablePrefix, $charsetCollate),
        ];
    }

    /**
     * @return list<string>
     */
    public static function tableNames(string $tablePrefix): array
    {
        return [$tablePrefix . self::LEADS_TABLE, $tablePrefix . self::LEAD_META_TABLE];
    }
}
