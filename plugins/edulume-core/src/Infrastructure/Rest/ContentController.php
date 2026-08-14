<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Rest;

use Edulume\Core\Domain\Content\ContentModel;
use Edulume\Core\Domain\Finder\FinderCatalogue;
use Edulume\Core\Domain\Finder\FinderQuery;
use Edulume\Core\Domain\Lead\FormPlacement;
use Edulume\Core\Infrastructure\Wp\Container;
use WP_Error;

/**
 * The public content endpoint the finders call, and the form submission endpoint.
 *
 * Both are public routes, which is why every parameter here is either whitelisted against a
 * declared facet or clamped. A public endpoint that passes request parameters into a query is
 * a denial-of-service primitive with a JSON wrapper.
 */
final class ContentController
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>|WP_Error
     */
    public function query(string $postType, array $parameters)
    {
        if (!in_array($postType, $this->publicPostTypes(), true)) {
            return new WP_Error('edulume_unknown_type', __('No such content type.', 'edulume'), ['status' => 404]);
        }

        $query = FinderQuery::fromRequest($postType, FinderCatalogue::facetsFor($postType), $parameters);
        $repository = $this->container->finderRepository();
        $filters = $this->filtersFor($query);

        return [
            'items' => $repository->find($postType, $filters),
            'total' => $repository->count($postType, $filters),
            'page' => $query->page,
            'perPage' => $query->perPage,
            'query' => $query->toQueryParameters(),
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>|WP_Error
     */
    public function submit(string $formSlug, array $parameters, string $ipAddress)
    {
        $form = $this->container->formRepository()->find($formSlug);

        if ($form === null) {
            return new WP_Error('edulume_unknown_form', __('No such form.', 'edulume'), ['status' => 404]);
        }

        $answers = is_array($parameters['answers'] ?? null) ? $parameters['answers'] : [];
        $seconds = is_numeric($parameters['seconds_on_form'] ?? null) ? (int) $parameters['seconds_on_form'] : 0;
        $token = is_string($parameters['captcha_token'] ?? null) ? $parameters['captcha_token'] : '';
        $branchId = is_numeric($parameters['branch'] ?? null) ? (int) $parameters['branch'] : 0;

        $outcome = ($this->container->registerLead())($form, $answers, $ipAddress, $seconds, $token, $branchId);

        if ($outcome->wasSilentlyDiscarded) {
            // Spam gets the same response a success does. Telling a bot it was caught is telling
            // it what to change, and the person on the other end of a false positive at least
            // sees the same screen everyone else does.
            return ['accepted' => true];
        }

        if (!$outcome->isAccepted) {
            return new WP_Error(
                'edulume_submission_rejected',
                $outcome->rejectionReason !== ''
                    ? $outcome->rejectionReason
                    : __('Please check the highlighted fields.', 'edulume'),
                ['status' => 422, 'fields' => $outcome->fieldErrors]
            );
        }

        return ['accepted' => true];
    }

    /**
     * The placements a form may be rendered in, exposed so the block editor can offer them
     * without duplicating the list.
     *
     * @return list<string>
     */
    public function formPlacements(): array
    {
        return array_map(static fn (FormPlacement $placement): string => $placement->value, FormPlacement::cases());
    }

    /**
     * @return list<string>
     */
    private function publicPostTypes(): array
    {
        return array_map(
            static fn ($definition): string => $definition->key,
            ContentModel::postTypes(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFor(FinderQuery $query): array
    {
        return [
            'selections' => $query->selections,
            'search' => $query->search,
            'sort' => $query->sort->value,
            'metaKey' => $query->sort->metaKey(),
            'descending' => $query->sort->isDescending(),
            // Always bounded. `FinderQuery` clamps `perPage` before it gets here, so no request
            // can widen this however it is spelled.
            'perPage' => $query->perPage,
            'offset' => $query->offset(),
        ];
    }
}
