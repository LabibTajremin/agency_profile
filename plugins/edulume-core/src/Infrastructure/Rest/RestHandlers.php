<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Rest;

use Edulume\Core\Domain\Rest\RouteDefinition;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * Maps a route definition to the callable that answers it.
 *
 * Handlers are looked up by path and method rather than named in the catalogue, so the
 * catalogue stays pure data that the domain can hold and a test can audit.
 */
final class RestHandlers
{
    public function __construct(private readonly Container $container)
    {
    }

    public function callbackFor(RouteDefinition $definition): callable
    {
        $key = $definition->methods[0] . ' ' . $definition->route;
        $container = $this->container;

        return match ($key) {
            'GET /settings' => static fn (): array => $container->settingsRepository()->load()->toArray(),
            'GET /presets' => static fn (): array => array_map(
                static fn ($preset): array => $preset->toArray(),
                \Edulume\Core\Domain\Theming\StylePresetLibrary::all(),
            ),
            default => static fn (): array => ['ok' => true, 'route' => $definition->fullPath()],
        };
    }
}
