<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Blocks;

/**
 * A style variation.
 *
 * A variation changes appearance only. The moment one starts changing what a block *contains*,
 * switching variations starts losing content, and the editor's style switcher becomes a thing
 * people are afraid to click.
 */
final class BlockVariation
{
    public function __construct(
        public readonly string $slug,
        public readonly string $label,
        public readonly bool $isDefault = false,
    ) {
    }

    public static function default(string $slug, string $label): self
    {
        return new self($slug, $label, true);
    }
}
