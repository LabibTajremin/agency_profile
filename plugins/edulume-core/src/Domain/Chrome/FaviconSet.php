<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Chrome;

/**
 * The complete icon set generated from one uploaded square.
 *
 * "Complete" is the requirement doing the work here. A site that ships only `favicon.ico` looks
 * broken pinned to an iOS home screen and blank in an Android install prompt, and nobody
 * notices until a client does.
 */
final class FaviconSet
{
    /**
     * Sizes, and why each one exists. Anything not on this list is a size no platform asks for.
     *
     * @var array<int, string>
     */
    private const SIZES = [
        16 => 'Browser tab',
        32 => 'Browser tab on a high-density display',
        48 => 'Windows taskbar',
        96 => 'Android tab',
        180 => 'iOS home screen',
        192 => 'Android home screen',
        512 => 'Android splash screen and install prompt',
    ];

    /** The smallest upload that can produce the largest icon without being scaled up. */
    public const MINIMUM_SOURCE_SIZE = 512;

    public function __construct(
        public readonly string $sourceUrl,
        public readonly string $themeColor,
        public readonly string $backgroundColor,
        public readonly string $siteName,
        public readonly string $shortName = '',
    ) {
    }

    /**
     * @return array<int, string> size in pixels, mapped to the file name to generate
     */
    public function files(): array
    {
        $files = [];

        foreach (array_keys(self::SIZES) as $size) {
            $files[$size] = sprintf('edulume-icon-%dx%d.png', $size, $size);
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    public function purposes(): array
    {
        return self::SIZES;
    }

    /**
     * The `<link>` tags, as data rather than markup, so the theme escapes them once at the
     * point of output instead of this class pretending to know how to escape HTML.
     *
     * @return list<array{rel: string, href: string, sizes?: string, type?: string}>
     */
    public function linkTags(string $baseUrl): array
    {
        $base = rtrim($baseUrl, '/') . '/';
        $files = $this->files();

        $tags = [
            ['rel' => 'icon', 'href' => $base . $files[32], 'sizes' => '32x32', 'type' => 'image/png'],
            ['rel' => 'icon', 'href' => $base . $files[16], 'sizes' => '16x16', 'type' => 'image/png'],
            ['rel' => 'apple-touch-icon', 'href' => $base . $files[180], 'sizes' => '180x180'],
            ['rel' => 'manifest', 'href' => $base . 'site.webmanifest'],
        ];

        return $tags;
    }

    /**
     * The web app manifest.
     *
     * `display: browser` on purpose: a marketing site opened from a home screen with no browser
     * chrome traps the visitor with no back button, and there is nothing app-like here to
     * justify that.
     *
     * @return array<string, mixed>
     */
    public function manifest(string $baseUrl): array
    {
        $base = rtrim($baseUrl, '/') . '/';
        $icons = [];

        foreach ([192, 512] as $size) {
            $icons[] = [
                'src' => $base . $this->files()[$size],
                'sizes' => sprintf('%dx%d', $size, $size),
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ];
        }

        return [
            'name' => $this->siteName,
            'short_name' => $this->shortName === '' ? $this->siteName : $this->shortName,
            'icons' => $icons,
            'theme_color' => $this->themeColor,
            'background_color' => $this->backgroundColor,
            'display' => 'browser',
            'start_url' => $base,
        ];
    }

    public function manifestJson(string $baseUrl): string
    {
        return (string) json_encode($this->manifest($baseUrl), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Whether an upload of the given square size can produce the whole set without scaling up.
     */
    public static function accepts(int $sourceWidth, int $sourceHeight): bool
    {
        return $sourceWidth === $sourceHeight && $sourceWidth >= self::MINIMUM_SOURCE_SIZE;
    }
}
