<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Rest;

use Edulume\Core\Infrastructure\Wp\Capabilities;

/**
 * Every route the plugin exposes, in one place.
 *
 * Having the catalogue as data rather than as scattered `register_rest_route` calls is what
 * lets a test assert the properties that matter across all of them at once: every route has a
 * schema, every write route demands a capability, and nothing is public by accident.
 */
final class RouteCatalogue
{
    private const HEX_PATTERN = '^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$';

    /**
     * @return list<RouteDefinition>
     */
    public static function all(): array
    {
        return [
            RouteDefinition::of('/settings', ['GET'], Capabilities::MANAGE_THEME, 'Read the theme settings.', [
                'context' => ['type' => 'string', 'enum' => ['view', 'edit'], 'default' => 'view'],
            ]),
            RouteDefinition::of('/settings', ['POST'], Capabilities::MANAGE_THEME, 'Replace the theme settings.', [
                'settings' => ['type' => 'object', 'required' => true],
            ]),
            RouteDefinition::of(
                '/settings/section/(?P<section>[a-z-]+)',
                ['POST'],
                Capabilities::MANAGE_THEME,
                'Save one section override.',
                [
                'section' => ['type' => 'string', 'required' => true, 'pattern' => '^[a-z-]+$'],
                'override' => ['type' => 'object', 'required' => true],
                ]
            ),
            RouteDefinition::of(
                '/settings/section/(?P<section>[a-z-]+)',
                ['DELETE'],
                Capabilities::MANAGE_THEME,
                'Reset one section to inherit.',
                [
                'section' => ['type' => 'string', 'required' => true, 'pattern' => '^[a-z-]+$'],
                ]
            ),
            RouteDefinition::of('/palette', ['GET'], Capabilities::MANAGE_THEME, 'Generate a palette from a seed colour.', [
                'seed' => ['type' => 'string', 'required' => true, 'pattern' => self::HEX_PATTERN],
            ]),
            RouteDefinition::of('/palette/review', ['GET'], Capabilities::MANAGE_THEME, 'Review a custom accent for contrast.', [
                'seed' => ['type' => 'string', 'required' => true, 'pattern' => self::HEX_PATTERN],
            ]),
            RouteDefinition::of('/preview', ['POST'], Capabilities::MANAGE_THEME, 'Compile a stylesheet without saving it.', [
                'settings' => ['type' => 'object', 'required' => true],
            ]),
            RouteDefinition::of('/presets', ['GET'], Capabilities::MANAGE_THEME, 'List the style presets.'),
            RouteDefinition::of(
                '/presets/(?P<slug>[a-z0-9-]+)/apply',
                ['POST'],
                Capabilities::MANAGE_THEME,
                'Apply a style preset.',
                [
                'slug' => ['type' => 'string', 'required' => true, 'pattern' => '^[a-z0-9-]+$'],
                ]
            ),
            RouteDefinition::of('/leads', ['GET'], Capabilities::MANAGE_LEADS, 'List leads.', [
                'status' => ['type' => 'string', 'enum' => [
                    'new', 'contacted', 'counselling-booked', 'application-started', 'closed-won', 'closed-lost',
                ]],
                'branch' => ['type' => 'integer', 'minimum' => 0],
                'search' => ['type' => 'string'],
                'page' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 200, 'default' => 25],
            ]),
            RouteDefinition::of('/leads/(?P<id>\d+)', ['GET'], Capabilities::MANAGE_LEADS, 'Read one lead.', [
                'id' => ['type' => 'integer', 'required' => true, 'minimum' => 1],
            ]),
            RouteDefinition::of('/leads/(?P<id>\d+)/status', ['POST'], Capabilities::MANAGE_LEADS, 'Move a lead along the pipeline.', [
                'id' => ['type' => 'integer', 'required' => true, 'minimum' => 1],
                'status' => ['type' => 'string', 'required' => true],
            ]),
            RouteDefinition::of('/leads/(?P<id>\d+)/assignee', ['POST'], Capabilities::MANAGE_LEADS, 'Assign a lead.', [
                'id' => ['type' => 'integer', 'required' => true, 'minimum' => 1],
                'assignee' => ['type' => 'integer', 'required' => true, 'minimum' => 0],
            ]),
            RouteDefinition::of('/leads/(?P<id>\d+)', ['DELETE'], Capabilities::MANAGE_LEADS, 'Erase a lead and its uploads.', [
                'id' => ['type' => 'integer', 'required' => true, 'minimum' => 1],
            ]),
            RouteDefinition::of('/leads/export', ['GET'], Capabilities::EXPORT_LEADS, 'Export the filtered leads as CSV.', [
                'status' => ['type' => 'string'],
                'branch' => ['type' => 'integer', 'minimum' => 0],
                'search' => ['type' => 'string'],
            ]),
            RouteDefinition::of('/submit/(?P<form>[a-z0-9-]+)', ['POST'], RouteDefinition::PUBLIC_CAPABILITY, 'Submit a public form.', [
                'form' => ['type' => 'string', 'required' => true, 'pattern' => '^[a-z0-9-]+$'],
                'answers' => ['type' => 'object', 'required' => true],
                'seconds_on_form' => ['type' => 'integer', 'required' => true, 'minimum' => 0],
                'captcha_token' => ['type' => 'string'],
            ]),
            RouteDefinition::of(
                '/content/(?P<type>[a-z_-]+)',
                ['GET'],
                RouteDefinition::PUBLIC_CAPABILITY,
                'Query published content for the finders.',
                [
                'type' => ['type' => 'string', 'required' => true, 'pattern' => '^[a-z_-]+$'],
                'page' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
                'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 24],
                ]
            ),
            RouteDefinition::of('/content/import', ['POST'], Capabilities::MANAGE_CONTENT, 'Import a chunk of a CSV.', [
                'type' => ['type' => 'string', 'required' => true],
                'cursor' => ['type' => 'object'],
            ]),
        ];
    }

    /**
     * @return list<RouteDefinition>
     */
    public static function publicRoutes(): array
    {
        return array_values(array_filter(self::all(), static fn (RouteDefinition $route): bool => $route->isPublic()));
    }

    /**
     * @return list<RouteDefinition>
     */
    public static function privilegedRoutes(): array
    {
        return array_values(array_filter(self::all(), static fn (RouteDefinition $route): bool => !$route->isPublic()));
    }
}
