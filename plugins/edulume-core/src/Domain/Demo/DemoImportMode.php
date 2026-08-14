<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Demo;

/**
 * How much of a demo to bring in.
 *
 * Three modes rather than one, because the two halves are wanted separately in practice: a site
 * with real content wants the look without the sample courses, and a site being rebuilt wants
 * the content without losing the branding it already set.
 */
enum DemoImportMode: string
{
    case Full = 'full';
    case ContentOnly = 'content-only';
    case SettingsOnly = 'settings-only';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Everything',
            self::ContentOnly => 'Content and media only',
            self::SettingsOnly => 'Theme settings only',
        };
    }

    public function importsContent(): bool
    {
        return $this !== self::SettingsOnly;
    }

    public function importsSettings(): bool
    {
        return $this !== self::ContentOnly;
    }
}
