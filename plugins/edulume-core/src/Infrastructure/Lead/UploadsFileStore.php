<?php

declare(strict_types=1);

namespace Edulume\Core\Infrastructure\Lead;

use Edulume\Core\Application\Port\UploadedFileStore;

/**
 * Files attached to a lead, kept under `wp-content/uploads/edulume-leads/<lead id>/`.
 *
 * Outside the media library on purpose. A transcript attached to an enquiry is not site content:
 * it should not appear in the media grid, it should not be reachable by guessing an attachment
 * ID, and — most of all — erasing the lead has to erase it, which detaching a media item does
 * not do.
 */
final class UploadsFileStore implements UploadedFileStore
{
    private const DIRECTORY = 'edulume-leads';

    public function deleteAllFor(int $leadId): void
    {
        $directory = $this->directoryFor($leadId);

        if ($directory === '' || !is_dir($directory)) {
            return;
        }

        foreach ($this->pathsFor($leadId) as $path) {
            if (is_file($path)) {
                wp_delete_file($path);
            }
        }

        // Removed only when empty. A non-empty directory here means something was written that
        // this class does not know about, and deleting it blind would destroy it unexamined.
        @rmdir($directory);
    }

    public function pathsFor(int $leadId): array
    {
        $directory = $this->directoryFor($leadId);

        if ($directory === '' || !is_dir($directory)) {
            return [];
        }

        $found = [];

        foreach ((array) scandir($directory) as $entry) {
            if (!is_string($entry) || $entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;

            if (is_file($path)) {
                $found[] = $path;
            }
        }

        sort($found);

        return $found;
    }

    private function directoryFor(int $leadId): string
    {
        if ($leadId <= 0) {
            return '';
        }

        $uploads = wp_upload_dir();
        $base = is_array($uploads) && is_string($uploads['basedir'] ?? null) ? $uploads['basedir'] : '';

        return $base === '' ? '' : sprintf('%s/%s/%d', $base, self::DIRECTORY, $leadId);
    }
}
