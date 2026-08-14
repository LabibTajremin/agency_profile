<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Rest;

use Edulume\Core\Domain\Color\InvalidColorException;
use Edulume\Core\Domain\Color\Srgb;
use Edulume\Core\Domain\Theming\AccentReview;
use Edulume\Core\Domain\Theming\SectionId;
use Edulume\Core\Domain\Theming\SectionOverride;
use Edulume\Core\Domain\Theming\StylePresetLibrary;
use Edulume\Core\Domain\Theming\ThemeMode;
use Edulume\Core\Domain\Theming\ThemeSettings;
use Edulume\Core\Domain\Theming\UnknownStylePresetException;
use Edulume\Core\Infrastructure\Wp\Container;
use WP_Error;

/**
 * The theming half of the REST API.
 *
 * Each method takes the parameters it needs and returns an array or a `WP_Error`. Nothing here
 * checks a capability: the registrar derives the permission callback from the route definition,
 * so a controller cannot forget a check it never had the chance to write.
 */
final class ThemeController
{
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function readSettings(): array
    {
        return $this->container->settingsRepository()->load()->toArray();
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>|WP_Error
     */
    public function replaceSettings(array $settings)
    {
        $repository = $this->container->settingsRepository();
        $repository->save(ThemeSettings::fromArray($settings));

        // Recompiled on save rather than on read. A stylesheet compiled lazily on the first
        // front-end request makes that visitor pay for the admin's edit.
        $published = ($this->container->publishStylesheet())();

        return [
            'settings' => $repository->load()->toArray(),
            'stylesheet' => ['url' => $published->url, 'version' => $published->version()],
        ];
    }

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>|WP_Error
     */
    public function saveSectionOverride(string $section, array $override)
    {
        $sectionId = SectionId::tryFrom($section);

        if ($sectionId === null) {
            return new WP_Error('edulume_unknown_section', __('That section does not exist.', 'edulume'), ['status' => 404]);
        }

        $repository = $this->container->settingsRepository();

        $repository->saveSectionOverride($sectionId->value, SectionOverride::fromArray($override));
        $this->container->publishStylesheet()();

        return $this->sectionOverridesAsArray();
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function resetSectionOverride(string $section)
    {
        $sectionId = SectionId::tryFrom($section);

        if ($sectionId === null) {
            return new WP_Error('edulume_unknown_section', __('That section does not exist.', 'edulume'), ['status' => 404]);
        }

        // Removed rather than emptied: absence is what the resolver reads as "inherit", and a
        // stored empty override is a value that overrides with nothing.
        $this->container->settingsRepository()->deleteSectionOverride($sectionId->value);
        $this->container->publishStylesheet()();

        return $this->sectionOverridesAsArray();
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function palette(string $seed)
    {
        try {
            $palette = $this->container->paletteGenerator()->generate(Srgb::fromHex($seed));
        } catch (InvalidColorException $exception) {
            return new WP_Error('edulume_invalid_colour', $exception->getMessage(), ['status' => 400]);
        }

        return [
            'steps' => array_map(static fn (Srgb $step): string => $step->toHex(), $palette->steps()),
            'light' => ['fill' => $palette->fill(ThemeMode::Light)->toHex(), 'text' => $palette->text(ThemeMode::Light)->toHex()],
            'dark' => ['fill' => $palette->fill(ThemeMode::Dark)->toHex(), 'text' => $palette->text(ThemeMode::Dark)->toHex()],
        ];
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function reviewAccent(string $seed)
    {
        try {
            $review = $this->container->accentReviewer()->review(Srgb::fromHex($seed));
        } catch (InvalidColorException $exception) {
            return new WP_Error('edulume_invalid_colour', $exception->getMessage(), ['status' => 400]);
        }

        return [
            'seed' => $review->seed->toHex(),
            'needsAttention' => $review->needsAttention(),
            'light' => $this->modeReview($review, ThemeMode::Light),
            'dark' => $this->modeReview($review, ThemeMode::Dark),
        ];
    }

    /**
     * Compiles a stylesheet from posted settings without saving anything.
     *
     * This is what the live preview calls. It deliberately never touches the repository, so a
     * preview can never leak onto the public site.
     *
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    public function preview(array $settings): array
    {
        $compile = $this->container->compileStylesheet();
        $compiled = $compile(
            ThemeSettings::fromArray($settings),
            $this->container->settingsRepository()->loadSectionOverrides(),
        );

        return ['css' => $compiled->css, 'hash' => $compiled->hash];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPresets(): array
    {
        return array_map(
            static fn ($preset): array => $preset->toArray(),
            StylePresetLibrary::all(),
        );
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public function applyPreset(string $slug)
    {
        try {
            $preset = StylePresetLibrary::get($slug);
        } catch (UnknownStylePresetException $exception) {
            return new WP_Error('edulume_unknown_preset', $exception->getMessage(), ['status' => 404]);
        }

        $repository = $this->container->settingsRepository();
        $application = $this->container->applyStylePreset()->apply($repository->load(), $preset);

        $repository->save($application->settings);
        $this->container->publishStylesheet()();

        return [
            'settings' => $application->settings->toArray(),
            // The snapshot is what undo restores. Returned with the result so the admin can
            // offer undo without a second request, which is what makes it feel instant.
            'undo' => $application->snapshot(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sectionOverridesAsArray(): array
    {
        $overrides = [];

        foreach ($this->container->settingsRepository()->loadSectionOverrides() as $id => $override) {
            $overrides[$id] = $override->toArray();
        }

        return $overrides;
    }

    /**
     * @return array<string, mixed>
     */
    private function modeReview(AccentReview $review, ThemeMode $mode): array
    {
        return [
            'isCompliant' => $review->isCompliantIn($mode),
            'ratio' => $review->pairFor($mode)->ratio,
            'suggestion' => $review->suggestionFor($mode)?->toHex(),
        ];
    }
}
