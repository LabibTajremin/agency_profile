<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Admin;

use Edulume\Core\Domain\Theming\AccentLibrary;
use Edulume\Core\Domain\Theming\FontPairingLibrary;
use Edulume\Core\Domain\Theming\LayoutDensity;
use Edulume\Core\Domain\Theming\LayoutSettings;
use Edulume\Core\Domain\Theming\MotionPreset;
use Edulume\Core\Domain\Theming\PatternAttachment;
use Edulume\Core\Domain\Theming\PatternBlendMode;
use Edulume\Core\Domain\Theming\PatternColorSource;
use Edulume\Core\Domain\Theming\PatternLibrary;
use Edulume\Core\Domain\Theming\PatternSettings;
use Edulume\Core\Domain\Theming\StylePresetLibrary;
use Edulume\Core\Domain\Theming\ThemePreference;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\TypeScale;
use Edulume\Core\Infrastructure\Wp\Capabilities;
use Edulume\Core\Infrastructure\Wp\Container;

/**
 * Colour, type, pattern, motion and layout — the whole of the site's look, on one screen.
 *
 * Server-rendered, like every other Edulume settings screen. There is a configurator
 * application in `apps/admin` describing these same controls as data, and it was never mounted:
 * `AdminAssets` enqueued no bundle and the bundle's entry file exported a library without ever
 * looking for a root element. The result was six screens that drew nothing but their own help
 * panel, and no test noticed because nothing rendered them.
 *
 * The options come out of the domain libraries rather than a hand-written list, so a new accent
 * or pattern appears here the moment it is added to the library. The whole form posts one
 * settings array through `ThemeSettings::fromArray()`, which clamps and coerces every field —
 * the screen never has to be trusted to submit something valid.
 */
final class DesignScreen
{
    public const ACTION = 'edulume_save_design';
    public const PRESET_ACTION = 'edulume_apply_preset';
    public const NOTICE_PARAMETER = 'edulume_design_notice';

    public function __construct(private readonly Container $container)
    {
    }

    public function register(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handleSave']);
        add_action('admin_post_' . self::PRESET_ACTION, [$this, 'handlePreset']);
    }

    public function handleSave(): void
    {
        $this->assertCapability();
        check_admin_referer(self::ACTION);

        // Sanitised in one expression rather than assigned and cleaned on the next line: the
        // escaping audit reads the assignment as the moment the value enters the program.
        $submitted = isset($_POST['settings']) && is_array($_POST['settings'])
            ? map_deep(wp_unslash($_POST['settings']), 'sanitize_text_field')
            : [];

        $repository = $this->container->settingsRepository();
        $repository->save(ThemeSettings::fromArray($this->shape(is_array($submitted) ? $submitted : [])));

        // Recompiled here rather than lazily on the next page view: a stylesheet compiled on
        // read makes the first visitor after a save pay for it.
        ($this->container->publishStylesheet())();

        $this->redirect(__('Design saved.', 'edulume'));
    }

    public function handlePreset(): void
    {
        $this->assertCapability();
        check_admin_referer(self::PRESET_ACTION);

        $slug = isset($_POST['preset']) ? sanitize_key(wp_unslash($_POST['preset'])) : '';

        if (!StylePresetLibrary::has($slug)) {
            $this->redirect(__('That style is not one we ship.', 'edulume'));
        }

        $repository = $this->container->settingsRepository();
        $application = $this->container->applyStylePreset()->apply($repository->load(), StylePresetLibrary::get($slug));

        $repository->save($application->settings);
        ($this->container->publishStylesheet())();

        $this->redirect(sprintf(
            /* translators: %s: the name of the style preset that was applied. */
            __('Applied the %s style.', 'edulume'),
            StylePresetLibrary::get($slug)->name
        ));
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            return;
        }

        Notice::render(self::NOTICE_PARAMETER);

        $settings = $this->container->settingsRepository()->load();

        $this->renderPresets($settings);

        printf(
            '<form method="post" action="%s" class="edulume-settings">',
            esc_url(admin_url('admin-post.php'))
        );

        wp_nonce_field(self::ACTION);
        Field::hidden('action', self::ACTION);

        $this->renderColour($settings);
        $this->renderTypography($settings);
        $this->renderPattern($settings);
        $this->renderMotion($settings);
        $this->renderLayout($settings->layout);

