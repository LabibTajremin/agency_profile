<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use InvalidArgumentException;

final class InvalidTypographyException extends InvalidArgumentException
{
    public static function forUnknownFamily(string $slug): self
    {
        return new self(sprintf('"%s" is not a bundled font family.', $slug));
    }

    public static function forUnknownPairing(string $slug): self
    {
        return new self(sprintf('"%s" is not a curated font pairing.', $slug));
    }
}
