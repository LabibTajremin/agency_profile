<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Theming;

use Edulume\Core\Application\Port\SettingsRepository;
use Edulume\Core\Application\Port\StylesheetWriter;

/**
 * Compiles the current settings and publishes the result as a static file.
 *
 * Called on save, never on render. A 40 KB style block inlined into every request costs every
 * visitor the bytes and denies the browser its cache; a hashed static file costs one request
 * that is then cached forever and replaced only when the content changes.
 */
final class PublishStylesheet
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly CompileStylesheet $compileStylesheet,
        private readonly StylesheetWriter $stylesheetWriter,
    ) {
    }

    public function __invoke(): PublishedStylesheet
    {
        $settings = $this->settingsRepository->load();
        $overrides = $this->settingsRepository->loadSectionOverrides();

        $stylesheet = ($this->compileStylesheet)($settings, $overrides);

        $url = $this->stylesheetWriter->write($stylesheet);
        $this->stylesheetWriter->pruneOthers($stylesheet);

        return PublishedStylesheet::of($stylesheet, $url);
    }
}
