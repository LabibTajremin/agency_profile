<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Rest;

use Edulume\Core\Domain\Lead\Lead;
use Edulume\Core\Domain\Lead\LeadQuery;
use Edulume\Core\Domain\Lead\LeadStatus;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;
use WP_Error;

/**
 * The lead half of the REST API.
 *
 * The counsellor scope is applied here, to the query, not to the rendered list. A scope applied
 * after the rows come back is a scope the next endpoint forgets — and this endpoint returns JSON
 * to anyone who can call it, so forgetting means handing one counsellor another's pipeline.
 */
final class LeadController
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    public function list(array $parameters): array
    {
        $page = ($this->container->listLeads())($this->queryFrom($parameters), $this->counsellorScope());

        return [
            'leads' => array_map(static fn (Lead $lead): array => $lead->toArray(), $page->leads),
            'total' => $page->totalCount,
            'page' => $page->query->page,
            'pages' => $page->pageCount(),
            'hasMore' => $page->hasNextPage(),
        ];
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function read(int $id)
    {
        $lead = $this->container->leadRepository()->find($id);

        if ($lead === null || !$this->isVisible($lead)) {
            // The same answer for "does not exist" and "not yours". Distinguishing them turns
            // the endpoint into a way of enumerating which lead IDs exist.
            return $this->notFound();
        }

        return $lead->toArray();
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function moveStatus(int $id, string $status, int $actorId)
    {
        $target = LeadStatus::tryFrom($status);

        if ($target === null) {
            return new WP_Error('edulume_unknown_status', __('That is not a pipeline status.', 'edulume'), ['status' => 400]);
        }

        $existing = $this->container->leadRepository()->find($id);

        if ($existing === null || !$this->isVisible($existing)) {
            return $this->notFound();
        }

        $moved = $this->container->moveLeadThroughPipeline()->moveTo($id, $target, $actorId);

        if ($moved === null) {
            // The domain refused the transition. Reported as a conflict rather than a 400: the
            // request was well-formed, the pipeline simply does not go that way.
            return new WP_Error(
                'edulume_illegal_transition',
                __('A lead cannot move to that status from where it is.', 'edulume'),
                ['status' => 409]
            );
        }

        return $moved->toArray();
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function assign(int $id, int $assigneeId)
    {
        $existing = $this->container->leadRepository()->find($id);

        if ($existing === null || !$this->isVisible($existing)) {
            return $this->notFound();
        }

        $assigned = $this->container->moveLeadThroughPipeline()->assignTo($id, $assigneeId);

        return $assigned === null ? $this->notFound() : $assigned->toArray();
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function erase(int $id)
    {
        $existing = $this->container->leadRepository()->find($id);

        if ($existing === null || !$this->isVisible($existing)) {
            return $this->notFound();
        }

        // Erasure removes the uploaded files too. A row deleted while its transcript stays on
        // disk is not an erasure, whatever the confirmation dialog said.
        $erased = ($this->container->eraseLead())($id);

        return ['erased' => $erased, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function export(array $parameters): string
    {
        return ($this->container->exportLeadsCsv())($this->queryFrom($parameters), $this->counsellorScope());
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function queryFrom(array $parameters): LeadQuery
    {
        $status = is_string($parameters['status'] ?? null) ? LeadStatus::tryFrom($parameters['status']) : null;

        return LeadQuery::of(
            $status,
            null,
            isset($parameters['branch']) && is_numeric($parameters['branch']) ? (int) $parameters['branch'] : null,
            is_string($parameters['search'] ?? null) ? $parameters['search'] : '',
            isset($parameters['page']) && is_numeric($parameters['page']) ? (int) $parameters['page'] : 1,
            isset($parameters['per_page']) && is_numeric($parameters['per_page']) ? (int) $parameters['per_page'] : 25,
        );
    }

    /**
     * The current user's id when they are scoped to their own leads, or null when they see all.
     */
    private function counsellorScope(): ?int
    {
        return current_user_can(Capabilities::MANAGE_ALL_LEADS) ? null : get_current_user_id();
    }

    private function isVisible(Lead $lead): bool
    {
        $scope = $this->counsellorScope();

        return $scope === null || $lead->isVisibleTo($scope);
    }

    private function notFound(): WP_Error
    {
        return new WP_Error('edulume_lead_not_found', __('No such lead.', 'edulume'), ['status' => 404]);
    }
}
