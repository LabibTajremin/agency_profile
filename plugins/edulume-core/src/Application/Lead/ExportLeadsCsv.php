<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Lead;

use Edulume\Core\Application\Port\LeadRepository;
use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadQuery;

/**
 * Exports exactly the leads the screen was showing.
 *
 * The export runs the same query the list ran, page by page rather than all at once, so a
 * filtered export of a 50,000-lead inbox is a stream rather than a memory limit. An export
 * that quietly returns everything when the screen said "Contacted, Branch 3" is a data
 * protection incident, not a convenience.
 */
final class ExportLeadsCsv
{
    private const LINE_ENDING = "\r\n";
    private const EXPORT_PAGE_SIZE = 200;

    private const COLUMNS = ['ID', 'Name', 'Email', 'Phone', 'Status', 'Assigned To', 'Branch', 'Form', 'Created'];

    public function __construct(private readonly LeadRepository $leadRepository)
    {
    }

    public function __invoke(LeadQuery $query, ?int $restrictToCounsellorId = null): string
    {
        $scoped = ($restrictToCounsellorId === null ? $query : $query->scopedToCounsellor($restrictToCounsellorId))
            ->forPage(1);

        $csv = $this->line(self::COLUMNS);
        $page = 1;

        while (true) {
            $leads = $this->leadRepository->search(self::pageOf($scoped, $page));

            if ($leads === []) {
                break;
            }

            foreach ($leads as $lead) {
                $csv .= $this->line($this->cellsFor($lead));
            }

            $page++;
        }

        return $csv;
    }

    private static function pageOf(LeadQuery $query, int $page): LeadQuery
    {
        return LeadQuery::of(
            $query->status,
            $query->assignedToId,
            $query->branchId,
            $query->searchTerm,
            $page,
            self::EXPORT_PAGE_SIZE,
            $query->sortColumn,
            $query->isDescending,
        );
    }

    /**
     * @return list<string>
     */
    private function cellsFor(Lead $lead): array
    {
        return [
            (string) $lead->id,
            $lead->name,
            $lead->email,
            $lead->phone,
            $lead->status->label(),
            (string) $lead->assignedToId,
            (string) $lead->branchId,
            $lead->formId,
            $lead->createdAt,
        ];
    }

    /**
     * @param list<string> $cells
     */
    private function line(array $cells): string
    {
        return implode(',', array_map([$this, 'escape'], $cells)) . self::LINE_ENDING;
    }

    private function escape(string $value): string
    {
        if (preg_match('/[",\r\n]/', $value) !== 1) {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }
}
