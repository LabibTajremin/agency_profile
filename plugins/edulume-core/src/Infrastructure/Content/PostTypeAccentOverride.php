<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Domain\Color\InvalidColorException;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\AccentLibrary;

/**
 * The per-item accent override: a curated accent slug or a custom hex, stored on the post.
 *
 * Anything else is stored as an empty string, which reads as "inherit" — the same
 * inheritance-by-absence rule the section overrides follow.
 */
final class PostTypeAccentOverride
{
    public const META_KEY = '_edulume_accent';

    public static function sanitize(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $candidate = trim($value);

        if ($candidate === '' || AccentLibrary::has($candidate)) {
            return $candidate;
        }

        try {
            return Srgb::fromHex($candidate)->toHex();
        } catch (InvalidColorException) {
            return '';
        }
    }
}
