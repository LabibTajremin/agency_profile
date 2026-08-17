<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Video;

/**
 * Where a video is hosted.
 *
 * The difference between them is not cosmetic: a self-hosted MP4 is a `<video>` element the
 * browser will autoplay muted every time, while everything else is a third-party iframe that
 * autoplays when it feels like it and sets cookies when it does. That distinction drives the
 * consent gate, the iframe budget and the honest advice in the admin help text, so it is
 * modelled rather than sniffed from the URL at render time.
 *
 * Instagram and TikTok are the two that most need saying out loud: neither offers an autoplay
 * parameter at all, and TikTok's embed ships its own controls and its own branding. They are
 * supported because that is where a consultancy's short video actually lives — but an owner who
 * wants a promo that plays on sight should upload the file as well.
 */
enum VideoSource: string
{
    case Facebook = 'facebook';
    case YouTube = 'youtube';
    case Instagram = 'instagram';
    case TikTok = 'tiktok';
    case Mp4 = 'mp4';

    public function isThirdParty(): bool
    {
        return $this !== self::Mp4;
    }

    /**
     * Whether muted autoplay can be relied on.
     *
     * Only the self-hosted case. Everything else is a request to somebody else's player, and
     * two of them do not take the request at all.
     */
    public function autoplayIsReliable(): bool
    {
        return $this === self::Mp4;
    }

    /**
     * Whether the host's embed has any concept of autoplay.
     *
     * Facebook and YouTube accept the parameter and honour it inconsistently; Instagram and
     * TikTok have no such parameter, so asking is not merely unreliable, it is meaningless.
     * The admin says so rather than letting an owner tick a box that does nothing.
     */
    public function acceptsAutoplayRequest(): bool
    {
        return $this !== self::Instagram && $this !== self::TikTok;
    }

    /**
     * The shape the host's own player is built around.
     *
     * A landscape frame around a TikTok gives two black pillars and a video a third of the
     * width it should be, so the rail's shape setting is overridden per card rather than
     * applied blindly to all of them.
     */
    public function nativeAspect(): string
    {
        return match ($this) {
            self::Instagram, self::TikTok => '9:16',
            default => '',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::YouTube => 'YouTube',
            self::Instagram => 'Instagram',
            self::TikTok => 'TikTok',
            self::Mp4 => 'Uploaded video file',
        };
    }

    /**
     * What to paste, in the words the field's help text uses.
     */
    public function urlHint(): string
    {
        return match ($this) {
            self::Facebook => 'facebook.com/…/videos/…, fb.watch/… or a reel',
            self::YouTube => 'any YouTube link, including Shorts',
            self::Instagram => 'instagram.com/p/…, /reel/… or /tv/…',
            self::TikTok => 'the full tiktok.com/@name/video/… link, not the share short link',
            self::Mp4 => 'the file URL from your media library',
        };
    }
}
