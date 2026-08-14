<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Rest;

use Edulume\Core\Domain\Rest\RouteCatalogue;
use Edulume\Core\Domain\Rest\RouteDefinition;

/**
 * Registers every route in the catalogue, deriving the permission callback from the
 * definition rather than trusting each controller to remember one.
 *
 * That is the point of the catalogue: there is no way to add a route without stating who may
 * call it, and no way for a controller to be written without a check.
 */
final class RestRegistrar
{
    public function __construct(private readonly RestHandlers $handlers)
    {
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        foreach (RouteCatalogue::all() as $definition) {
            register_rest_route(RouteDefinition::NAMESPACE, $definition->route, [
                'methods' => implode(', ', $definition->methods),
                'callback' => $this->handlers->callbackFor($definition),
                'permission_callback' => $this->permissionCallbackFor($definition),
                'args' => $this->argsFor($definition),
            ]);
        }
    }

    /**
     * A public route still returns true explicitly. WordPress treats a missing
     * `permission_callback` as "no opinion" and warns about it; saying so out loud is the
     * difference between a deliberate public endpoint and an oversight.
     */
    private function permissionCallbackFor(RouteDefinition $definition): callable
    {
        if ($definition->isPublic()) {
            return static fn (): bool => true;
        }

        $capability = $definition->capability;

        return static fn (): bool => current_user_can($capability);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function argsFor(RouteDefinition $definition): array
    {
        $args = [];

        foreach ($definition->schema as $name => $rules) {
            $args[$name] = array_merge($rules, [
                'validate_callback' => 'rest_validate_request_arg',
                'sanitize_callback' => 'rest_sanitize_request_arg',
            ]);
        }

        return $args;
    }
}
