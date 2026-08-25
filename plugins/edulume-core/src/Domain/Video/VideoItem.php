<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Video;

/**
 * One video in a rail, or the single video on a destination page.
 *
 * Carries its own poster and title because the facade has to draw something before anything is
 * loaded, and "something" cannot be a grey box: a rail of grey boxes is indistinguishable from
 * a rail that failed.
 */
final class VideoItem
{
    private function __construct(
        public readonly VideoSource $source,
        public readonly VideoUrl $url,
        public readonly string $title,
        public readonly int $posterId,
        public readonly string $duration,
    ) {
    }

    public static function of(
        VideoSource $source,
        string $url,
        string $title = '',
        int $posterId = 0,
        string $duration = '',
    ): self {
        return new self(
            $source,
            VideoUrl::parse($source, $url),
            trim($title),
            max(0, $posterId),
            trim($duration),
        );
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $source = VideoSource::tryFrom(is_string($stored['source'] ?? null) ? $stored['source'] : '')
            ?? VideoSource::Mp4;

        return self::of(
            $source,
            is_string($stored['url'] ?? null) ? $stored['url'] : '',
            is_string($stored['title'] ?? null) ? $stored['title'] : '',
            is_numeric($stored['poster_id'] ?? null) ? (int) $stored['poster_id'] : 0,
            is_string($stored['duration'] ?? null) ? $stored['duration'] : '',
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source->value,
            'url' => $this->url->value,
            'title' => $this->title,
            'poster_id' => $this->posterId,
            'duration' => $this->duration,
        ];
    }

    public function isPlayable(): bool
    {
        return $this->url->isValid;
    }

    public function needsConsent(): bool
    {
        return $this->source->isThirdParty();
    }

    public function embedUrl(bool $autoplay = true): string
    {
        return $this->url->embedUrl($autoplay);
    }
}
