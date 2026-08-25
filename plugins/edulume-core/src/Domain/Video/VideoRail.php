<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Video;

/**
 * A row of videos and how it behaves.
 *
 * Unplayable items are dropped on the way out rather than rendered and hidden. A card whose URL
 * no longer resolves is a card a visitor can tap that does nothing, and the owner has no way to
 * find out — the admin's own "missing poster" notice is built on the same idea: tell the person
 * who can fix it, do not show the person who cannot.
 */
final class VideoRail
{
    public const ASPECTS = ['9:16', '16:9', '1:1'];

    /**
     * @param list<VideoItem> $items
     */
    private function __construct(
        public readonly bool $enabled,
        public readonly string $title,
        public readonly string $subtitle,
        public readonly string $aspect,
        public readonly bool $autoplay,
        public readonly bool $consent,
        public readonly array $items,
    ) {
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $items = [];

        foreach (is_array($stored['items'] ?? null) ? $stored['items'] : [] as $row) {
            if (is_array($row)) {
                $items[] = VideoItem::fromArray($row);
            }
        }

        $aspect = is_string($stored['aspect'] ?? null) ? $stored['aspect'] : '';

        return new self(
            !array_key_exists('enabled', $stored) || (bool) $stored['enabled'],
            is_string($stored['title'] ?? null) ? $stored['title'] : '',
            is_string($stored['subtitle'] ?? null) ? $stored['subtitle'] : '',
            in_array($aspect, self::ASPECTS, true) ? $aspect : '9:16',
            !array_key_exists('autoplay', $stored) || (bool) $stored['autoplay'],
            (bool) ($stored['consent'] ?? false),
            $items,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'aspect' => $this->aspect,
            'autoplay' => $this->autoplay,
            'consent' => $this->consent,
            'items' => array_map(static fn (VideoItem $item): array => $item->toArray(), $this->items),
        ];
    }

    /**
     * The items worth rendering.
     *
     * @return list<VideoItem>
     */
    public function playableItems(): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (VideoItem $item): bool => $item->isPlayable()
        ));
    }

    public function hasAnythingToShow(): bool
    {
        return $this->enabled && $this->playableItems() !== [];
    }

    /**
     * Items the owner has not given a poster, so the admin can tell them.
     *
     * @return list<VideoItem>
     */
    public function itemsMissingPosters(): array
    {
        return array_values(array_filter(
            $this->playableItems(),
            static fn (VideoItem $item): bool => $item->posterId === 0
        ));
    }

    /**
     * The CSS aspect ratio, ready to print.
     */
    public function aspectRatio(): string
    {
        return str_replace(':', ' / ', $this->aspect);
    }
}
