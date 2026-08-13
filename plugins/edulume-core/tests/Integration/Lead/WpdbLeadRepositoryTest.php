<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Integration\Lead;

use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadNote;
use Edulume\Core\Domain\Lead\LeadQuery;
use Edulume\Core\Domain\Lead\LeadStatus;
use Edulume\Core\Domain\Lead\LeadTableSchema;
use Edulume\Core\Infrastructure\Lead\LeadTableMigrator;
use Edulume\Core\Infrastructure\Lead\WpdbLeadRepository;
use PHPUnit\Framework\Attributes\Test;
use WP_UnitTestCase;

/**
 * Proves the lead tables create idempotently, that leads round-trip through them, and that a
 * realistic inbox filters and paginates without a slow query.
 */
final class WpdbLeadRepositoryTest extends WP_UnitTestCase
{
    private const NOW = '2026-03-01 09:00:00';
    private const SLOW_QUERY_SECONDS = 1.0;

    private WpdbLeadRepository $repository;

    public function set_up(): void
    {
        parent::set_up();

        delete_option(LeadTableMigrator::VERSION_OPTION);
        (new LeadTableMigrator())->migrate();

        global $wpdb;

        foreach (LeadTableSchema::tableNames($wpdb->prefix) as $table) {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }

        $this->repository = new WpdbLeadRepository();
    }

    private function capture(string $name, string $email, int $branchId = 0): Lead
    {
        return Lead::captured($name, $email, '+8801700000000', 'enquiry', ['destination' => 'Canada'], self::NOW, $branchId);
    }

    #[Test]
    public function it_creates_both_tables(): void
    {
        $this->assertTrue((new LeadTableMigrator())->tablesExist());
        $this->assertSame(LeadTableSchema::CURRENT_VERSION, (new LeadTableMigrator())->installedVersion());
    }

    #[Test]
    public function it_migrates_idempotently(): void
    {
        $id = $this->repository->save($this->capture('Amina Rahman', 'amina@example.test'), self::NOW);

        (new LeadTableMigrator())->migrate();
        delete_option(LeadTableMigrator::VERSION_OPTION);
        (new LeadTableMigrator())->migrate();

        $this->assertNotNull($this->repository->find($id));
    }

    #[Test]
    public function it_round_trips_a_lead_including_notes_and_submission(): void
    {
        $lead = $this->capture('Amina Rahman', 'amina@example.test', 7)
            ->movedTo(LeadStatus::Contacted, 3, self::NOW)
            ->assignedTo(12, self::NOW)
            ->annotated(LeadNote::written(4, 'Spoke to the family.', self::NOW));

        $id = $this->repository->save($lead, self::NOW);
        $loaded = $this->repository->find($id);

        $this->assertNotNull($loaded);
        $this->assertSame('Amina Rahman', $loaded->name);
        $this->assertSame(LeadStatus::Contacted, $loaded->status);
        $this->assertSame(12, $loaded->assignedToId);
        $this->assertSame(7, $loaded->branchId);
        $this->assertSame(['destination' => 'Canada'], $loaded->submission);
        $this->assertCount(3, $loaded->notes);
    }

    #[Test]
    public function it_returns_nothing_for_a_lead_that_does_not_exist(): void
    {
        $this->assertNull($this->repository->find(987654));
    }

    #[Test]
    public function it_updates_rather_than_duplicating_on_a_second_save(): void
    {
        $id = $this->repository->save($this->capture('Amina Rahman', 'amina@example.test'), self::NOW);
        $loaded = $this->repository->find($id);

        $this->assertNotNull($loaded);

        $this->repository->save($loaded->movedTo(LeadStatus::Contacted, 3, self::NOW), self::NOW);

        $this->assertSame(1, $this->repository->count(LeadQuery::all()));
    }

    #[Test]
    public function it_deletes_a_lead_and_its_meta(): void
    {
        $id = $this->repository->save($this->capture('Amina Rahman', 'amina@example.test'), self::NOW);

        $this->repository->delete($id);

        global $wpdb;
        $metaTable = $wpdb->prefix . LeadTableSchema::LEAD_META_TABLE;

        $this->assertNull($this->repository->find($id));
        $this->assertSame(
            '0',
            $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$metaTable} WHERE lead_id = %d", $id)),
        );
    }

    #[Test]
    public function it_filters_by_status_assignee_and_branch(): void
    {
        $this->repository->save(
            $this->capture('Amina Rahman', 'amina@example.test', 7)->assignedTo(12, self::NOW),
            self::NOW,
        );
        $this->repository->save($this->capture('Karim Uddin', 'karim@example.test', 9), self::NOW);

        $this->assertCount(2, $this->repository->search(LeadQuery::of(LeadStatus::New)));
        $this->assertCount(1, $this->repository->search(LeadQuery::of(assignedToId: 12)));
        $this->assertCount(1, $this->repository->search(LeadQuery::of(branchId: 9)));
        $this->assertCount(0, $this->repository->search(LeadQuery::of(branchId: 99)));
    }

    #[Test]
    public function it_searches_across_name_email_and_phone(): void
    {
        $this->repository->save($this->capture('Amina Rahman', 'amina@example.test'), self::NOW);
        $this->repository->save($this->capture('Karim Uddin', 'karim@example.test'), self::NOW);

        $this->assertCount(1, $this->repository->search(LeadQuery::of(searchTerm: 'Amina')));
        $this->assertCount(1, $this->repository->search(LeadQuery::of(searchTerm: 'karim@example')));
        $this->assertCount(2, $this->repository->search(LeadQuery::of(searchTerm: '+880')));
    }

    #[Test]
    public function it_paginates_and_filters_ten_thousand_leads_without_a_slow_query(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . LeadTableSchema::LEADS_TABLE;
        $values = [];

        for ($index = 1; $index <= 10000; $index++) {
            $status = $index % 3 === 0 ? LeadStatus::Contacted->value : LeadStatus::New->value;

            $values[] = $wpdb->prepare(
                '(%s, %s, %s, %s, %d, %d, %s, %s, %s)',
                'enquiry',
                sprintf('Applicant %d', $index),
                sprintf('applicant%d@example.test', $index),
                $status,
                $index % 5,
                $index % 7,
                '',
                self::NOW,
                self::NOW,
            );
        }

        foreach (array_chunk($values, 1000) as $chunk) {
            $wpdb->query(
                "INSERT INTO {$table} (form_id, name, email, status, assigned_to_id, branch_id, source_url,"
                . ' created_at, updated_at) VALUES ' . implode(',', $chunk)
            );
        }

        $startedAt = microtime(true);

        $page = $this->repository->search(LeadQuery::of(LeadStatus::Contacted, page: 40, perPage: 50));
        $total = $this->repository->count(LeadQuery::of(LeadStatus::Contacted));

        $elapsed = microtime(true) - $startedAt;

        $this->assertCount(50, $page);
        $this->assertGreaterThan(3000, $total);
        $this->assertLessThan(self::SLOW_QUERY_SECONDS, $elapsed, 'Filtering the inbox took too long.');
    }

    #[Test]
    public function it_ignores_a_sort_column_that_is_not_on_the_allowlist(): void
    {
        $this->repository->save($this->capture('Amina Rahman', 'amina@example.test'), self::NOW);

        $found = $this->repository->search(LeadQuery::of(sortColumn: 'name; DROP TABLE wp_posts; --'));

        $this->assertCount(1, $found);
    }
}
