<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

/**
 * Ten complete looks, each an accent, a pairing, a pattern, a motion character and a set of
 * layout measurements that were chosen together.
 *
 * Presets lead every panel because most people recognise a look far faster than they can
 * assemble one.
 */
final class StylePresetLibrary
{
    public const PRESET_COUNT = 10;

    /**
     * @var array<string, array{string, string, array<string, mixed>}>
     */
    private const PRESETS = [
        'oxford-classic' => ['Oxford Classic', 'Deep navy, serif headlines, restrained rules.', [
            'accentSlug' => 'oxford-blue',
            'themePreference' => 'light',
            'typography' => ['pairingSlug' => 'oxford-editorial', 'scale' => ['ratio' => 1.25]],
            'pattern' => ['enabled' => true, 'patternSlug' => 'grid-lines', 'lightModeOpacity' => 0.05,
                'darkModeOpacity' => 0.09, 'scale' => 1.0],
            'motion' => ['preset' => 'refined'],
            'layout' => ['contentWidthPixels' => 1140, 'cornerRadiusPixels' => 6, 'density' => 'comfortable'],
        ]],
        'gulf-premium' => ['Gulf Premium', 'Warm gold, generous spacing, cinematic motion.', [
            'accentSlug' => 'gulf-gold',
            'themePreference' => 'light',
            'typography' => ['pairingSlug' => 'gulf-premium', 'scale' => ['ratio' => 1.333]],
            'pattern' => ['enabled' => true, 'patternSlug' => 'passport-stamps', 'lightModeOpacity' => 0.05,
                'darkModeOpacity' => 0.1, 'scale' => 1.4],
            'motion' => ['preset' => 'cinematic'],
            'layout' => ['contentWidthPixels' => 1280, 'cornerRadiusPixels' => 20, 'density' => 'spacious'],
        ]],
        'nordic-minimal' => ['Nordic Minimal', 'Cool slate, no pattern, almost no motion.', [
            'accentSlug' => 'nordic-slate',
            'themePreference' => 'light',
            'typography' => ['pairingSlug' => 'nordic-minimal', 'scale' => ['ratio' => 1.2]],
            'pattern' => ['enabled' => false],
            'motion' => ['preset' => 'subtle'],
            'layout' => ['contentWidthPixels' => 1100, 'cornerRadiusPixels' => 4, 'density' => 'compact'],
        ]],
        'warm-campus' => ['Warm Campus', 'Amber accents, soft corners, friendly rhythm.', [
            'accentSlug' => 'departure-amber',
            'themePreference' => 'light',
            'typography' => ['pairingSlug' => 'campus-report', 'scale' => ['ratio' => 1.25]],
            'pattern' => ['enabled' => true, 'patternSlug' => 'dot-scatter', 'lightModeOpacity' => 0.07,
                'darkModeOpacity' => 0.12, 'scale' => 1.2],
            'motion' => ['preset' => 'refined'],
            'layout' => ['contentWidthPixels' => 1200, 'cornerRadiusPixels' => 16, 'density' => 'comfortable'],
        ]],
        'midnight-pro' => ['Midnight Pro', 'Dark by default, indigo accents, meridian pattern.', [
            'accentSlug' => 'meridian-indigo',
            'themePreference' => 'dark',
            'typography' => ['pairingSlug' => 'bold-brief', 'scale' => ['ratio' => 1.25]],
            'pattern' => ['enabled' => true, 'patternSlug' => 'globe-meridians', 'lightModeOpacity' => 0.05,
                'darkModeOpacity' => 0.12, 'scale' => 1.6],
            'motion' => ['preset' => 'dynamic'],
            'layout' => ['contentWidthPixels' => 1240, 'cornerRadiusPixels' => 12, 'density' => 'comfortable'],
        ]],
        'editorial-journal' => ['Editorial Journal', 'Ink-grey, column rules, long-form measure.', [
            'accentSlug' => 'graphite-ink',
            'themePreference' => 'light',
            'typography' => ['pairingSlug' => 'classic-scholar', 'scale' => ['ratio' => 1.2, 'baseSizeRem' => 1.125]],
            'pattern' => ['enabled' => true, 'patternSlug' => 'column-rules', 'lightModeOpacity' => 0.04,
                'darkModeOpacity' => 0.08, 'scale' => 1.0],
            'motion' => ['preset' => 'subtle'],
            'layout' => ['contentWidthPixels' => 960, 'cornerRadiusPixels' => 2, 'density' => 'comfortable'],
        ]],
        'vibrant-youth' => ['Vibrant Youth', 'Magenta, terrazzo, everything moving.', [
            'accentSlug' => 'bursary-magenta',
            'themePreference' => 'light',
            'typography' => ['pairingSlug' => 'bold-brief', 'scale' => ['ratio' => 1.414]],
            'pattern' => ['enabled' => true, 'patternSlug' => 'terrazzo', 'lightModeOpacity' => 0.08,
                'darkModeOpacity' => 0.14, 'scale' => 1.8],
            'motion' => ['preset' => 'dynamic'],
            'layout' => ['contentWidthPixels' => 1280, 'cornerRadiusPixels' => 24, 'density' => 'spacious'],
        ]],
        'corporate-trust' => ['Corporate Trust', 'Aegean blue, plain stripes, nothing surprising.', [
            'accentSlug' => 'aegean-blue',
            'themePreference' => 'light',
            'typography' => ['pairingSlug' => 'technical-brief', 'scale' => ['ratio' => 1.2]],
            'pattern' => ['enabled' => true, 'patternSlug' => 'thin-stripes', 'lightModeOpacity' => 0.04,
                'darkModeOpacity' => 0.08, 'scale' => 1.0],
            'motion' => ['preset' => 'refined'],
            'layout' => ['contentWidthPixels' => 1200, 'cornerRadiusPixels' => 8, 'density' => 'comfortable'],
        ]],
        'soft-neutral' => ['Soft Neutral', 'Sand tones, woven texture, gentle corners.', [
            'accentSlug' => 'desert-sand',
            'themePreference' => 'light',
            'typography' => ['pairingSlug' => 'prospectus', 'scale' => ['ratio' => 1.25]],
            'pattern' => ['enabled' => true, 'patternSlug' => 'weave', 'lightModeOpacity' => 0.05,
                'darkModeOpacity' => 0.1, 'scale' => 0.8],
            'motion' => ['preset' => 'subtle'],
            'layout' => ['contentWidthPixels' => 1160, 'cornerRadiusPixels' => 18, 'density' => 'comfortable'],
        ]],
        'bold-brutalist' => ['Bold Brutalist', 'Crimson, square corners, heavy rules.', [
            'accentSlug' => 'visa-crimson',
            'themePreference' => 'light',
            'typography' => ['pairingSlug' => 'slab-authority', 'scale' => ['ratio' => 1.5]],
            'pattern' => ['enabled' => true, 'patternSlug' => 'crosshatch', 'lightModeOpacity' => 0.09,
                'darkModeOpacity' => 0.16, 'scale' => 1.0],
            'motion' => ['preset' => 'dynamic'],
            'layout' => ['contentWidthPixels' => 1320, 'cornerRadiusPixels' => 0, 'borderWidthPixels' => 3,
                'density' => 'compact'],
        ]],
    ];

    /**
     * @return list<StylePreset>
     */
    public static function all(): array
    {
        $presets = [];

        foreach (self::PRESETS as $slug => $preset) {
            $presets[] = self::build($slug, $preset);
        }

        return $presets;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::PRESETS);
    }

    public static function has(string $slug): bool
    {
        return array_key_exists($slug, self::PRESETS);
    }

    /**
     * @throws UnknownStylePresetException when the slug is not shipped
     */
    public static function get(string $slug): StylePreset
    {
        if (!array_key_exists($slug, self::PRESETS)) {
            throw UnknownStylePresetException::forSlug($slug);
        }

        return self::build($slug, self::PRESETS[$slug]);
    }

    /**
     * @param array{string, string, array<string, mixed>} $preset
     */
    private static function build(string $slug, array $preset): StylePreset
    {
        [$name, $description, $values] = $preset;

        return StylePreset::of($slug, $name, $description, $values, true);
    }
}
