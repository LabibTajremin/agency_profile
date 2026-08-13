<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Content;

use InvalidArgumentException;

final class UnknownPostTypeException extends InvalidArgumentException
{
    public static function forKey(string $postTypeKey): self
    {
        return new self(sprintf('"%s" is not part of the Edulume content model.', $postTypeKey));
    }
}
