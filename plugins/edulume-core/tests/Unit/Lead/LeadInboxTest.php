<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Lead;

use Edulume\Core\Application\Lead\ExportLeadsCsv;
use Edulume\Core\Application\Lead\ListLeads;
use Edulume\Core\Application\Lead\MoveLeadThroughPipeline;
use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Lead\AutoresponderTemplate;
use Edulume\Core\Domain\Lead\IllegalLeadTransitionException;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadQuery;
use Edulume\Core\Domain\Lead\LeadStatus;
use Edulume\Core\Domain\Lead\NotificationRecipients;
use Edulume\Core\Domain\Theming\AccentLibrary;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Tests\Unit\Fake\FixedClock;
use Edulume\Core\Tests\Unit\Fake\InMemoryLeadRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeadInboxTest extends TestCase
{
    private const NOW = '2026-03-01T09:00:00+00:00';

    private InMemoryLeadRepository $leads;

    protected function setUp(): void
    {
        $this->leads = new InMemoryLeadRepository();
    }

    private function pipeline(): MoveLeadThroughPipeline
    {
        return new MoveLeadThroughPipeline($this->leads, new FixedClock(self::NOW, 1772355600));
    }

    private function seed(string $name, int $branchId = 0, int $assignedToId = 0): int
    {
        $lead = Lead::captured($name, strtolower($name) . '@example.test', '+8801700000000', 'enquiry', [], self::NOW, $branchId);

        $id = $this->leads->save($lead, self::NOW);

        if ($assignedToId > 0) {
            $this->pipeline()->assignTo($id, $assignedToId);
        }

        return $id;
    }

    #[Test]
    public function it_composes_filters_across_status_assignee_and_branch(): void
    {
        $this->seed('Amina', 7, 12);
        $this->seed('Karim', 9);
        $this->pipeline()->moveTo(1, LeadStatus::Contacted, 3);

        $list = new ListLeads($this->leads);

        $this->assertCount(1, $list(LeadQuery::of(LeadStatus::Contacted))->leads);
        $this->assertCount(1, $list(LeadQuery::of(assignedToId: 12))->leads);
        $this->assertCount(1, $list(LeadQuery::of(branchId: 9))->leads);
        $this->assertCount(0, $list(LeadQuery::of(LeadStatus::Contacted, branchId: 9))->leads);
    }

    #[Test]
    public function it_reports_enough_to_render_pagination(): void
    {
        for ($index = 1; $index <= 7; $index++) {
            $this->seed('Applicant' . $index);
        }

        $page = (new ListLeads($this->leads))(LeadQuery::of(perPage: 3));

        $this->assertCount(3, $page->leads);
        $this->assertSame(7, $page->totalCount);
        $this->assertSame(3, $page->pageCount());
        $this->assertTrue($page->hasNextPage());
        $this->assertFalse($page->isEmpty());
    }

    #[Test]
    public function it_reports_an_empty_inbox_without_pretending_there_are_pages(): void
    {
        $page = (new ListLeads($this->leads))(LeadQuery::all());

        $this->assertTrue($page->isEmpty());
        $this->assertSame(1, $page->pageCount());
        $this->assertFalse($page->hasNextPage());
    }

    /**
     * A counsellor's scope is applied after whatever the request asked for, so a hand-edited
     * query string cannot widen it.
     */
    #[Test]
    public function it_shows_a_counsellor_only_their_own_leads_whatever_was_requested(): void
    {
        $this->seed('Amina', 0, 12);
        $this->seed('Karim', 0, 44);

        $page = (new ListLeads($this->leads))(LeadQuery::of(assignedToId: 12), 44);

        $this->assertCount(1, $page->leads);
        $this->assertSame('Karim', $page->leads[0]->name);
        $this->assertSame(44, $page->query->assignedToId);
    }

    #[Test]
    public function it_moves_a_lead_along_the_pipeline(): void
    {
        $id = $this->seed('Amina');

        $moved = $this->pipeline()->moveTo($id, LeadStatus::Contacted, 3);

        $this->assertNotNull($moved);
        $this->assertSame(LeadStatus::Contacted, $moved->status);
        $this->assertSame(LeadStatus::Contacted, $this->leads->find($id)?->status);
    }

    #[Test]
    public function it_lets_the_entity_refuse_an_illegal_move(): void
    {
        $id = $this->seed('Amina');

        $this->expectException(IllegalLeadTransitionException::class);

        $this->pipeline()->moveTo($id, LeadStatus::ClosedWon, 3);
    }

    #[Test]
    public function it_assigns_and_annotates(): void
    {
        $id = $this->seed('Amina');

        $this->pipeline()->assignTo($id, 12);
        $annotated = $this->pipeline()->annotate($id, 4, 'Family called back.');

        $this->assertNotNull($annotated);
        $this->assertTrue($annotated->isAssigned());
        $this->assertSame('Family called back.', $annotated->notes[1]->body);
    }

    #[Test]
    public function it_reports_nothing_changed_for_a_lead_that_does_not_exist(): void
    {
        $this->assertNull($this->pipeline()->moveTo(404, LeadStatus::Contacted, 3));
        $this->assertNull($this->pipeline()->assignTo(404, 12));
        $this->assertNull($this->pipeline()->annotate(404, 4, 'x'));
    }

    #[Test]
    public function it_exports_exactly_what_the_filters_selected(): void
    {
        $this->seed('Amina', 7);
        $this->seed('Karim', 9);

        $csv = (new ExportLeadsCsv($this->leads))(LeadQuery::of(branchId: 9));

        $this->assertStringContainsString('Karim', $csv);
        $this->assertStringNotContainsString('Amina', $csv);
        $this->assertStringStartsWith('ID,Name,Email,Phone,Status,Assigned To,Branch,Form,Created', $csv);
    }

    #[Test]
    public function it_exports_every_matching_page_not_just_the_first(): void
    {
        for ($index = 1; $index <= 450; $index++) {
            $this->seed('Applicant' . $index);
        }

        $csv = (new ExportLeadsCsv($this->leads))(LeadQuery::of(perPage: 10));

        $this->assertSame(451, substr_count($csv, "\r\n"));
    }

    #[Test]
    public function it_scopes_an_export_to_the_counsellor_running_it(): void
    {
        $this->seed('Amina', 0, 12);
        $this->seed('Karim', 0, 44);

        $csv = (new ExportLeadsCsv($this->leads))(LeadQuery::all(), 44);

        $this->assertStringContainsString('Karim', $csv);
        $this->assertStringNotContainsString('Amina', $csv);
    }

    #[Test]
    public function it_escapes_a_name_containing_a_comma(): void
    {
        $this->leads->save(
            Lead::captured('Rahman, Amina', 'a@example.test', '', 'enquiry', [], self::NOW),
            self::NOW,
        );

        $this->assertStringContainsString('"Rahman, Amina"', (new ExportLeadsCsv($this->leads))(LeadQuery::all()));
    }

    #[Test]
    public function it_notifies_the_site_the_form_and_the_branch(): void
    {
        $recipients = NotificationRecipients::of(
            ['office@example.test'],
            ['counselling-enquiry' => ['counsellors@example.test']],
            [7 => ['dhaka@example.test']],
        );

        $this->assertSame(
            ['office@example.test', 'counsellors@example.test', 'dhaka@example.test'],
            $recipients->for('counselling-enquiry', 7),
        );
    }

    #[Test]
    public function it_never_emails_the_same_person_twice_or_a_broken_address(): void
    {
        $recipients = NotificationRecipients::of(
            ['office@example.test', ' OFFICE@example.test ', 'not-an-address', ''],
            [],
            [7 => ['office@example.test']],
        );

        $this->assertSame(['office@example.test'], $recipients->for('any', 7));
    }

    #[Test]
    public function it_falls_back_to_the_site_addresses_for_an_unknown_form_or_branch(): void
    {
        $recipients = NotificationRecipients::of(['office@example.test'], [], [7 => ['dhaka@example.test']]);

        $this->assertSame(['office@example.test'], $recipients->for('some-other-form', 99));
    }

    #[Test]
    public function it_inlines_the_sites_accent_into_the_autoresponder(): void
    {
        $palette = (new PaletteGenerator(new ContrastEngine()))->generate(AccentLibrary::get('oxford-blue')->seed);
        $template = new AutoresponderTemplate($palette, 'Edulume Consultancy');

        $html = $template->render(
            Lead::captured('Amina Rahman', 'a@example.test', '', 'enquiry', [], self::NOW),
            'Thanks {{name}}, {{site}} will be in touch.',
        );

        $this->assertStringContainsString($palette->fill(\Edulume\Core\Domain\Theming\ThemeMode::Light)->toHex(), $html);
        $this->assertStringContainsString('Thanks Amina Rahman, Edulume Consultancy will be in touch.', $html);
        $this->assertStringContainsString('Edulume Consultancy', $html);
    }

    #[Test]
    public function it_escapes_what_the_visitor_typed_into_the_autoresponder(): void
    {
        $palette = (new PaletteGenerator(new ContrastEngine()))->generate(AccentLibrary::get('oxford-blue')->seed);

        $html = (new AutoresponderTemplate($palette, 'Edulume'))->render(
            Lead::captured('<script>alert(1)</script>', 'a@example.test', '', 'enquiry', [], self::NOW),
            'Hello {{name}}.',
        );

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    #[Test]
    public function it_lists_the_placeholders_an_editor_may_use(): void
    {
        $palette = (new PaletteGenerator(new ContrastEngine()))->generate(AccentLibrary::get('oxford-blue')->seed);

        $this->assertSame(
            ['{{name}}', '{{site}}', '{{body}}'],
            (new AutoresponderTemplate($palette, 'Edulume'))->placeholders(),
        );
    }
}
