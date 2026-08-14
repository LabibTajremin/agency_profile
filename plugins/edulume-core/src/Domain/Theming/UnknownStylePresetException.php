<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use InvalidArgumentException;

final class UnknownStylePresetException extends InvalidArgumentException
{
    public static function forSlug(string $slug): self
    {
        return new self(sprintf('"%s" is not a shipped style preset.', $slug));
    }
}
