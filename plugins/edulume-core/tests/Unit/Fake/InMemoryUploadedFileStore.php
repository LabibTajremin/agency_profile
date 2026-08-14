<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Unit\Fake;

use Edulume\Core\Application\Port\UploadedFileStore;

/** Uploaded files in an array, so an erasure can be proven complete. */
final class InMemoryUploadedFileStore implements UploadedFileStore
{
    /** @var array<int, list<string>> */
    private array $files = [];

    public function add(int $leadId, string $path): void
    {
        $this->files[$leadId][] = $path;
    }

    public function deleteAllFor(int $leadId): void
    {
        unset($this->files[$leadId]);
    }

    public function pathsFor(int $leadId): array
    {
        return $this->files[$leadId] ?? [];
    }
}
