<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Content;

use Edulume\Core\Domain\Support\Guard;
use Edulume\Core\Domain\Video\VideoItem;
use Edulume\Core\Domain\Video\VideoRail;

/**
 * Hands the theme a rail it can render without knowing anything about video hosts.
 *
 * URL shapes, embed parameters and which sources need a consent gate are all decided here, so
 * the template is a loop over prepared cards. A template that builds a Facebook plugin URL is a
 * template that has to be edited when Facebook changes one, which is not a thing a theme file
 * should ever be responsible for.
 */
final class VideoRailProvider
{
    public const FILTER = 'edulume_video_rail';
    public const OPTION_KEY = 'video-rail';

    public function register(): void
    {
        add_filter(self::FILTER, [$this, 'rail'], 10, 2);
    }

    /**
     * @param array<string, mixed> $fallback
     *
     * @return array<string, mixed>
     */
    public function rail(array $fallback, string $context = 'home'): array
    {
        unset($fallback);

        return $this->prepare($this->load(), $context);
    }

    /**
     * @return array<string, mixed>
     */
    public function prepare(VideoRail $rail, string $context = 'home'): array
    {
        if (!$rail->hasAnythingToShow()) {
            return ['enabled' => false, 'items' => []];
        }

        $items = [];

        foreach ($rail->playableItems() as $item) {
            $items[] = $this->card($item, $rail);
        }

        return [
            'enabled' => true,
            'context' => $context,
            'title' => $rail->title,
            'subtitle' => $rail->subtitle,
            'aspect' => $rail->aspectRatio(),
            'autoplay' => $rail->autoplay,
            'consent' => $rail->consent,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function card(VideoItem $item, VideoRail $rail): array
    {
        $poster = $item->posterId > 0
            ? (string) wp_get_attachment_image_url($item->posterId, 'large')
            : '';

        return [
            'source' => $item->source->value,
            // The host's own name, not a title-cased slug: "TikTok" and "YouTube" are spelled
            // the way they are spelled, and `ucfirst()` gets both wrong.
            'sourceLabel' => $item->source->label(),
            'title' => $item->title,
            'duration' => $item->duration,
            'poster' => $poster,
            'thirdParty' => $item->needsConsent(),
            /*
             * A per-card shape, where the host has one it insists on. A landscape frame around
             * a TikTok or a reel gives two black pillars and a video a third of the width it
             * should be, so those override the rail's setting instead of obeying it.
             */
            'aspect' => str_replace(':', ' / ', $item->source->nativeAspect()),
            // The autoplay flag is baked into the URL the card carries, so the script never has
            // to know how each host spells it — or, for two of them, that it cannot be spelled.
            'embed' => $item->embedUrl($rail->autoplay),
        ];
    }

    /**
     * The rail, with the owner's stored settings over the seeded ones.
     *
     * Read through the same filter the rest of the theme's copy uses rather than straight from
     * the option row. Reading the row directly is how the rail ended up empty on a fresh
     * install while every other section was populated: the defaults live one layer up.
     */
    private function load(): VideoRail
    {
        $stored = apply_filters(SiteContent::VALUE_FILTER, [], self::OPTION_KEY);

        return VideoRail::fromArray(Guard::toArray($stored));
    }
}
