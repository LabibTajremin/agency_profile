<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Application\Port\LeadRepository;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadQuery;
use Edulume\Core\Domain\Lead\LeadStatus;
use Edulume\Core\Domain\Lead\LeadTableSchema;
use Edulume\Core\Domain\Support\Guard;

/**
 * Lead storage on the custom tables.
 *
 * Every query goes through `$wpdb->prepare()`. There is no string-concatenated SQL anywhere in
 * this class, including in the parts that look harmless — an ORDER BY built from a request
 * parameter is exactly how injection gets in, so sort columns are matched against an allowlist
 * rather than interpolated.
 */
final class WpdbLeadRepository implements LeadRepository
{
    private const SORTABLE_COLUMNS = ['created_at', 'updated_at', 'name', 'status'];
    private const DEFAULT_SORT_COLUMN = 'created_at';

    public function save(Lead $lead, string $savedAt): int
    {
        global $wpdb;

        $table = $wpdb->prefix . LeadTableSchema::LEADS_TABLE;

        $row = [
            'form_id' => $lead->formId,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => $lead->status->value,
            'assigned_to_id' => $lead->assignedToId,
            'branch_id' => $lead->branchId,
            'source_url' => $lead->submission['sourceUrl'] ?? '',
            'updated_at' => $savedAt,
        ];

        if ($lead->id > 0) {
            $wpdb->update($table, $row, ['id' => $lead->id]);
            $this->saveMeta($lead->id, $lead);

            return $lead->id;
        }

        $row['created_at'] = $lead->createdAt;
        $wpdb->insert($table, $row);

        $id = $wpdb->insert_id;
        $this->saveMeta($id, $lead);

        return $id;
    }

    public function find(int $id): ?Lead
    {
        global $wpdb;

        $table = $wpdb->prefix . LeadTableSchema::LEADS_TABLE;

        $rows = $wpdb->get_results(
            $wpdb->prepare('SELECT * FROM %i WHERE id = %d', $table, $id),
            'ARRAY_A'
        );

        if ($rows === []) {
            return null;
        }

        return $this->hydrate($rows[0]);
    }

    /**
     * @return list<Lead>
     */
    public function search(LeadQuery $query): array
    {
        global $wpdb;

        $table = $wpdb->prefix . LeadTableSchema::LEADS_TABLE;

        $conditions = ['1 = %d'];
        $values = [1];

        if ($query->status !== null) {
            $conditions[] = 'status = %s';
            $values[] = $query->status->value;
        }

        if ($query->assignedToId !== null) {
            $conditions[] = 'assigned_to_id = %d';
            $values[] = $query->assignedToId;
        }

        if ($query->branchId !== null) {
            $conditions[] = 'branch_id = %d';
            $values[] = $query->branchId;
        }

        if ($query->searchTerm !== '') {
            $conditions[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $like = '%' . $wpdb->esc_like($query->searchTerm) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $where = implode(' AND ', $conditions);
        $order = $this->sortColumn($query->sortColumn) . ' ' . ($query->isDescending ? 'DESC' : 'ASC');

        $values[] = $query->perPage;
        $values[] = $query->offset();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                // The WHERE fragment is built from bound placeholders and the ORDER BY from an
                // allowlist of column names, so neither can carry request data. The table name
                // is a %i identifier placeholder. Nothing here is interpolated from input.
                // The replacement count below is right: the WHERE fragment carries its own
                // placeholders, which the sniff cannot see from here.
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
                "SELECT * FROM %i WHERE {$where} ORDER BY {$order} LIMIT %d OFFSET %d",
                $table,
                ...$values
            ),
            'ARRAY_A'
        );

        return array_map([$this, 'hydrate'], $rows);
    }

    public function count(LeadQuery $query): int
    {
        global $wpdb;

        $table = $wpdb->prefix . LeadTableSchema::LEADS_TABLE;

        if ($query->status === null) {
            return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE 1 = %d', $table, 1));
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare('SELECT COUNT(*) FROM %i WHERE status = %s', $table, $query->status->value)
        );
    }

    public function delete(int $id): void
    {
        global $wpdb;

        $leads = $wpdb->prefix . LeadTableSchema::LEADS_TABLE;
        $meta = $wpdb->prefix . LeadTableSchema::LEAD_META_TABLE;

        $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE lead_id = %d', $meta, $id));
        $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE id = %d', $leads, $id));
    }

    private function saveMeta(int $leadId, Lead $lead): void
    {
        global $wpdb;

        $table = $wpdb->prefix . LeadTableSchema::LEAD_META_TABLE;

        $wpdb->query($wpdb->prepare('DELETE FROM %i WHERE lead_id = %d', $table, $leadId));

        $wpdb->insert($table, [
            'lead_id' => $leadId,
            'meta_key' => 'submission',
            'meta_value' => (string) wp_json_encode($lead->submission),
        ]);

        $wpdb->insert($table, [
            'lead_id' => $leadId,
            'meta_key' => 'notes',
            'meta_value' => (string) wp_json_encode(array_map(
                static fn ($note): array => $note->toArray(),
                $lead->notes,
            )),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Lead
    {
        $id = Guard::toInt($row['id'] ?? null);

        return Lead::fromArray([
            'id' => $id,
            'name' => Guard::toString($row['name'] ?? null),
            'email' => Guard::toString($row['email'] ?? null),
            'phone' => Guard::toString($row['phone'] ?? null),
            'status' => Guard::toString($row['status'] ?? null, LeadStatus::New->value),
            'assignedToId' => Guard::toInt($row['assigned_to_id'] ?? null),
            'branchId' => Guard::toInt($row['branch_id'] ?? null),
            'formId' => Guard::toString($row['form_id'] ?? null),
            'createdAt' => Guard::toString($row['created_at'] ?? null),
            'submission' => $this->readMeta($id, 'submission'),
            'notes' => $this->readMeta($id, 'notes'),
        ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function readMeta(int $leadId, string $key): array
    {
        global $wpdb;

        $table = $wpdb->prefix . LeadTableSchema::LEAD_META_TABLE;

        $value = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT meta_value FROM %i WHERE lead_id = %d AND meta_key = %s',
                $table,
                $leadId,
                $key
            )
        );

        return Guard::toArray(json_decode((string) $value, true));
    }

    /**
     * An allowlist rather than interpolation: an ORDER BY built from a request parameter is
     * exactly how injection gets past a codebase that otherwise prepares everything.
     */
    private function sortColumn(string $requested): string
    {
        return in_array($requested, self::SORTABLE_COLUMNS, true) ? $requested : self::DEFAULT_SORT_COLUMN;
    }
}
