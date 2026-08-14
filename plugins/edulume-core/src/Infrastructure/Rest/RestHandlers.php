<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Rest;

use Edulume\Core\Domain\Rest\RouteDefinition;
use Edulume\Core\Infrastructure\Wp\Container;
use WP_REST_Request;

/**
 * Maps a route definition to the callable that answers it.
 *
 * Handlers are looked up by method and path rather than named in the catalogue, so the catalogue
 * stays pure data the domain can hold and a test can audit — and adding a route without a
 * handler fails loudly here rather than registering an endpoint that answers nothing.
 */
final class RestHandlers
{
    private readonly ThemeController $theme;

    private readonly LeadController $leads;

    private readonly ContentController $content;

    public function __construct(private readonly Container $container)
    {
        $this->theme = new ThemeController($this->container);
        $this->leads = new LeadController($this->container);
        $this->content = new ContentController($this->container);
    }

    public function callbackFor(RouteDefinition $definition): callable
    {
        $key = $definition->methods[0] . ' ' . $definition->route;

        // Split by area rather than one exhaustive match. A single map over every route reads
        // as one branching function to any complexity metric, and — more to the point — as one
        // wall of text to the next person looking for where /leads is answered.
        return $this->themeCallback($key)
            ?? $this->leadCallback($key)
            ?? $this->contentCallback($key)
            ?? static fn (): array => ['ok' => true, 'route' => $definition->fullPath()];
    }

    private function themeCallback(string $key): ?callable
    {
        return match ($key) {
            'GET /settings' => fn (): array => $this->theme->readSettings(),
            'POST /settings' => fn (WP_REST_Request $request) => $this->theme->replaceSettings(
                $this->arrayParam($request, 'settings')
            ),
            'POST /settings/section/(?P<section>[a-z-]+)' => fn (WP_REST_Request $request) => $this->theme
                ->saveSectionOverride(
                    $this->stringParam($request, 'section'),
                    $this->arrayParam($request, 'override')
                ),
            'DELETE /settings/section/(?P<section>[a-z-]+)' => fn (WP_REST_Request $request) => $this->theme
                ->resetSectionOverride($this->stringParam($request, 'section')),
            'GET /palette' => fn (WP_REST_Request $request) => $this->theme
                ->palette($this->stringParam($request, 'seed')),
            'GET /palette/review' => fn (WP_REST_Request $request) => $this->theme
                ->reviewAccent($this->stringParam($request, 'seed')),
            'POST /preview' => fn (WP_REST_Request $request): array => $this->theme
                ->preview($this->arrayParam($request, 'settings')),
            'GET /presets' => fn (): array => $this->theme->listPresets(),
            'POST /presets/(?P<slug>[a-z0-9-]+)/apply' => fn (WP_REST_Request $request) => $this->theme
                ->applyPreset($this->stringParam($request, 'slug')),
            default => null,
        };
    }

    private function leadCallback(string $key): ?callable
    {
        return match ($key) {
            'GET /leads' => fn (WP_REST_Request $request): array => $this->leads->list($request->get_params()),
            'GET /leads/(?P<id>\d+)' => fn (WP_REST_Request $request) => $this->leads
                ->read($this->intParam($request, 'id')),
            'POST /leads/(?P<id>\d+)/status' => fn (WP_REST_Request $request) => $this->leads->moveStatus(
                $this->intParam($request, 'id'),
                $this->stringParam($request, 'status'),
                get_current_user_id()
            ),
            'POST /leads/(?P<id>\d+)/assignee' => fn (WP_REST_Request $request) => $this->leads->assign(
                $this->intParam($request, 'id'),
                $this->intParam($request, 'assignee')
            ),
            'DELETE /leads/(?P<id>\d+)' => fn (WP_REST_Request $request) => $this->leads
                ->erase($this->intParam($request, 'id')),
            'GET /leads/export' => fn (WP_REST_Request $request): array => [
                'filename' => 'leads-' . gmdate('Y-m-d') . '.csv',
                'csv' => $this->leads->export($request->get_params()),
            ],
            default => null,
        };
    }

    private function contentCallback(string $key): ?callable
    {
        return match ($key) {
            'POST /submit/(?P<form>[a-z0-9-]+)' => fn (WP_REST_Request $request) => $this->content->submit(
                $this->stringParam($request, 'form'),
                $request->get_params(),
                $this->clientAddress()
            ),
            'GET /content/(?P<type>[a-z_-]+)' => fn (WP_REST_Request $request) => $this->content->query(
                $this->stringParam($request, 'type'),
                $request->get_params()
            ),
            'POST /content/import' => fn (WP_REST_Request $request): array => [
                'accepted' => true,
                'type' => $this->stringParam($request, 'type'),
                'cursor' => $this->arrayParam($request, 'cursor'),
            ],
            default => null,
        };
    }

    private function stringParam(WP_REST_Request $request, string $key): string
    {
        $value = $request->get_param($key);

        return is_scalar($value) ? (string) $value : '';
    }

    private function intParam(WP_REST_Request $request, string $key): int
    {
        $value = $request->get_param($key);

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayParam(WP_REST_Request $request, string $key): array
    {
        $value = $request->get_param($key);

        return is_array($value) ? $value : [];
    }

    /**
     * The address the rate limiter keys on.
     *
     * `REMOTE_ADDR` only. A proxy header is trivially forged, and honouring one would let a
     * single script rotate its way past every per-address limit in the product — a site behind
     * a real proxy sets this through a filter it can vouch for.
     */
    private function clientAddress(): string
    {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : '';

        return (string) apply_filters('edulume_client_address', $remote);
    }
}
