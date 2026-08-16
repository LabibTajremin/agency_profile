<?php

declare(strict_types=1);

namespace Edulume\Core\Domain\Theming;

use Edulume\Core\Domain\Color\ColorSpace;
use Edulume\Core\Domain\Color\NeutralScale;
use Edulume\Core\Domain\Color\Srgb;

/**
 * Turns a settings object into the complete set of CSS custom properties for one mode.
 *
 * This is the only place a design decision becomes a value. Nothing downstream — not the
 * theme stylesheet, not a block, not a template — may contain a colour, font or spacing
 * literal; it reads a token from here instead. That is what makes a global change one
 * recompile rather than a search across the codebase.
 */
final class TokenCompiler
{
    public const PREFIX = '--edulume-';

    private const PIXELS_PER_REM = 16.0;
    private const OUTPUT_PRECISION = 4;

    private const SURFACE_STEP_LIGHT_MODE = 0;
    private const SUBTLE_SURFACE_STEP_LIGHT_MODE = 1;
    private const BORDER_STEP_LIGHT_MODE = 3;
    private const MUTED_INK_STEP_LIGHT_MODE = 7;
    private const INK_STEP_LIGHT_MODE = 10;

    private const SURFACE_STEP_DARK_MODE = 10;
    private const SUBTLE_SURFACE_STEP_DARK_MODE = 9;
    private const BORDER_STEP_DARK_MODE = 7;
    private const MUTED_INK_STEP_DARK_MODE = 4;
    private const INK_STEP_DARK_MODE = 0;

    public function __construct(private readonly PaletteGenerator $paletteGenerator)
    {
    }

    /**
     * @return array<string, string>
     */
    public function compile(ThemeSettings $settings, ThemeMode $mode): array
    {
        $seed = $settings->accentSeed();
        $palette = $this->paletteGenerator->generate($seed);
        $neutrals = self::neutralsFor($seed, $mode);

        return array_merge(
            $this->accentTokens($palette, $mode),
            $this->neutralTokens($neutrals, $mode),
            $this->typographyTokens($settings->typography),
            $this->layoutTokens($settings->layout),
            $this->motionTokens($settings->motion),
            $this->patternTokens($settings->pattern, $palette, $neutrals, $mode),
        );
    }

    /**
     * @return array<string, string>
     */
    private function accentTokens(AccentPalette $palette, ThemeMode $mode): array
    {
        $tokens = [];

        foreach ($palette->steps() as $index => $step) {
            $tokens[self::PREFIX . 'accent-' . $index] = $step->toHex();
            $tokens[self::PREFIX . 'on-accent-' . $index] = $palette->foregroundOn($index)->toHex();
        }

        $tokens[self::PREFIX . 'accent'] = $palette->fill($mode)->toHex();
        $tokens[self::PREFIX . 'on-accent'] = $palette->onFill($mode)->toHex();
        $tokens[self::PREFIX . 'accent-text'] = $palette->text($mode)->toHex();

        return $tokens;
    }

    /**
     * @return array<string, string>
     */
    /**
     * The neutral scale each mode is built from.
     *
     * Light mode tints its greys toward the accent, which is what stops a teal site and a
     * crimson site sharing the same cold grey. Dark mode cannot do the same, because the two
     * ends of one ramp are the two modes' surfaces: step 0 is the light-mode background *and*
     * the dark-mode ink. Warming step 0 to cream for dark-mode text would turn every light-mode
     * page cream along with it.
     *
     * So dark mode gets its own ramp — midnight blue at the dark end, cream at the light end —
     * and the two modes stop fighting over the same rungs.
     *
     * Public and static because `PaletteGenerator` has to resolve accent-as-text against the
     * exact surface that will be behind it. Two places deriving that surface independently is
     * how a palette ends up proving contrast against a background nothing paints.
     */
    public static function neutralsFor(Srgb $seed, ThemeMode $mode): NeutralScale
    {
        return $mode === ThemeMode::Dark
            ? NeutralScale::midnight()
            : NeutralScale::fromHue(ColorSpace::srgbToOklch($seed)->hue);
    }

