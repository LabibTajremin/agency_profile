<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Seo;

/**
 * One JSON-LD document.
 *
 * Emitted as a `@graph` rather than several sibling scripts, so a page that is both an
 * organisation and a course does not present search engines with two unrelated documents and
 * leave them to guess which describes the page.
 */
final class StructuredData
{
    public const CONTEXT = 'https://schema.org';

    /**
     * @param list<array<string, mixed>> $nodes
     */
    private function __construct(private readonly array $nodes)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function with(SchemaType $type, array $properties): self
    {
        $node = array_merge(['@type' => $type->value], $this->pruned($properties));

        return new self([...$this->nodes, $node]);
    }

    public function isEmpty(): bool
    {
        return $this->nodes === [];
    }

    public function count(): int
    {
        return count($this->nodes);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['@context' => self::CONTEXT, '@graph' => $this->nodes];
    }

    public function toJson(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $encoded = json_encode(
            $this->toArray(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
        );

        return $encoded === false ? '' : $encoded;
    }

    /**
     * Empty properties are dropped rather than emitted as null. A `Course` with
     * `"provider": null` validates worse than one that never claimed a provider.
     *
     * @param array<string, mixed> $properties
     *
     * @return array<string, mixed>
     */
    private function pruned(array $properties): array
    {
        $kept = [];

        foreach ($properties as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $kept[$key] = is_array($value) ? $this->pruned($value) : $value;
        }

        return $kept;
    }
}
