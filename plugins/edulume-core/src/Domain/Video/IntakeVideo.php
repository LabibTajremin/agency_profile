<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Video;

/**
 * The video shown against a destination, and which tier answered.
 *
 * The client's requirement was "every country must have one". A three-tier resolution is the
 * honest way to keep that promise: the country's own video, else the one set for the whole
 * site, else the sample that ships with the theme. The block is never empty, on any country, on
 * any install — and the tier travels with the answer so the admin can say which countries are
 * still living on the fallback.
 */
final class IntakeVideo
{
    private function __construct(
        public readonly IntakeVideoTier $tier,
        public readonly ?VideoItem $item,
    ) {
    }

    /**
     * @param array<array-key, mixed> $own the destination's own setting
     * @param array<array-key, mixed> $global the site-wide setting
     * @param array<array-key, mixed> $demo what the theme ships with
     */
    public static function resolve(array $own, array $global, array $demo): self
    {
        $tiers = [
            [IntakeVideoTier::Own, $own],
            [IntakeVideoTier::Global, $global],
            [IntakeVideoTier::Demo, $demo],
        ];

        foreach ($tiers as [$tier, $stored]) {
            $item = self::usable($stored);

            if ($item !== null) {
                return new self($tier, $item);
            }
        }

        return new self(IntakeVideoTier::None, null);
    }

    public function hasVideo(): bool
    {
        return $this->item instanceof VideoItem;
    }

    /**
     * The rail payload for a single video, so the destination page reuses the rail component
     * rather than growing a second player.
     *
     * @return array<string, mixed>
     */
    public function asRail(string $title = ''): array
    {
        if (!$this->item instanceof VideoItem) {
            return ['enabled' => false, 'items' => []];
        }

        return [
            'enabled' => true,
            'title' => $title,
            'aspect' => '16:9',
            'autoplay' => true,
            'items' => [$this->item->toArray()],
        ];
    }

    /**
     * A tier's setting, if it is switched on and its URL actually parses.
     *
     * A stored-but-broken URL falls through to the next tier rather than winning and rendering
     * nothing, which is the difference between the guarantee holding and only appearing to.
     *
     * @param array<array-key, mixed> $stored
     */
    private static function usable(array $stored): ?VideoItem
    {
        if ($stored === [] || (array_key_exists('enabled', $stored) && !$stored['enabled'])) {
            return null;
        }

        $item = VideoItem::fromArray($stored);

        return $item->isPlayable() ? $item : null;
    }
}
