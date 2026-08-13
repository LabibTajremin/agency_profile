<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Theming;

/**
 * A compiled stylesheet that now exists at a URL.
 */
final class PublishedStylesheet
{
    private function __construct(
        public readonly CompiledStylesheet $stylesheet,
        public readonly string $url,
    ) {
    }

    public static function of(CompiledStylesheet $stylesheet, string $url): self
    {
        return new self($stylesheet, $url);
    }

    public function version(): string
    {
        return $this->stylesheet->hash;
    }
}
