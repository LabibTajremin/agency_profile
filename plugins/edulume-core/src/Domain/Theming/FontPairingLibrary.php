<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * The ten curated pairings offered before any font dropdown is opened.
 */
final class FontPairingLibrary
{
    public const PAIRING_COUNT = 10;

    public const DEFAULT_PAIRING_SLUG = 'modern-clarity';

    /**
     * @var array<string, array{string, string, string}>
     */
    private const PAIRINGS = [
        'modern-clarity' => ['Modern Clarity', 'plus-jakarta-sans', 'inter'],
        'oxford-editorial' => ['Oxford Editorial', 'playfair-display', 'source-serif-4'],
        'campus-report' => ['Campus Report', 'merriweather', 'source-sans-3'],
        'gulf-premium' => ['Gulf Premium', 'fraunces', 'dm-sans'],
        'nordic-minimal' => ['Nordic Minimal', 'outfit', 'work-sans'],
        'prospectus' => ['Prospectus', 'lora', 'figtree'],
        'technical-brief' => ['Technical Brief', 'ibm-plex-sans', 'ibm-plex-sans'],
        'classic-scholar' => ['Classic Scholar', 'eb-garamond', 'libre-baskerville'],
        'bold-brief' => ['Bold Brief', 'sora', 'manrope'],
        'slab-authority' => ['Slab Authority', 'roboto-slab', 'inter'],
    ];

    /**
     * @return list<FontPairing>
     */
    public static function all(): array
    {
        $pairings = [];

        foreach (self::PAIRINGS as $slug => $pairing) {
            $pairings[] = self::build($slug, $pairing);
        }

        return $pairings;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::PAIRINGS);
    }

    public static function has(string $slug): bool
    {
        return array_key_exists($slug, self::PAIRINGS);
    }

    /**
     * @throws InvalidTypographyException when the slug is not curated
     */
    public static function get(string $slug): FontPairing
    {
        if (!array_key_exists($slug, self::PAIRINGS)) {
            throw InvalidTypographyException::forUnknownPairing($slug);
        }

        return self::build($slug, self::PAIRINGS[$slug]);
    }

    /**
     * @param array{string, string, string} $pairing
     */
    private static function build(string $slug, array $pairing): FontPairing
    {
        [$name, $headingFamilySlug, $bodyFamilySlug] = $pairing;

        return FontPairing::of($slug, $name, $headingFamilySlug, $bodyFamilySlug);
    }
}
