<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\StylesheetWriter;
use Edulume\Core\Application\Theming\CompiledStylesheet;

/**
 * An in-memory stand-in for the uploads directory, behaving as the real writer must: writing
 * the same content twice is a no-op, and pruning removes everything except the file kept.
 */
final class InMemoryStylesheetWriter implements StylesheetWriter
{
    private const BASE_URL = 'https://example test/wp-content/uploads/edulume/';

    /** @var array<string, string> */
    private array $files = [];

    public int $writeCount = 0;

    public function write(CompiledStylesheet $stylesheet): string
    {
        if (!array_key_exists($stylesheet->fileName(), $this->files)) {
            $this->files[$stylesheet->fileName()] = $stylesheet->css;
            $this->writeCount++;
        }

        return $this->urlFor($stylesheet);
    }

    public function urlFor(CompiledStylesheet $stylesheet): string
    {
        return self::BASE_URL . $stylesheet->fileName();
    }

    public function pruneOthers(CompiledStylesheet $keep): void
    {
        foreach (array_keys($this->files) as $fileName) {
            if ($fileName !== $keep->fileName()) {
                unset($this->files[$fileName]);
            }
        }
    }

    /**
     * @return list<string>
     */
    public function fileNames(): array
    {
        return array_keys($this->files);
    }
}
