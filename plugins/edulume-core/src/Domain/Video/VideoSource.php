<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Video;

/**
 * Where a video is hosted.
 *
 * Three, and the difference between them is not cosmetic: a self-hosted MP4 is a `<video>`
 * element the browser will autoplay muted every time, while a Facebook or YouTube embed is a
 * third-party iframe that autoplays when it feels like it and sets cookies when it does. That
 * distinction drives the consent gate, the iframe budget and the honest advice in the admin
 * help text, so it is modelled rather than sniffed from the URL at render time.
 */
enum VideoSource: string
{
    case Facebook = 'facebook';
    case YouTube = 'youtube';
    case Mp4 = 'mp4';

    public function isThirdParty(): bool
    {
        return $this !== self::Mp4;
    }

    /**
     * Whether muted autoplay can be relied on.
     *
     * Only the self-hosted case. Everything else is a request to somebody else's player.
     */
    public function autoplayIsReliable(): bool
    {
        return $this === self::Mp4;
    }

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::YouTube => 'YouTube',
            self::Mp4 => 'Uploaded video file',
        };
    }
}
