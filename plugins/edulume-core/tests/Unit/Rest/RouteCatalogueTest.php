<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Rest;

use Edulume\Core\Domain\Rest\RouteCatalogue;
use Edulume\Core\Domain\Rest\RouteDefinition;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RouteCatalogueTest extends TestCase
{
    /**
     * @return array<string, array{RouteDefinition}>
     */
    public static function routeProvider(): array
    {
        $cases = [];

        foreach (RouteCatalogue::all() as $route) {
            $cases[implode('|', $route->methods) . ' ' . $route->route] = [$route];
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('routeProvider')]
    public function it_namespaces_every_route(RouteDefinition $route): void
    {
        $this->assertStringStartsWith('/' . RouteDefinition::NAMESPACE . '/', $route->fullPath());
        $this->assertStringStartsWith('/', $route->route);
    }

    #[Test]
    #[DataProvider('routeProvider')]
    public function it_documents_every_route(RouteDefinition $route): void
    {
        $this->assertNotSame('', $route->summary);
        $this->assertNotEmpty($route->methods);
    }

    /**
     * The rule that matters: a nonce proves the request came from the site's own page. It says
     * nothing about whether this user may do this.
     */
    #[Test]
    #[DataProvider('routeProvider')]
    public function it_demands_a_capability_on_every_route_that_is_not_deliberately_public(
        RouteDefinition $route
    ): void {
        if ($route->isPublic()) {
            $this->assertContains(
                $route->route,
                ['/submit/(?P<form>[a-z0-9-]+)', '/content/(?P<type>[a-z_-]+)'],
                'A route was made public that was not on the deliberate list.',
            );

            return;
        }

        $this->assertContains($route->capability, Capabilities::all());
    }

    #[Test]
    #[DataProvider('routeProvider')]
    public function it_gives_every_parameterised_route_a_schema_for_its_parameters(RouteDefinition $route): void
    {
        preg_match_all('/\(\?P<([a-z_]+)>/', $route->route, $matches);

        $this->assertCount(count($matches[1]), $matches[1]);

        foreach ($matches[1] as $parameter) {
            $this->assertArrayHasKey(
                $parameter,
                $route->schema,
                sprintf('%s takes %s in its path but does not describe it.', $route->fullPath(), $parameter),
            );
            $this->assertArrayHasKey('type', $route->schema[$parameter]);
        }
    }

    #[Test]
    #[DataProvider('routeProvider')]
    public function it_types_every_schema_entry(RouteDefinition $route): void
    {
        $this->assertIsArray($route->schema);

        foreach ($route->schema as $name => $rules) {
            $this->assertArrayHasKey('type', $rules, sprintf('%s in %s has no type.', $name, $route->fullPath()));
            $this->assertContains($rules['type'], ['string', 'integer', 'number', 'boolean', 'object', 'array']);
        }
    }

    #[Test]
    public function it_never_exposes_a_write_route_publicly(): void
    {
        $this->assertNotEmpty(RouteCatalogue::publicRoutes());

        foreach (RouteCatalogue::publicRoutes() as $route) {
            if (!$route->acceptsWrites()) {
                continue;
            }

            $this->assertSame(
                '/submit/(?P<form>[a-z0-9-]+)',
                $route->route,
                'The only public write route is the form submission endpoint.',
            );
        }
    }

    #[Test]
    public function it_keeps_every_lead_route_behind_a_lead_capability(): void
    {
        $checked = 0;

        foreach (RouteCatalogue::all() as $route) {
            if (!str_starts_with($route->route, '/leads')) {
                continue;
            }

            $this->assertContains($route->capability, [Capabilities::MANAGE_LEADS, Capabilities::EXPORT_LEADS]);
            $checked++;
        }

        $this->assertGreaterThan(0, $checked);
    }

    #[Test]
    public function it_keeps_every_settings_route_behind_the_theme_capability(): void
    {
        $checked = 0;

        foreach (RouteCatalogue::all() as $route) {
            if (!str_starts_with($route->route, '/settings') && !str_starts_with($route->route, '/preview')) {
                continue;
            }

            $this->assertSame(Capabilities::MANAGE_THEME, $route->capability);
            $checked++;
        }

        $this->assertGreaterThan(0, $checked);
    }

    #[Test]
    public function it_separates_reading_leads_from_exporting_them(): void
    {
        $export = null;

        foreach (RouteCatalogue::all() as $route) {
            if ($route->route === '/leads/export') {
                $export = $route;
            }
        }

        $this->assertNotNull($export);
        $this->assertSame(Capabilities::EXPORT_LEADS, $export->capability);
    }

    #[Test]
    public function it_splits_the_catalogue_into_public_and_privileged(): void
    {
        $this->assertCount(
            count(RouteCatalogue::all()),
            array_merge(RouteCatalogue::publicRoutes(), RouteCatalogue::privilegedRoutes()),
        );
        $this->assertNotEmpty(RouteCatalogue::publicRoutes());
        $this->assertNotEmpty(RouteCatalogue::privilegedRoutes());
    }

    #[Test]
    public function it_reports_which_routes_accept_writes(): void
    {
        $readOnly = RouteDefinition::of('/x', ['GET'], Capabilities::MANAGE_THEME, 'Read.');
        $writable = RouteDefinition::of('/x', ['GET', 'POST'], Capabilities::MANAGE_THEME, 'Write.');

        $this->assertFalse($readOnly->acceptsWrites());
        $this->assertTrue($writable->acceptsWrites());
    }

    #[Test]
    public function it_bounds_pagination_on_every_route_that_takes_it(): void
    {
        $bounded = 0;

        foreach (RouteCatalogue::all() as $route) {
            if (!array_key_exists('per_page', $route->schema)) {
                continue;
            }

            $this->assertArrayHasKey('maximum', $route->schema['per_page'], $route->fullPath());
            $this->assertLessThanOrEqual(200, $route->schema['per_page']['maximum']);
            $bounded++;
        }

        $this->assertGreaterThan(0, $bounded);
    }
}