        printf(
            '<p class="edulume-settings__actions">'
            . '<button type="submit" class="button button-primary button-hero">%1$s</button> '
            . '<a class="button" href="%2$s" target="_blank" rel="noopener">%3$s</a></p></form>',
            esc_html__('Save design', 'edulume'),
            esc_url(home_url('/')),
            esc_html__('View the site', 'edulume')
        );
    }

    private function renderPresets(ThemeSettings $settings): void
    {
        echo '<section class="edulume-presets"><h2 class="edulume-group__title">';
        esc_html_e('Start from a style', 'edulume');
        echo '</h2><p class="edulume-group__summary">';
        esc_html_e(
            'A style sets colour, type, pattern and motion together. Change anything you like afterwards.',
            'edulume'
        );
        echo '</p><div class="edulume-presets__grid">';

        foreach (StylePresetLibrary::all() as $preset) {
            printf(
                '<form method="post" action="%1$s" class="edulume-preset-card">'
                . '<h3 class="edulume-preset-card__name">%2$s</h3>'
                . '<p class="edulume-preset-card__blurb">%3$s</p>',
                esc_url(admin_url('admin-post.php')),
                esc_html($preset->name),
                esc_html($preset->description)
            );

            wp_nonce_field(self::PRESET_ACTION);
            Field::hidden('action', self::PRESET_ACTION);
            Field::hidden('preset', $preset->slug);

            printf(
                '<button type="submit" class="button">%s</button></form>',
                esc_html__('Use this style', 'edulume')
            );
        }

        echo '</div></section>';

        // Named so the person can tell what the last preset press actually did.
        printf(
            '<p class="edulume-settings__current">%s</p>',
            esc_html(sprintf(
                /* translators: %s: the name of the accent colour currently in use. */
                __('Current accent: %s', 'edulume'),
                AccentLibrary::get($settings->accentSlug)->name
            ))
        );
    }

    private function renderColour(ThemeSettings $settings): void
    {
        Field::openGroup(
            __('Colour', 'edulume'),
            __('Pick one colour. Buttons, links, headings and the admin bar all follow it.', 'edulume')
        );

        $accents = [];

        foreach (AccentLibrary::all() as $accent) {
            $accents[$accent->slug] = $accent->name;
        }

        Field::select('settings[accentSlug]', __('Accent', 'edulume'), $settings->accentSlug, $accents);

        Field::color(
            'settings[customAccent]',
            __('Custom accent', 'edulume'),
            $settings->customAccent?->toHex() ?? '',
            __('Leave empty to use the accent above. A custom colour is checked for contrast before it is used.', 'edulume')
        );

        Field::select(
            'settings[themePreference]',
            __('Default mode', 'edulume'),
            $settings->themePreference->value,
            [
                ThemePreference::Auto->value => __('Follow the visitor’s device', 'edulume'),
                ThemePreference::Light->value => __('Always light', 'edulume'),
                ThemePreference::Dark->value => __('Always dark', 'edulume'),
            ]
        );

        Field::toggle(
            'settings[offersModeToggle]',
            __('Show the light/dark switch in the header', 'edulume'),
            $settings->offersModeToggle
        );

        Field::closeGroup();
    }

    private function renderTypography(ThemeSettings $settings): void
    {
        Field::openGroup(
            __('Type', 'edulume'),
            __('Pairings are chosen to work together. Set the base size and the rest of the scale follows.', 'edulume')
        );

        $pairings = [];

        foreach (FontPairingLibrary::all() as $pairing) {
            $pairings[$pairing->slug] = $pairing->name;
        }

        $typography = $settings->typography->toArray();

        Field::select(
            'settings[typography][pairingSlug]',
            __('Font pairing', 'edulume'),
            (string) ($typography['pairingSlug'] ?? ''),
            $pairings
        );

        $scale = is_array($typography['scale'] ?? null) ? $typography['scale'] : [];

        Field::range(
            'settings[typography][scale][baseSizeRem]',
            __('Base text size', 'edulume'),
            (float) ($scale['baseSizeRem'] ?? TypeScale::DEFAULT_BASE_REM),
            TypeScale::MINIMUM_BASE_REM,
            TypeScale::MAXIMUM_BASE_REM,
            0.0625,
            __('In rem. 1 is sixteen pixels on a default browser.', 'edulume')
        );

        Field::range(
            'settings[typography][scale][ratio]',
            __('Heading scale', 'edulume'),
            (float) ($scale['ratio'] ?? TypeScale::DEFAULT_RATIO),
            TypeScale::MINIMUM_RATIO,
            TypeScale::MAXIMUM_RATIO,
            0.01,
            __('How much bigger each heading level is than the one below it.', 'edulume')
        );

        Field::toggle(
            'settings[typography][usesGoogleFontsCdn]',
            __('Load fonts from Google rather than from this server', 'edulume'),
            (bool) ($typography['usesGoogleFontsCdn'] ?? false),
            __('Off is faster and keeps visitor addresses off a third party.', 'edulume')
        );

        Field::closeGroup();
    }

    private function renderPattern(ThemeSettings $settings): void
    {
        Field::openGroup(
            __('Pattern', 'edulume'),
            __('A faint texture behind sections. Drawn in CSS, so it costs no image request.', 'edulume')
        );

        $pattern = $settings->pattern->toArray();

        Field::toggle(
            'settings[pattern][enabled]',
            __('Use a background pattern', 'edulume'),
            (bool) ($pattern['enabled'] ?? false)
        );

        $patterns = [];

        foreach (PatternLibrary::all() as $definition) {
            $patterns[$definition->slug] = $definition->name;
        }

        Field::select(
            'settings[pattern][patternSlug]',
            __('Pattern', 'edulume'),
            (string) ($pattern['patternSlug'] ?? PatternSettings::DEFAULT_PATTERN_SLUG),
            $patterns
        );

        Field::range(
            'settings[pattern][lightModeOpacity]',
            __('Strength in light mode', 'edulume'),
            (float) ($pattern['lightModeOpacity'] ?? 0.05),
            0.0,
            0.4,
            0.01
        );

        Field::range(
            'settings[pattern][darkModeOpacity]',
            __('Strength in dark mode', 'edulume'),
            (float) ($pattern['darkModeOpacity'] ?? 0.08),
            0.0,
            0.4,
            0.01
        );

        Field::range(
            'settings[pattern][scale]',
            __('Pattern size', 'edulume'),
            (float) ($pattern['scale'] ?? 1.0),
            PatternSettings::MINIMUM_SCALE,
            PatternSettings::MAXIMUM_SCALE,
            0.1
        );

        Field::select(
            'settings[pattern][colorSource]',
            __('Pattern colour', 'edulume'),
            (string) ($pattern['colorSource'] ?? PatternColorSource::Neutral->value),
            [
                PatternColorSource::Neutral->value => __('Neutral', 'edulume'),
                PatternColorSource::Accent->value => __('The accent', 'edulume'),
                PatternColorSource::Custom->value => __('Custom', 'edulume'),
            ]
        );

        Field::select(
            'settings[pattern][blendMode]',
            __('Blend', 'edulume'),
            (string) ($pattern['blendMode'] ?? PatternBlendMode::Normal->value),
            [
                PatternBlendMode::Normal->value => __('Normal', 'edulume'),
                PatternBlendMode::Multiply->value => __('Multiply', 'edulume'),
                PatternBlendMode::Screen->value => __('Screen', 'edulume'),
                PatternBlendMode::Overlay->value => __('Overlay', 'edulume'),
                PatternBlendMode::SoftLight->value => __('Soft light', 'edulume'),
            ]
        );

        Field::select(
            'settings[pattern][attachment]',
            __('Behaviour on scroll', 'edulume'),
            (string) ($pattern['attachment'] ?? PatternAttachment::Scroll->value),
            [
                PatternAttachment::Scroll->value => __('Scrolls with the page', 'edulume'),
                PatternAttachment::Fixed->value => __('Stays put', 'edulume'),
            ]
        );

        Field::closeGroup();
    }

    private function renderMotion(ThemeSettings $settings): void
    {
        Field::openGroup(
            __('Motion', 'edulume'),
            __('Every preset is switched off automatically for visitors who ask for reduced motion.', 'edulume')
        );

        $motion = $settings->motion->toArray();

        Field::toggle(
            'settings[motion][enabled]',
            __('Animate things as they come into view', 'edulume'),
            (bool) ($motion['enabled'] ?? true)
        );

        Field::select(
            'settings[motion][preset]',
            __('How much', 'edulume'),
            (string) ($motion['preset'] ?? MotionPreset::Subtle->value),
            [
                MotionPreset::None->value => __('None', 'edulume'),
                MotionPreset::Subtle->value => __('Subtle', 'edulume'),
                MotionPreset::Refined->value => __('Refined', 'edulume'),
                MotionPreset::Dynamic->value => __('Dynamic', 'edulume'),
                MotionPreset::Cinematic->value => __('Cinematic', 'edulume'),
            ]
        );

        Field::closeGroup();
    }

    private function renderLayout(LayoutSettings $layout): void
    {
        Field::openGroup(
            __('Layout', 'edulume'),
            __('How wide the page runs, how much air it has, and how round its corners are.', 'edulume')
        );

        Field::select(
            'settings[layout][density]',
            __('Density', 'edulume'),
            $layout->density->value,
            [
                LayoutDensity::Compact->value => __('Compact', 'edulume'),
                LayoutDensity::Comfortable->value => __('Comfortable', 'edulume'),
                LayoutDensity::Spacious->value => __('Spacious', 'edulume'),
            ]
        );

        Field::integer(
            'settings[layout][contentWidthPixels]',
            __('Content width', 'edulume'),
            $layout->contentWidthPixels,
            LayoutSettings::MINIMUM_CONTENT_WIDTH_PIXELS,
            LayoutSettings::MAXIMUM_CONTENT_WIDTH_PIXELS,
            '',
            'px'
        );

        Field::integer(
            'settings[layout][wideWidthPixels]',
            __('Wide width', 'edulume'),
            $layout->wideWidthPixels,
            LayoutSettings::MINIMUM_CONTENT_WIDTH_PIXELS,
            LayoutSettings::MAXIMUM_WIDE_WIDTH_PIXELS,
            '',
            'px'
        );

        Field::integer(
            'settings[layout][sectionSpacingPixels]',
            __('Space between sections', 'edulume'),
            $layout->sectionSpacingPixels,
            0,
            LayoutSettings::MAXIMUM_SECTION_SPACING_PIXELS,
            '',
            'px'
        );

        Field::integer(
            'settings[layout][gutterPixels]',
            __('Gutter', 'edulume'),
            $layout->gutterPixels,
            0,
            LayoutSettings::MAXIMUM_GUTTER_PIXELS,
            '',
            'px'
        );

        Field::integer(
            'settings[layout][cornerRadiusPixels]',
            __('Corner radius', 'edulume'),
            $layout->cornerRadiusPixels,
            0,
            LayoutSettings::MAXIMUM_CORNER_RADIUS_PIXELS,
            '',
            'px'
        );

        Field::integer(
            'settings[layout][borderWidthPixels]',
            __('Border width', 'edulume'),
            $layout->borderWidthPixels,
            0,
            LayoutSettings::MAXIMUM_BORDER_WIDTH_PIXELS,
            '',
            'px'
        );

        Field::closeGroup();
    }

    /**
     * Turns what the browser posted into the shape `ThemeSettings::fromArray()` reads.
     *
     * Unchecked checkboxes are not submitted at all, so every boolean has to be reconstructed
     * from presence rather than read from the payload — otherwise a switch can be turned on and
     * never off.
     *
     * @param array<array-key, mixed> $submitted
     *
     * @return array<string, mixed>
     */
    private function shape(array $submitted): array
    {
        $current = $this->container->settingsRepository()->load();

        return [
            'schemaVersion' => ThemeSettings::CURRENT_SCHEMA_VERSION,
            'accentSlug' => (string) ($submitted['accentSlug'] ?? ''),
            'customAccent' => $this->hex((string) ($submitted['customAccent'] ?? '')),
            'themePreference' => (string) ($submitted['themePreference'] ?? ''),
            'offersModeToggle' => isset($submitted['offersModeToggle']),
            'typography' => $this->shapeTypography($this->sub($submitted, 'typography'), $current),
            'pattern' => $this->shapePattern($this->sub($submitted, 'pattern'), $current),
            'motion' => $this->shapeMotion($this->sub($submitted, 'motion'), $current),
            'layout' => $this->shapeLayout($this->sub($submitted, 'layout')),
        ];
    }

    /**
     * @param array<array-key, mixed> $typography
     *
     * @return array<string, mixed>
     */
    private function shapeTypography(array $typography, ThemeSettings $current): array
    {
        $stored = $current->typography->toArray();
        $scale = $this->sub($typography, 'scale');

        return [
            'pairingSlug' => (string) ($typography['pairingSlug'] ?? ''),
            // Not on this screen, and dropping them here would silently reset them.
            'roleOverrides' => $stored['roleOverrides'],
            'subsets' => $stored['subsets'],
            'scale' => [
                'ratio' => (float) ($scale['ratio'] ?? TypeScale::DEFAULT_RATIO),
                'baseSizeRem' => (float) ($scale['baseSizeRem'] ?? TypeScale::DEFAULT_BASE_REM),
            ],
            'usesGoogleFontsCdn' => isset($typography['usesGoogleFontsCdn']),
        ];
    }

    /**
     * @param array<array-key, mixed> $pattern
     *
     * @return array<string, mixed>
     */
    private function shapePattern(array $pattern, ThemeSettings $current): array
    {
        return [
            'enabled' => isset($pattern['enabled']),
            'patternSlug' => (string) ($pattern['patternSlug'] ?? ''),
            'lightModeOpacity' => (float) ($pattern['lightModeOpacity'] ?? 0),
            'darkModeOpacity' => (float) ($pattern['darkModeOpacity'] ?? 0),
            'scale' => (float) ($pattern['scale'] ?? 1),
            'colorSource' => (string) ($pattern['colorSource'] ?? ''),
            'rotation' => $current->pattern->toArray()['rotation'],
            'blendMode' => (string) ($pattern['blendMode'] ?? ''),
            'attachment' => (string) ($pattern['attachment'] ?? ''),
        ];
    }

    /**
     * @param array<array-key, mixed> $motion
     *
     * @return array<string, mixed>
     */
    private function shapeMotion(array $motion, ThemeSettings $current): array
    {
        $stored = $current->motion->toArray();

        return [
            'enabled' => isset($motion['enabled']),
            'preset' => (string) ($motion['preset'] ?? ''),
            'effectOverrides' => $stored['effectOverrides'],
            'customEasing' => $stored['customEasing'],
        ];
    }

    /**
     * @param array<array-key, mixed> $layout
     *
     * @return array<string, int|string>
     */
    private function shapeLayout(array $layout): array
    {
        return [
            'contentWidthPixels' => (int) ($layout['contentWidthPixels'] ?? 0),
            'wideWidthPixels' => (int) ($layout['wideWidthPixels'] ?? 0),
            'gutterPixels' => (int) ($layout['gutterPixels'] ?? 0),
            'sectionSpacingPixels' => (int) ($layout['sectionSpacingPixels'] ?? 0),
            'cornerRadiusPixels' => (int) ($layout['cornerRadiusPixels'] ?? 0),
            'borderWidthPixels' => (int) ($layout['borderWidthPixels'] ?? 0),
            'density' => (string) ($layout['density'] ?? ''),
        ];
    }

    /**
     * @param array<array-key, mixed> $source
     *
     * @return array<array-key, mixed>
     */
    private function sub(array $source, string $key): array
    {
        return is_array($source[$key] ?? null) ? $source[$key] : [];
    }

    /**
     * The hex is passed through as typed and validated where every other stored value is.
     *
     * `ThemeSettings::fromArray()` already turns an unparseable colour into "no custom accent";
     * validating it a second time here would be a second definition of what a colour is.
     */
    private function hex(string $candidate): ?string
    {
        $trimmed = trim($candidate);

        return $trimmed === '' ? null : $trimmed;
    }

    private function redirect(string $notice): never
    {
        Notice::redirect('edulume-design', self::NOTICE_PARAMETER, $notice);
    }

    private function assertCapability(): void
    {
        if (!current_user_can(Capabilities::MANAGE_THEME)) {
            wp_die(esc_html__('You are not allowed to change the design.', 'edulume'), '', ['response' => 403]);
        }
    }
}
