<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

use Edulume\Core\Application\Theming\CompiledStylesheet;

/**
 * Where the compiled stylesheet is put so a browser can fetch it.
 *
 * Implementations write a static file and return its public URL. Writing is expected to be
 * idempotent: the filename carries the content hash, so re-publishing identical CSS must not
 * churn the file or change the URL.
 */
interface StylesheetWriter
{
    public function write(CompiledStylesheet $stylesheet): string;

    public function urlFor(CompiledStylesheet $stylesheet): string;

    /**
     * Removes previously published stylesheets other than the one given, so the uploads
     * directory does not accumulate one file per save.
     */
    public function pruneOthers(CompiledStylesheet $keep): void;
}
