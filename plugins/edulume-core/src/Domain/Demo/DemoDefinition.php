<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Demo;

/**
 * One starter demo.
 *
 * A demo is a complete site, not a theme setting: content, media, menus, widgets, the homepage
 * assignment and the theme settings. Shipping a "demo" that is only a colour scheme is the
 * single most common complaint about premium themes, because the screenshot that sold it is
 * three quarters content.
 */
final class DemoDefinition
{
    /**
     * @param array<string, int> $itemCounts post type key mapped to how many items ship
     * @param array<string, mixed> $settings the theme settings this demo applies
     * @param list<string> $menus the menu locations this demo fills
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $description,
        public readonly array $itemCounts,
        public readonly array $settings,
        public readonly array $menus,
        public readonly string $homepageTitle,
        public readonly MediaLicence $mediaLicence = MediaLicence::Cc0,
    ) {
    }

    /** How many content items the whole import will create. */
    public function totalItems(): int
    {
        return array_sum($this->itemCounts);
    }

    /**
     * The post types this demo brings, in a stable order.
     *
     * Stable because the cursor indexes into it: a set whose order changed between requests
     * would resume a paused import in the wrong place.
     *
     * @return list<string>
     */
    public function postTypes(): array
    {
        $types = array_keys($this->itemCounts);

        sort($types);

        return $types;
    }

    public function itemCountFor(string $postType): int
    {
        return $this->itemCounts[$postType] ?? 0;
    }

    /**
     * Whether every bundled image may legally be redistributed with the product.
     *
     * A demo whose photography cannot be resold has to import placeholders instead — which is
     * a worse demo, but a demo that does not put the buyer in breach of a stock licence.
     */
    public function mediaIsRedistributable(): bool
    {
        return $this->mediaLicence->isRedistributable();
    }
}
