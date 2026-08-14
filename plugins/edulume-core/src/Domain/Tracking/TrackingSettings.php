<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Tracking;

use Edulume\Core\Domain\Support\Guard;

/**
 * The trackers a site has configured, and which of them a given visitor may actually load.
 *
 * `scriptsToLoad()` is the only way anything gets on the page, so there is no path where a
 * measurement ID is present and consent is not consulted.
 */
final class TrackingSettings
{
    public const GOOGLE_ANALYTICS = 'ga4';
    public const META_PIXEL = 'meta-pixel';
    public const GOOGLE_TAG_MANAGER = 'gtm';

    private function __construct(
        /** @var list<TrackingScript> */
        private readonly array $scripts,
        public readonly bool $showsConsentBanner,
    ) {
    }

    public static function none(): self
    {
        return self::of('', '', '');
    }

    public static function of(
        string $googleAnalyticsId,
        string $metaPixelId,
        string $googleTagManagerId,
        bool $showsConsentBanner = true
    ): self {
        return new self([
            TrackingScript::of(self::GOOGLE_ANALYTICS, 'Google Analytics 4', ConsentCategory::Analytics, $googleAnalyticsId),
            TrackingScript::of(self::META_PIXEL, 'Meta Pixel', ConsentCategory::Marketing, $metaPixelId),
            TrackingScript::of(self::GOOGLE_TAG_MANAGER, 'Google Tag Manager', ConsentCategory::Analytics, $googleTagManagerId),
        ], $showsConsentBanner);
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        return self::of(
            Guard::toString($stored[self::GOOGLE_ANALYTICS] ?? null),
            Guard::toString($stored[self::META_PIXEL] ?? null),
            Guard::toString($stored[self::GOOGLE_TAG_MANAGER] ?? null),
            Guard::toBool($stored['showsConsentBanner'] ?? null, true),
        );
    }

    /**
     * @return array<string, bool|string>
     */
    public function toArray(): array
    {
        $stored = ['showsConsentBanner' => $this->showsConsentBanner];

        foreach ($this->scripts as $script) {
            $stored[$script->id] = $script->measurementId;
        }

        return $stored;
    }

    /**
     * @return list<TrackingScript>
     */
    public function configuredScripts(): array
    {
        return array_values(array_filter(
            $this->scripts,
            static fn (TrackingScript $script): bool => $script->isConfigured(),
        ));
    }

    /**
     * @return list<TrackingScript>
     */
    public function scriptsToLoad(ConsentState $consent): array
    {
        return array_values(array_filter(
            $this->scripts,
            static fn (TrackingScript $script): bool => $script->mayLoadGiven($consent),
        ));
    }

    /**
     * @return list<ConsentCategory>
     */
    public function categoriesInUse(): array
    {
        $categories = [];

        foreach ($this->configuredScripts() as $script) {
            $categories[$script->category->value] = $script->category;
        }

        return array_values($categories);
    }
}
