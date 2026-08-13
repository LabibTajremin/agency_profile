<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Wp;

use Edulume\Core\Application\Port\SettingsRepository;
use Edulume\Core\Application\Port\StylesheetWriter;
use Edulume\Core\Application\Theming\ApplyStylePreset;
use Edulume\Core\Application\Theming\CompileStylesheet;
use Edulume\Core\Application\Theming\PublishStylesheet;
use Edulume\Core\Domain\Color\ContrastEngine;
use Edulume\Core\Domain\Theming\AccentReviewer;
use Edulume\Core\Domain\Theming\PaletteGenerator;
use Edulume\Core\Domain\Theming\SectionResolver;
use Edulume\Core\Domain\Theming\SettingsMigrator;
use Edulume\Core\Domain\Theming\TokenCompiler;
use Edulume\Core\Infrastructure\Settings\OptionSettingsRepository;
use Edulume\Core\Infrastructure\Theming\UploadsStylesheetWriter;

/**
 * The wiring, in one readable place.
 *
 * Deliberately not a reflection-driven auto-wiring container: with this few services, a list
 * of explicit factories is shorter to read, impossible to misconfigure at runtime, and shows
 * a newcomer the whole dependency graph on one screen. Services are built once and reused.
 */
final class Container
{
    /** @var array<string, object> */
    private array $services = [];

    public function contrastEngine(): ContrastEngine
    {
        return $this->service(ContrastEngine::class, static fn (): ContrastEngine => new ContrastEngine());
    }

    public function paletteGenerator(): PaletteGenerator
    {
        return $this->service(
            PaletteGenerator::class,
            fn (): PaletteGenerator => new PaletteGenerator($this->contrastEngine()),
        );
    }

    public function accentReviewer(): AccentReviewer
    {
        return $this->service(
            AccentReviewer::class,
            fn (): AccentReviewer => new AccentReviewer($this->contrastEngine()),
        );
    }

    public function tokenCompiler(): TokenCompiler
    {
        return $this->service(
            TokenCompiler::class,
            fn (): TokenCompiler => new TokenCompiler($this->paletteGenerator()),
        );
    }

    public function sectionResolver(): SectionResolver
    {
        return $this->service(SectionResolver::class, static fn (): SectionResolver => new SectionResolver());
    }

    public function settingsMigrator(): SettingsMigrator
    {
        return $this->service(SettingsMigrator::class, static fn (): SettingsMigrator => new SettingsMigrator());
    }

    public function settingsRepository(): SettingsRepository
    {
        return $this->service(
            SettingsRepository::class,
            fn (): SettingsRepository => new OptionSettingsRepository($this->settingsMigrator()),
        );
    }

    public function stylesheetWriter(): StylesheetWriter
    {
        return $this->service(
            StylesheetWriter::class,
            static fn (): StylesheetWriter => new UploadsStylesheetWriter(),
        );
    }

    public function compileStylesheet(): CompileStylesheet
    {
        return $this->service(
            CompileStylesheet::class,
            fn (): CompileStylesheet => new CompileStylesheet($this->tokenCompiler(), $this->sectionResolver()),
        );
    }

    public function publishStylesheet(): PublishStylesheet
    {
        return $this->service(
            PublishStylesheet::class,
            fn (): PublishStylesheet => new PublishStylesheet(
                $this->settingsRepository(),
                $this->compileStylesheet(),
                $this->stylesheetWriter(),
            ),
        );
    }

    public function applyStylePreset(): ApplyStylePreset
    {
        return $this->service(ApplyStylePreset::class, static fn (): ApplyStylePreset => new ApplyStylePreset());
    }

    /**
     * @template TService of object
     *
     * @param class-string<TService> $id
     * @param callable(): TService $factory
     *
     * @return TService
     */
    private function service(string $id, callable $factory): object
    {
        if (!array_key_exists($id, $this->services)) {
            $this->services[$id] = $factory();
        }

        /** @var TService $service */
        $service = $this->services[$id];

        return $service;
    }
}
