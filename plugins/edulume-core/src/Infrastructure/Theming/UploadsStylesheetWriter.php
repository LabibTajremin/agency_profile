<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Theming;

use Edulume\Core\Application\Port\StylesheetWriter;
use Edulume\Core\Application\Theming\CompiledStylesheet;
use RuntimeException;

/**
 * Writes the compiled stylesheet into `wp-content/uploads/edulume/`.
 *
 * The uploads directory rather than the plugin directory, because a plugin folder is often
 * read-only on managed hosting and is replaced wholesale on update. The filename carries the
 * content hash, so an unchanged stylesheet is never rewritten and an unchanged URL can be
 * cached indefinitely.
 */
final class UploadsStylesheetWriter implements StylesheetWriter
{
    public const DIRECTORY_NAME = 'edulume';

    private const FILE_PERMISSIONS = 0644;
    private const DIRECTORY_PERMISSIONS = 0755;

    public function write(CompiledStylesheet $stylesheet): string
    {
        $directory = $this->directoryPath();

        if (!wp_mkdir_p($directory)) {
            throw new RuntimeException(sprintf('Could not create the Edulume stylesheet directory at %s.', $directory));
        }

        $path = $directory . '/' . $stylesheet->fileName();

        if (!file_exists($path)) {
            $written = file_put_contents($path, $stylesheet->css, LOCK_EX);

            if ($written === false) {
                throw new RuntimeException(sprintf('Could not write the Edulume stylesheet to %s.', $path));
            }

            chmod($path, self::FILE_PERMISSIONS);
        }

        return $this->urlFor($stylesheet);
    }

    public function urlFor(CompiledStylesheet $stylesheet): string
    {
        return $this->directoryUrl() . '/' . $stylesheet->fileName();
    }

    public function pruneOthers(CompiledStylesheet $keep): void
    {
        $pattern = $this->directoryPath() . '/' . CompiledStylesheet::FILE_NAME_PREFIX . '*'
            . CompiledStylesheet::FILE_EXTENSION;

        foreach (glob($pattern) ?: [] as $path) {
            if (basename($path) !== $keep->fileName()) {
                wp_delete_file($path);
            }
        }
    }

    public function directoryPath(): string
    {
        $uploads = wp_get_upload_dir();

        return rtrim((string) $uploads['basedir'], '/') . '/' . self::DIRECTORY_NAME;
    }

    public function directoryUrl(): string
    {
        $uploads = wp_get_upload_dir();

        return rtrim((string) $uploads['baseurl'], '/') . '/' . self::DIRECTORY_NAME;
    }

    public function directoryPermissions(): int
    {
        return self::DIRECTORY_PERMISSIONS;
    }
}
