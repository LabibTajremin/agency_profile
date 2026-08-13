<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Lead;

use Edulume\Core\Domain\Lead\IllegalLeadTransitionException;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadNote;
use Edulume\Core\Domain\Lead\LeadStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LeadTest extends TestCase
{
    private const CAPTURED_AT = '2026-03-01T09:00:00+00:00';
    private const MOVED_AT = '2026-03-02T11:30:00+00:00';

    private static function lead(): Lead
    {
        return Lead::captured(
            '  Amina Rahman  ',
            'amina@example.test',
            ' +8801700000000 ',
            'counselling-enquiry',
            ['destination' => 'Canada', 'level' => 'Postgraduate'],
            self::CAPTURED_AT,
            7,
        );
    }

    #[Test]
    public function it_starts_new_and_unassigned(): void
    {
        $lead = self::lead();

        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertFalse($lead->isAssigned());
        $this->assertSame([], $lead->notes);
        $this->assertSame(7, $lead->branchId);
    }

    #[Test]
    public function it_trims_what_the_visitor_typed(): void
    {
        $lead = self::lead();

        $this->assertSame('Amina Rahman', $lead->name);
        $this->assertSame('+8801700000000', $lead->phone);
    }

    #[Test]
    public function it_truncates_a_name_longer_than_the_column(): void
    {
        $lead = Lead::captured(str_repeat('a', 400), 'a@example.test', '', 'f', [], self::CAPTURED_AT);

        $this->assertSame(Lead::MAXIMUM_NAME_LENGTH, mb_strlen($lead->name));
    }

    /**
     * @return array<string, array{LeadStatus, LeadStatus}>
     */
    public static function legalTransitionProvider(): array
    {
        return [
            'new to contacted' => [LeadStatus::New, LeadStatus::Contacted],
            'new to closed lost' => [LeadStatus::New, LeadStatus::ClosedLost],
            'contacted to counselling' => [LeadStatus::Contacted, LeadStatus::CounsellingBooked],
            'counselling to application' => [LeadStatus::CounsellingBooked, LeadStatus::ApplicationStarted],
            'application to closed won' => [LeadStatus::ApplicationStarted, LeadStatus::ClosedWon],
            'application to closed lost' => [LeadStatus::ApplicationStarted, LeadStatus::ClosedLost],
        ];
    }

    #[Test]
    #[DataProvider('legalTransitionProvider')]
    public function it_allows_a_legal_move(LeadStatus $from, LeadStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    /**
     * @return array<string, array{LeadStatus, LeadStatus}>
     */
    public static function illegalTransitionProvider(): array
    {
        return [
            'skipping contact' => [LeadStatus::New, LeadStatus::CounsellingBooked],
            'winning without an application' => [LeadStatus::Contacted, LeadStatus::ClosedWon],
            'winning straight from new' => [LeadStatus::New, LeadStatus::ClosedWon],
            'reopening a win' => [LeadStatus::ClosedWon, LeadStatus::Contacted],
            'reopening a loss' => [LeadStatus::ClosedLost, LeadStatus::New],
            'going backwards' => [LeadStatus::ApplicationStarted, LeadStatus::Contacted],
        ];
    }

    #[Test]
    #[DataProvider('illegalTransitionProvider')]
    public function it_refuses_an_illegal_move(LeadStatus $from, LeadStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
    }

    #[Test]
    public function it_walks_the_whole_pipeline_to_a_win(): void
    {
        $lead = self::lead()
            ->movedTo(LeadStatus::Contacted, 3, self::MOVED_AT)
            ->movedTo(LeadStatus::CounsellingBooked, 3, self::MOVED_AT)
            ->movedTo(LeadStatus::ApplicationStarted, 3, self::MOVED_AT)
            ->movedTo(LeadStatus::ClosedWon, 3, self::MOVED_AT);

        $this->assertSame(LeadStatus::ClosedWon, $lead->status);
        $this->assertCount(4, $lead->notes);
        $this->assertTrue($lead->status->isClosed());
    }

    #[Test]
    public function it_records_every_move_in_the_note_trail(): void
    {
        $lead = self::lead()->movedTo(LeadStatus::Contacted, 3, self::MOVED_AT);

        $this->assertCount(1, $lead->notes);
        $this->assertTrue($lead->notes[0]->isSystemEntry);
        $this->assertSame('Moved from New to Contacted.', $lead->notes[0]->body);
        $this->assertSame(self::MOVED_AT, $lead->notes[0]->recordedAt);
    }

    #[Test]
    public function it_rejects_an_illegal_move_at_the_entity(): void
    {
        $this->expectException(IllegalLeadTransitionException::class);

        self::lead()->movedTo(LeadStatus::ClosedWon, 3, self::MOVED_AT);
    }

    #[Test]
    public function it_refuses_to_reopen_a_closed_lead(): void
    {
        $closed = self::lead()->movedTo(LeadStatus::ClosedLost, 3, self::MOVED_AT);

        $this->expectExceptionMessage('cannot be reopened');

        $closed->movedTo(LeadStatus::Contacted, 3, self::MOVED_AT);
    }

    #[Test]
    public function it_treats_a_move_to_the_current_status_as_nothing_happening(): void
    {
        $lead = self::lead();

        $this->assertSame($lead, $lead->movedTo(LeadStatus::New, 3, self::MOVED_AT));
    }

    #[Test]
    public function it_records_an_assignment(): void
    {
        $lead = self::lead()->assignedTo(12, self::MOVED_AT);

        $this->assertTrue($lead->isAssigned());
        $this->assertTrue($lead->isVisibleTo(12));
        $this->assertFalse($lead->isVisibleTo(13));
        $this->assertSame('Assigned to user 12.', $lead->notes[0]->body);
    }

    #[Test]
    public function it_records_an_assignment_being_cleared(): void
    {
        $lead = self::lead()->assignedTo(12, self::MOVED_AT)->assignedTo(0, self::MOVED_AT);

        $this->assertFalse($lead->isAssigned());
        $this->assertSame('Assignment cleared.', $lead->notes[1]->body);
    }

    #[Test]
    public function it_treats_reassignment_to_the_same_person_as_nothing_happening(): void
    {
        $lead = self::lead()->assignedTo(12, self::MOVED_AT);

        $this->assertSame($lead, $lead->assignedTo(12, self::MOVED_AT));
    }

    #[Test]
    public function it_appends_a_written_note(): void
    {
        $lead = self::lead()->annotated(LeadNote::written(4, '  Called, will call back Tuesday. ', self::MOVED_AT));

        $this->assertCount(1, $lead->notes);
        $this->assertFalse($lead->notes[0]->isSystemEntry);
        $this->assertSame('Called, will call back Tuesday.', $lead->notes[0]->body);
        $this->assertSame(4, $lead->notes[0]->authorId);
    }

    #[Test]
    public function it_round_trips_through_storage(): void
    {
        $lead = self::lead()
            ->movedTo(LeadStatus::Contacted, 3, self::MOVED_AT)
            ->assignedTo(12, self::MOVED_AT)
            ->annotated(LeadNote::written(4, 'Spoke to the family.', self::MOVED_AT));

        $this->assertSame($lead->toArray(), Lead::fromArray($lead->toArray())->toArray());
    }

    #[Test]
    public function it_loads_defaults_from_a_corrupt_row(): void
    {
        $lead = Lead::fromArray(['status' => 'imaginary', 'assignedToId' => -4, 'notes' => 'not a list']);

        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertSame(0, $lead->assignedToId);
        $this->assertSame([], $lead->notes);
    }

    #[Test]
    public function it_labels_every_status(): void
    {
        foreach (LeadStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());
        }

        $this->assertSame([], LeadStatus::ClosedWon->allowedTransitions());
    }

    #[Test]
    public function it_keeps_the_submission_it_was_given(): void
    {
        $this->assertSame(['destination' => 'Canada', 'level' => 'Postgraduate'], self::lead()->submission);
    }
}
