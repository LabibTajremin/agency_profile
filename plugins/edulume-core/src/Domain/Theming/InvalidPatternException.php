<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use InvalidArgumentException;

final class InvalidPatternException extends InvalidArgumentException
{
    public static function forMissingTintPlaceholder(string $slug, string $placeholder): self
    {
        return new self(sprintf(
            'Pattern "%s" cannot be tinted: its markup contains no %s placeholder.',
            $slug,
            $placeholder
        ));
    }

    public static function forMarkupThatIsNotSvg(string $slug): self
    {
        return new self(sprintf('Pattern "%s" must be a single <svg> element.', $slug));
    }

    public static function forUnknownSlug(string $slug): self
    {
        return new self(sprintf('"%s" is not a known pattern.', $slug));
    }
}
