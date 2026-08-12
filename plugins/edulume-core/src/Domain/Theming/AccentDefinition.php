<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\Srgb;

/**
 * A curated accent: the slug it is stored under, the name shown in the admin, and the seed
 * colour its palette is generated from.
 */
final class AccentDefinition
{
    private function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly Srgb $seed,
    ) {
    }

    public static function of(string $slug, string $name, string $seedHex): self
    {
        return new self($slug, $name, Srgb::fromHex($seedHex));
    }
}
