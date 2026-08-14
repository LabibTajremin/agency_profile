<?php

declare(strict_types=1);

namespace Edulume\Core\Application\Port;

/**
 * The files a lead uploaded with their enquiry.
 */
interface UploadedFileStore
{
    public function deleteAllFor(int $leadId): void;

    /**
     * @return list<string>
     */
    public function pathsFor(int $leadId): array;
}
