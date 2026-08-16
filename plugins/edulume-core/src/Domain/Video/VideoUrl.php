<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Video;

/**
 * Recognising a video URL, and turning it into something embeddable.
 *
 * Validation happens when the owner saves, not when a visitor loads the page. A URL that does
 * not embed produces a grey rectangle on the front page and nothing anywhere that says why, so
 * the only useful moment to refuse it is while the person who pasted it is still looking at it.
 *
 * Facebook URLs are stored raw and encoded at render time, because Facebook's plugin endpoint
 * takes the whole page URL as a parameter and there is no id to extract without an API token
 * this product is not going to ask for. YouTube URLs are reduced to their eleven-character id,
 * because six URL shapes point at the same video and storing which one somebody happened to
 * copy is storing noise.
 */
final class VideoUrl
{
    private const YOUTUBE_ID = '[A-Za-z0-9_-]{11}';

    private function __construct(
        public readonly VideoSource $source,
        public readonly string $value,
        public readonly bool $isValid,
    ) {
    }

    public static function parse(VideoSource $source, string $url): self
    {
        $trimmed = trim($url);

        return match ($source) {
            VideoSource::Facebook => new self($source, $trimmed, self::isFacebook($trimmed)),
            VideoSource::YouTube => self::youTube($trimmed),
            VideoSource::Mp4 => new self($source, $trimmed, self::isMp4($trimmed)),
        };
    }

    /**
     * The URL to put in an iframe or a `<video>`, with autoplay and muting already applied.
     *
     * Muted is not negotiable and not a parameter. Every browser blocks autoplay with sound, so
     * an unmuted autoplay request is a video that silently does not start — which looks exactly
     * like a broken player.
     */
    public function embedUrl(bool $autoplay = true): string
    {
        if (!$this->isValid) {
            return '';
        }

        return match ($this->source) {
            VideoSource::Facebook => 'https://www.facebook.com/plugins/video.php?href='
                . rawurlencode($this->value)
                . '&show_text=false&autoplay=' . ($autoplay ? '1' : '0') . '&mute=1',
            VideoSource::YouTube => 'https://www.youtube-nocookie.com/embed/' . $this->value
                . '?autoplay=' . ($autoplay ? '1' : '0')
                . '&mute=1&playsinline=1&rel=0&modestbranding=1',
            VideoSource::Mp4 => $this->value,
        };
    }

    private static function isFacebook(string $url): bool
    {
        if (!self::isHttps($url)) {
            return false;
        }

        $patterns = [
            '#^https://(www\.)?facebook\.com/[^/]+/videos/[^/]+#i',
            '#^https://(www\.)?facebook\.com/watch/?\?v=\d+#i',
            '#^https://(www\.)?facebook\.com/reel/\d+#i',
            '#^https://(www\.)?facebook\.com/video\.php\?v=\d+#i',
            '#^https://fb\.watch/[A-Za-z0-9_-]+#i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function youTube(string $url): self
    {
        $patterns = [
            '#youtube\.com/watch\?(?:.*&)?v=(' . self::YOUTUBE_ID . ')#i',
            '#youtu\.be/(' . self::YOUTUBE_ID . ')#i',
            '#youtube\.com/embed/(' . self::YOUTUBE_ID . ')#i',
            '#youtube\.com/shorts/(' . self::YOUTUBE_ID . ')#i',
            '#youtube\.com/live/(' . self::YOUTUBE_ID . ')#i',
            '#youtube-nocookie\.com/embed/(' . self::YOUTUBE_ID . ')#i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches) === 1) {
                return new self(VideoSource::YouTube, $matches[1], true);
            }
        }

        // A bare id is what somebody who has read the field label twice ends up pasting.
        if (preg_match('#^' . self::YOUTUBE_ID . '$#', $url) === 1) {
            return new self(VideoSource::YouTube, $url, true);
        }

        return new self(VideoSource::YouTube, $url, false);
    }

    /**
     * A file, not a page.
     *
     * Relative paths are accepted because that is what a media-library URL looks like once a
     * site has been moved between domains; absolute ones must be HTTPS, because a video served
     * over plain HTTP is a mixed-content block on every page it appears on.
     */
    private static function isMp4(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (!in_array($extension, ['mp4', 'webm', 'ogv'], true)) {
            return false;
        }

        return str_starts_with($url, '/') || self::isHttps($url);
    }

    private static function isHttps(string $url): bool
    {
        return str_starts_with(strtolower($url), 'https://');
    }
}
