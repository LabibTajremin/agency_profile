<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Domain\Content\DemoContent;
use Edulume\Core\Domain\Support\Guard;
use Edulume\Core\Domain\Support\NestedArray;

/**
 * Serves section copy to the theme, defaulting to the bundled demo content.
 *
 * Nothing is written on activation. A fresh install has no row here at all and still renders a
 * complete site, because the read path merges whatever is stored over `DemoContent::all()`. An
 * owner who edits one headline stores one headline; every sibling field keeps falling back.
 *
 * One option row, read once per request. The alternative — a row per section, or a `get_option`
 * per field — turns a home page with seventeen sections into seventeen or two hundred queries,
 * and there is nothing here big enough to justify that.
 */
final class SiteContent
{
    public const OPTION = 'edulume_content';
    public const VALUE_FILTER = 'edulume_content_value';
    public const MEDIA_FILTER = 'edulume_demo_media_url';
    public const MEDIA_DATA_FILTER = 'edulume_demo_media_data';

    /*
     * Above this, inlining costs more than the round trip it saves: the bytes land in every
     * page's HTML, uncached and uncompressed against the rest of the document.
     */
    private const INLINE_LIMIT_BYTES = 8192;

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function register(): void
    {
        add_filter(self::VALUE_FILTER, [$this, 'value'], 10, 2);
        add_filter(self::MEDIA_FILTER, [$this, 'mediaUrl'], 10, 2);
        add_filter(self::MEDIA_DATA_FILTER, [$this, 'mediaData'], 10, 2);
    }

    /**
     * The value at a dot path, or the caller's fallback when the path is unknown.
     */
    public function value(mixed $fallback, string $path): mixed
    {
        $value = NestedArray::get($this->all(), $path, null);

        return $value === null ? $fallback : $value;
    }

    /**
     * A URL for one of the bundled demo artworks.
     *
     * Resolved against the plugin rather than the theme so the artwork survives a theme switch,
     * and returns the fallback rather than a 404 URL when the file is not there — a broken image
     * on a demo home page is worse than an absent one.
     */
    public function mediaUrl(string $fallback, string $file): string
    {
        $name = basename($file);

        if ($name === '' || !is_readable($this->mediaDirectory() . $name)) {
            return $fallback;
        }

        return plugins_url('demos/boutique/media/' . $name, $this->pluginFile());
    }

    /**
     * One of the bundled artworks as a `data:` URI, for the rare image worth inlining.
     *
     * Worth it for exactly one image: the hero backdrop, which Lighthouse measures as the front
     * page's largest contentful paint. Fetching it costs a round trip the page cannot start
     * until the document has parsed — 504ms of load delay against a 2000ms budget, on a file of
     * three and a half kilobytes. Inlining trades those bytes for that round trip.
     *
     * Returns the fallback for anything larger than the limit, so this cannot quietly become
     * the way every image is served.
     */
    public function mediaData(string $fallback, string $file): string
    {
        $path = $this->mediaDirectory() . basename($file);

        if (basename($file) === '' || !is_readable($path)) {
            return $fallback;
        }

        $size = filesize($path);

        if ($size === false || $size > self::INLINE_LIMIT_BYTES) {
            return $fallback;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return $fallback;
        }

        return 'data:image/svg+xml;base64,' . base64_encode($contents);
    }

    /**
     * @return array<string, mixed>
     */
    private function all(): array
    {
        if ($this->resolved === null) {
            $this->resolved = NestedArray::merge(
                DemoContent::all() + DemoContent::pages(),
                Guard::toArray(get_option(self::OPTION, []))
            );
        }

        return $this->resolved;
    }

    private function mediaDirectory(): string
    {
        return dirname(__DIR__, 3) . '/demos/boutique/media/';
    }

    private function pluginFile(): string
    {
        return dirname(__DIR__, 3) . '/edulume-core.php';
    }
}