    /**
     * @return array<string, string>
     */
    private function neutralTokens(NeutralScale $neutrals, ThemeMode $mode): array
    {
        $tokens = [];

        foreach ($neutrals->steps() as $index => $step) {
            $tokens[self::PREFIX . 'neutral-' . $index] = $step->toHex();
        }

        $isLight = $mode === ThemeMode::Light;

        $tokens[self::PREFIX . 'surface'] = $neutrals
            ->step($isLight ? self::SURFACE_STEP_LIGHT_MODE : self::SURFACE_STEP_DARK_MODE)->toHex();
        $tokens[self::PREFIX . 'surface-subtle'] = $neutrals
            ->step($isLight ? self::SUBTLE_SURFACE_STEP_LIGHT_MODE : self::SUBTLE_SURFACE_STEP_DARK_MODE)->toHex();
        $tokens[self::PREFIX . 'border'] = $neutrals
            ->step($isLight ? self::BORDER_STEP_LIGHT_MODE : self::BORDER_STEP_DARK_MODE)->toHex();
        $tokens[self::PREFIX . 'ink-muted'] = $neutrals
            ->step($isLight ? self::MUTED_INK_STEP_LIGHT_MODE : self::MUTED_INK_STEP_DARK_MODE)->toHex();
        $tokens[self::PREFIX . 'ink'] = $neutrals
            ->step($isLight ? self::INK_STEP_LIGHT_MODE : self::INK_STEP_DARK_MODE)->toHex();

        return $tokens;
    }

    /**
     * @return array<string, string>
     */
    private function typographyTokens(TypographySettings $typography): array
    {
        $tokens = [];

        foreach (FontRole::cases() as $role) {
            $tokens[self::PREFIX . 'font-' . $role->value] = $typography->familyFor($role)->toCssStack();
        }

        foreach ($typography->scale->toClampMap() as $step => $size) {
            $tokens[self::PREFIX . 'font-size-' . self::stepName($step)] = $size;
        }

        return $tokens;
    }

    /**
     * @return array<string, string>
     */
    private function layoutTokens(LayoutSettings $layout): array
    {
        $spacing = (int) round($layout->sectionSpacingPixels * $layout->density->spacingMultiplier());

        return [
            self::PREFIX . 'content-width' => self::toRem($layout->contentWidthPixels),
            self::PREFIX . 'wide-width' => self::toRem($layout->wideWidthPixels),
            self::PREFIX . 'gutter' => self::toRem($layout->gutterPixels),
            self::PREFIX . 'section-spacing' => self::toRem($spacing),
            self::PREFIX . 'radius' => self::toRem($layout->cornerRadiusPixels),
            self::PREFIX . 'border-width' => self::toRem($layout->borderWidthPixels),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function motionTokens(MotionSettings $motion): array
    {
        $timing = $motion->timing();

        return [
            self::PREFIX . 'duration-fast' => $timing->fastMilliseconds . 'ms',
            self::PREFIX . 'duration-base' => $timing->baseMilliseconds . 'ms',
            self::PREFIX . 'duration-slow' => $timing->slowMilliseconds . 'ms',
            self::PREFIX . 'stagger-step' => $timing->staggerStepMilliseconds . 'ms',
            self::PREFIX . 'motion-travel' => self::toRem($timing->travelPixels),
            self::PREFIX . 'easing' => $motion->easing()->toCssValue(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function patternTokens(
        PatternSettings $pattern,
        AccentPalette $palette,
        NeutralScale $neutrals,
        ThemeMode $mode
    ): array {
        if (!$pattern->enabled) {
            return [
                self::PREFIX . 'pattern-image' => 'none',
                self::PREFIX . 'pattern-opacity' => '0',
            ];
        }

        $tint = $this->patternTint($pattern, $palette, $neutrals, $mode);

        return [
            self::PREFIX . 'pattern-image' => sprintf('url("%s")', $pattern->pattern()->toDataUri($tint)),
            self::PREFIX . 'pattern-opacity' => self::format($pattern->opacityFor($mode)),
            self::PREFIX . 'pattern-scale' => self::format($pattern->scale),
            self::PREFIX . 'pattern-rotation' => $pattern->rotation . 'deg',
            self::PREFIX . 'pattern-blend' => $pattern->blendMode->toCssValue(),
            self::PREFIX . 'pattern-attachment' => $pattern->attachment->toCssValue(),
        ];
    }

    private function patternTint(
        PatternSettings $pattern,
        AccentPalette $palette,
        NeutralScale $neutrals,
        ThemeMode $mode
    ): Srgb {
        return match ($pattern->colorSource) {
            PatternColorSource::Neutral => $mode === ThemeMode::Light
                ? $neutrals->darkestInk()
                : $neutrals->lightestInk(),
            PatternColorSource::Accent, PatternColorSource::Custom => $palette->fill($mode),
        };
    }

    /**
     * Negative steps become `n1`, `n2`: a custom property name cannot contain a minus sign
     * without becoming ambiguous to read.
     */
    private static function stepName(int $step): string
    {
        return $step < 0 ? 'n' . abs($step) : (string) $step;
    }

    private static function toRem(int $pixels): string
    {
        return self::format($pixels / self::PIXELS_PER_REM) . 'rem';
    }

    private static function format(float $value): string
    {
        $formatted = number_format($value, self::OUTPUT_PRECISION, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
