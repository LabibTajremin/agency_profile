<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Lead;

use Edulume\Core\Domain\Lead\LeadQuery;
use Edulume\Core\Domain\Lead\LeadStatus;
use Edulume\Core\Domain\Lead\LeadTableSchema;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeadQueryTest extends TestCase
{
    #[Test]
    public function it_defaults_to_the_newest_leads_first(): void
    {
        $query = LeadQuery::all();

        $this->assertNull($query->status);
        $this->assertSame('created_at', $query->sortColumn);
        $this->assertTrue($query->isDescending);
        $this->assertSame(LeadQuery::DEFAULT_PER_PAGE, $query->perPage);
    }

    #[Test]
    public function it_never_lets_a_caller_ask_for_every_lead(): void
    {
        $this->assertSame(LeadQuery::MAXIMUM_PER_PAGE, LeadQuery::of(perPage: 100000)->perPage);
        $this->assertSame(1, LeadQuery::of(perPage: 0)->perPage);
        $this->assertSame(1, LeadQuery::of(perPage: -1)->perPage);
    }

    #[Test]
    public function it_computes_the_offset_from_the_page(): void
    {
        $this->assertSame(0, LeadQuery::of(page: 1, perPage: 25)->offset());
        $this->assertSame(50, LeadQuery::of(page: 3, perPage: 25)->offset());
        $this->assertSame(0, LeadQuery::of(page: -4, perPage: 25)->offset());
    }

    #[Test]
    public function it_composes_filters(): void
    {
        $query = LeadQuery::of(LeadStatus::Contacted, 12, 7, '  amina ');

        $this->assertSame(LeadStatus::Contacted, $query->status);
        $this->assertSame(12, $query->assignedToId);
        $this->assertSame(7, $query->branchId);
        $this->assertSame('amina', $query->searchTerm);
    }

    #[Test]
    public function it_clamps_a_negative_identifier_rather_than_querying_for_one(): void
    {
        $query = LeadQuery::of(assignedToId: -3, branchId: -9);

        $this->assertSame(0, $query->assignedToId);
        $this->assertSame(0, $query->branchId);
    }

    #[Test]
    public function it_scopes_a_query_to_one_counsellor_whatever_was_asked_for(): void
    {
        $scoped = LeadQuery::of(assignedToId: 12)->scopedToCounsellor(44);

        $this->assertSame(44, $scoped->assignedToId);
    }

    #[Test]
    public function it_pages_without_losing_the_filters(): void
    {
        $query = LeadQuery::of(LeadStatus::New, 12, 7, 'amina', 1, 50, 'name', false)->forPage(4);

        $this->assertSame(4, $query->page);
        $this->assertSame(LeadStatus::New, $query->status);
        $this->assertSame('amina', $query->searchTerm);
        $this->assertSame('name', $query->sortColumn);
        $this->assertFalse($query->isDescending);
        $this->assertSame(150, $query->offset());
        $this->assertSame(1, $query->forPage(-2)->page);
    }

    #[Test]
    public function it_indexes_the_columns_the_inbox_actually_filters_on(): void
    {
        $sql = LeadTableSchema::leadsTableSql('wp_', 'DEFAULT CHARSET=utf8mb4');

        $this->assertStringContainsString('KEY status_created_at (status, created_at)', $sql);
        $this->assertStringContainsString('KEY assigned_to_id (assigned_to_id, status)', $sql);
        $this->assertStringContainsString('KEY branch_id (branch_id, status)', $sql);
        $this->assertStringContainsString('KEY email (email)', $sql);
    }

    #[Test]
    public function it_names_the_tables_with_the_site_prefix(): void
    {
        $this->assertSame(
            ['wp_edulume_leads', 'wp_edulume_lead_meta'],
            LeadTableSchema::tableNames('wp_'),
        );
        $this->assertCount(2, LeadTableSchema::allTableSql('wp_', ''));
    }

    #[Test]
    public function it_keeps_leads_out_of_the_posts_table(): void
    {
        $sql = implode("\n", LeadTableSchema::allTableSql('wp_', ''));

        $this->assertStringNotContainsString('wp_posts', $sql);
        $this->assertStringContainsString('wp_edulume_leads', $sql);
    }
}
