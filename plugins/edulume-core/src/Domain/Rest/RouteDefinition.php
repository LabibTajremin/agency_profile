<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Rest;

/**
 * One REST route, described well enough to register it, document it, and audit it.
 *
 * The capability is part of the definition rather than something a controller remembers to
 * check. A nonce proves the request came from the site's own page; it says nothing about
 * whether this user is allowed to do this. A nonce alone is never authorisation.
 */
final class RouteDefinition
{
    public const NAMESPACE = 'edulume/v1';

    public const PUBLIC_CAPABILITY = '';

    private function __construct(
        public readonly string $route,
        /** @var non-empty-list<string> */
        public readonly array $methods,
        public readonly string $capability,
        public readonly string $summary,
        /** @var array<string, array<string, mixed>> */
        public readonly array $schema,
    ) {
    }

    /**
     * @param non-empty-list<string> $methods
     * @param array<string, array<string, mixed>> $schema
     */
    public static function of(
        string $route,
        array $methods,
        string $capability,
        string $summary,
        array $schema = []
    ): self {
        return new self('/' . ltrim($route, '/'), $methods, $capability, $summary, $schema);
    }

    public function isPublic(): bool
    {
        return $this->capability === self::PUBLIC_CAPABILITY;
    }

    public function fullPath(): string
    {
        return '/' . self::NAMESPACE . $this->route;
    }

    public function acceptsWrites(): bool
    {
        foreach ($this->methods as $method) {
            if ($method !== 'GET') {
                return true;
            }
        }

        return false;
    }
}
